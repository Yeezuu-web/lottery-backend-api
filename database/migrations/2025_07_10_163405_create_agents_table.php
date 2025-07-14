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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('username', 11)->unique();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password')->nullable();
            $table->enum('agent_type', [
                'company',
                'super senior',
                'senior',
                'master',
                'agent',
                'member',
            ])->default('member');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_active')->default(true)->comment('Agent active status');
            $table->unsignedBigInteger('upline_id')->nullable();
            $table->string('phone')->nullable();

            // Custom settings for agent
            $table->json('settings')->nullable()->comment('Agent-specific settings');

            // Email verification
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('upline_id')->references('id')->on('agents')->onDelete('set null');

            // Indexes for performance
            $table->index(['agent_type', 'status']);
            $table->index('upline_id');
            $table->index('username');
            $table->index('email');
            $table->index(['is_active']);
            $table->index(['username', 'agent_type']);
            $table->index(['upline_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
