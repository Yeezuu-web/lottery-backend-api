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
        Schema::create('agent_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('granted_by')->nullable(); // Who granted this permission
            $table->timestamp('granted_at')->nullable(); // When it was granted
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional context/restrictions
            $table->timestamps();

            // Indexes
            $table->unique(['agent_id', 'permission_id']);
            $table->index(['agent_id', 'is_active']);
            $table->index(['permission_id', 'is_active']);
            $table->index('granted_by');
            $table->index('expires_at');

            // Foreign keys
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_permissions');
    }
};
