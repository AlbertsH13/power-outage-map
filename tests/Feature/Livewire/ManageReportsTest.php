<?php

namespace Tests\Feature\Livewire;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Livewire\ManageReports;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_can_render_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $activeOutage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(3),
            'description' => 'Active neighborhood outage',
        ]);

        $closedOutage = Outage::factory()->closed()->create([
            'user_id' => $user->id,
            'description' => 'Resolved yesterday',
        ]);

        Livewire::actingAs($user)
            ->test(ManageReports::class)
            ->assertStatus(200)
            ->assertSee('Active Outages')
            ->assertSee('Active neighborhood outage')
            ->assertSee('Past Reports')
            ->assertSee('Resolved yesterday');
    }

    public function test_owner_can_close_active_report_via_livewire(): void
    {
        $user = User::factory()->create();
        $outage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
        ]);

        Livewire::actingAs($user)
            ->test(ManageReports::class)
            ->call('closeReport', $outage->id)
            ->assertSee('Report marked as resolved.');

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'status' => OutageStatus::Closed->value,
        ]);
        $this->assertNotNull($outage->fresh()->closed_at);
    }

    public function test_owner_can_open_edit_modal_and_update_report_via_livewire(): void
    {
        $user = User::factory()->create();
        $outage = Outage::factory()->create([
            'user_id' => $user->id,
            'radius_km' => 1.5,
            'duration_type' => DurationType::Fixed,
            'duration_hours' => 2,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(2),
            'description' => 'Initial notes',
        ]);

        Livewire::actingAs($user)
            ->test(ManageReports::class)
            ->call('openEditModal', $outage->id)
            ->assertSet('editOutageId', $outage->id)
            ->assertSet('showEditModal', true)
            ->set('radius_km', 4.5)
            ->set('duration_type', 'fixed')
            ->set('duration_hours', 8)
            ->set('description', 'Updated via livewire')
            ->call('updateReport')
            ->assertSet('showEditModal', false)
            ->assertSee('Outage report updated successfully.');

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'radius_km' => 4.5,
            'duration_hours' => 8,
            'description' => 'Updated via livewire',
        ]);
    }

    public function test_non_owner_cannot_edit_another_users_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $outage = Outage::factory()->create([
            'user_id' => $owner->id,
            'status' => OutageStatus::Active,
        ]);

        Livewire::actingAs($otherUser)
            ->test(ManageReports::class)
            ->call('openEditModal', $outage->id)
            ->assertForbidden();
    }

    public function test_history_pagination_works(): void
    {
        $user = User::factory()->create();

        Outage::factory()->count(15)->closed()->create([
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(ManageReports::class)
            ->call('setPage', 2)
            ->assertStatus(200);
    }
}
