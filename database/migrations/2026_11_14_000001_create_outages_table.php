<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Geographic coordinates and affected radius (0.50km to 25.00km)
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('radius_km', 4, 2);

            // Precomputed bounding box coordinates for fast viewport queries
            $table->decimal('min_lat', 10, 8);
            $table->decimal('max_lat', 10, 8);
            $table->decimal('min_lng', 11, 8);
            $table->decimal('max_lng', 11, 8);

            // Duration and lifecycle
            $table->string('duration_type', 20); // 'fixed' or 'until_closed'
            $table->unsignedTinyInteger('duration_hours')->nullable(); // 1 to 72
            $table->string('status', 20)->default('active'); // 'active', 'closed', 'expired'
            $table->timestamp('expires_at');
            $table->timestamp('closed_at')->nullable();

            // Optional description (max 500 characters)
            $table->string('description', 500)->nullable();

            $table->timestamps();

            // Composite indexes defined in ARCHITECTURE.md
            $table->index(['status', 'expires_at', 'min_lat', 'max_lat', 'min_lng', 'max_lng'], 'idx_outages_viewport');
            $table->index(['user_id', 'status', 'expires_at'], 'idx_outages_user_active');
            $table->index(['status', 'expires_at'], 'idx_outages_expiration');
            $table->index(['latitude', 'longitude'], 'idx_outages_coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outages');
    }
};
