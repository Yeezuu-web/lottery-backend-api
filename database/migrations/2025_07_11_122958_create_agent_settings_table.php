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
        Schema::dropIfExists('payout_profile_templates');

        // Create new simplified agent_settings table
        Schema::create('agent_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id')->unique();

            // === DAILY LIMITS ===
            $table->decimal('daily_limit', 15, 2)->nullable()->comment('Daily betting limit in KHR');

            // === COMMISSION & SHARING (SET BY UPLINE) ===
            $table->decimal('max_commission', 5, 2)->nullable()->comment('Max commission rate set by upline');
            $table->decimal('max_share', 5, 2)->nullable()->comment('Max sharing rate set by upline');

            // === NUMBER LIMITS ===
            // Structure: {"2D": {"21": 1000000, "35": 1000000}, "3D": {"123": 2000000}}
            $table->json('number_limits')->nullable()->comment('Specific number limits by game type');

            // === BLOCKED NUMBERS ===
            $table->json('blocked_numbers')->nullable()->comment('Array of blocked numbers');

            // === STATUS ===
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // === FOREIGN KEYS ===
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');

            // === PERFORMANCE INDEXES ===
            $table->index(['agent_id'], 'idx_agent_settings_agent_id');
            $table->index(['is_active'], 'idx_agent_settings_active');
        });

        // Create daily_limit_usage table for caching daily usage
        Schema::create('daily_limit_usage', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->date('date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('last_updated_at');

            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');

            // Unique constraint for agent per day
            $table->unique(['agent_id', 'date']);
            $table->index(['agent_id', 'date']);
            $table->index(['date']);
        });

        // Create number_limit_usage table for caching number usage
        Schema::create('number_limit_usage', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('number');
            $table->enum('game_type', ['2D', '3D']);
            $table->date('date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('last_updated_at');

            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');

            // Unique constraint for agent per number per game type per day
            $table->unique(['agent_id', 'number', 'game_type', 'date']);
            $table->index(['agent_id', 'number', 'game_type', 'date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_limit_usage');
        Schema::dropIfExists('daily_limit_usage');
        Schema::dropIfExists('agent_settings');
    }
};
