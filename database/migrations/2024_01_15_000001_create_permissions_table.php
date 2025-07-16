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
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique(); // e.g., 'manage_agents'
            $table->string('display_name'); // e.g., 'Manage Agents'
            $table->string('description')->nullable(); // Human-readable description
            $table->string('category'); // e.g., 'agent_management', 'financial', 'reports'
            $table->json('agent_types')->nullable(); // Which agent types can have this permission
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
