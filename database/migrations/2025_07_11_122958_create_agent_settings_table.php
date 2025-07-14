<?php

declare(strict_types=1);

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
        // Drop existing table if exists
        Schema::dropIfExists('agent_settings');

        // Create optimized agent_settings table
        Schema::create('agent_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id')->unique();

            // === PAYOUT PROFILE SETTINGS ===
            $table->json('payout_profile')->nullable(); // {2D: 90, 3D: 800} or custom
            $table->unsignedBigInteger('payout_profile_source_agent_id')->nullable(); // Which agent this was inherited from
            $table->boolean('has_custom_payout_profile')->default(false); // True if agent set custom, false if inherited

            // === COMMISSION & SHARING SETTINGS ===
            $table->decimal('commission_rate', 5, 2)->nullable(); // Agent commission percentage (null = not configured)
            $table->decimal('sharing_rate', 5, 2)->nullable(); // Sharing percentage (null = not configured)
            $table->decimal('max_commission_sharing_rate', 5, 2)->default(50.00); // Dynamic limit based on payout profile

            // === COMPUTED/CACHED EFFECTIVE SETTINGS ===
            $table->json('effective_payout_profile'); // Resolved from hierarchy (for performance)
            $table->unsignedBigInteger('effective_payout_source_agent_id'); // Source of effective payout
            $table->decimal('effective_commission_rate', 5, 2)->nullable(); // Effective commission (may be inherited)
            $table->decimal('effective_sharing_rate', 5, 2)->nullable(); // Effective sharing (may be inherited)

            // === CACHE MANAGEMENT ===
            $table->boolean('is_computed')->default(false); // True if settings are computed from upline
            $table->timestamp('computed_at')->nullable(); // When settings were last computed
            $table->timestamp('cache_expires_at')->nullable(); // When to refresh cache

            // === ADDITIONAL SETTINGS ===
            $table->json('betting_limits')->nullable(); // {min: 100, max: 10000} per bet type
            $table->json('blocked_numbers')->nullable(); // Array of blocked numbers
            $table->boolean('auto_settlement')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // === FOREIGN KEYS ===
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('payout_profile_source_agent_id')->references('id')->on('agents')->onDelete('set null');
            $table->foreign('effective_payout_source_agent_id')->references('id')->on('agents')->onDelete('cascade');

            // === PERFORMANCE INDEXES ===
            $table->index(['agent_id'], 'idx_agent_settings_agent_id');
            $table->index(['is_computed', 'computed_at'], 'idx_agent_settings_computed');
            $table->index(['cache_expires_at'], 'idx_agent_settings_cache_expiry');
            $table->index(['has_custom_payout_profile'], 'idx_agent_settings_custom_payout');
            $table->index(['payout_profile_source_agent_id'], 'idx_agent_settings_payout_source');
            $table->index(['is_active'], 'idx_agent_settings_active');
        });

        // === DEFAULT PAYOUT PROFILES CONFIGURATION ===
        Schema::create('payout_profile_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50); // 'default', 'conservative', 'aggressive'
            $table->string('description')->nullable();
            $table->json('profile'); // {2D: 90, 3D: 800}
            $table->decimal('max_commission_sharing_rate', 5, 2)->default(50.00); // Max total for this profile
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_default', 'is_active'], 'idx_payout_templates_default');
            $table->index(['name'], 'idx_payout_templates_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_settings');
        Schema::dropIfExists('payout_profile_templates');
    }
};
