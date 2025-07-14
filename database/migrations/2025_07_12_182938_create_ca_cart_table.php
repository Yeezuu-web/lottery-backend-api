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
        Schema::create('ca_cart', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');

            // Bet data (stored as JSON)
            $table->json('bet_data');

            // Expanded numbers and channel weights
            $table->json('expanded_numbers');
            $table->json('channel_weights');

            // Financial information
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('KHR');

            // Cart status
            $table->enum('status', ['active', 'submitted', 'expired'])->default('active');

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');

            // Indexes
            $table->index(['agent_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ca_cart');
    }
};
