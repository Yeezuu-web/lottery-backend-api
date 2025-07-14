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
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('order_number')->unique();

            // Core bet information (from BetData value object)
            $table->string('number');
            $table->json('channels');
            $table->json('numbers');
            $table->json('provinces');
            $table->enum('type', ['2D', '3D']);
            $table->enum('period', ['evening', 'night']);
            $table->string('option');

            // Financial
            $table->decimal('amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('KHR');
            $table->decimal('potential_payout', 15, 2)->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'accepted',
                'won',
                'lost',
                'cancelled',
            ])->default('pending');

            // Settlement information
            $table->timestamp('settled_at')->nullable();

            // Audit
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');

            // Indexes
            $table->index(['agent_id', 'status']);
            $table->index(['number', 'type']);
            $table->index(['order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
