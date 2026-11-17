<?php

namespace Tests\Feature\Livewire;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Livewire\CreateReportForm;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CreateReportFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_can_render_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->assertStatus(200)
            ->assertSee('Coordinates')
            ->assertSee('Affected Radius')
            ->assertSee('Expected Duration');
    }

    public function test_can_create_outage_with_fixed_duration(): void
    {
        Carbon::setTestNow('2026-11-16 10:00:00');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->set('latitude', 10.49200000)
            ->set('longitude', -66.88000000)
            ->set('radius_km', 3.0)
            ->set('duration_type', 'fixed')
            ->set('duration_hours', 5)
            ->set('description', 'Feeder trip in sector 4')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'latitude' => 10.49200000,
            'longitude' => -66.88000000,
            'radius_km' => 3.0,
            'duration_type' => DurationType::Fixed->value,
            'duration_hours' => 5,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-16 15:00:00',
            'description' => 'Feeder trip in sector 4',
        ]);

        Carbon::setTestNow();
    }

    public function test_can_create_outage_with_until_closed_duration(): void
    {
        Carbon::setTestNow('2026-11-16 10:00:00');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->set('latitude', 10.49200000)
            ->set('longitude', -66.88000000)
            ->set('radius_km', 1.5)
            ->set('duration_type', 'until_closed')
            ->set('description', 'Main line failure')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'latitude' => 10.49200000,
            'longitude' => -66.88000000,
            'radius_km' => 1.5,
            'duration_type' => DurationType::UntilClosed->value,
            'duration_hours' => null,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-19 10:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_validation_fails_for_invalid_radius_and_coordinates(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->set('latitude', 120.0)
            ->set('longitude', -200.0)
            ->set('radius_km', 50.0)
            ->call('save')
            ->assertHasErrors(['latitude', 'longitude', 'radius_km']);
    }

    public function test_user_cannot_exceed_max_active_quota(): void
    {
        $user = User::factory()->create();

        Outage::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
        ]);

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->set('latitude', 10.492)
            ->set('longitude', -66.880)
            ->set('radius_km', 2.0)
            ->set('duration_type', 'fixed')
            ->set('duration_hours', 2)
            ->call('save')
            ->assertHasErrors(['outage']);

        $this->assertDatabaseCount('outages', 3);
    }

    public function test_livewire_save_enforces_hourly_rate_limit(): void
    {
        config([
            'outage.max_active_per_user' => 100,
            'outage.rate_limit_per_hour' => 2,
        ]);

        $user = User::factory()->create();

        foreach (range(1, 2) as $i) {
            Livewire::actingAs($user)
                ->test(CreateReportForm::class)
                ->set('latitude', 10.5)
                ->set('longitude', -66.9)
                ->set('radius_km', 1)
                ->set('duration_type', 'fixed')
                ->set('duration_hours', 2)
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user)
            ->test(CreateReportForm::class)
            ->set('latitude', 10.5)
            ->set('longitude', -66.9)
            ->set('radius_km', 1)
            ->set('duration_type', 'fixed')
            ->set('duration_hours', 2)
            ->call('save')
            ->assertHasErrors(['outage']);

        $this->assertSame(
            2,
            Outage::where('user_id', $user->id)->count()
        );
    }
}
