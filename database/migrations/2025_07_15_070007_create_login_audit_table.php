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
        Schema::create('login_audit', function (Blueprint $table): void {
            $table->id();

            // Agent Information
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('username', 255);
            $table->string('agent_type', 50)->nullable(); // company, super senior, senior, master, agent, member
            $table->string('audience', 20); // upline, member

            // Authentication Details
            $table->string('status', 20); // success, failed, locked, expired
            $table->string('failure_reason', 255)->nullable(); // invalid_credentials, account_locked, etc.
            $table->timestamp('attempted_at');
            $table->timestamp('succeeded_at')->nullable();

            // Session Information
            $table->string('session_id', 255)->nullable();
            $table->string('jwt_token_id', 255)->nullable(); // JTI from JWT
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('session_ended_at')->nullable();
            $table->string('logout_reason', 50)->nullable(); // manual, expired, forced

            // Request Information
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            // Security Information
            $table->boolean('is_suspicious')->default(false);
            $table->json('risk_factors')->nullable(); // Multiple IPs, unusual location, etc.
            $table->unsignedTinyInteger('failed_attempts_count')->default(0);
            $table->timestamp('last_failed_attempt_at')->nullable();

            // Additional Context
            $table->string('referer', 500)->nullable();
            $table->json('headers')->nullable(); // Store relevant headers
            $table->json('metadata')->nullable(); // Additional tracking data

            // Timestamps
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            // Indexes for performance
            $table->index(['agent_id', 'attempted_at']);
            $table->index(['username', 'attempted_at']);
            $table->index(['status', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['audience', 'attempted_at']);
            $table->index(['jwt_token_id']);
            $table->index(['session_id']);
            $table->index(['is_suspicious']);

            // Foreign key constraint
            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audit');
    }
};
