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
        // Create agent_multi_wallets table (different from existing agent_wallets)
        Schema::create('agent_multi_wallets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_id'); // agent_id
            $table->enum('wallet_type', ['main', 'commission', 'bonus', 'pending', 'locked']);
            $table->decimal('balance', 20, 2)->default(0.00);
            $table->decimal('locked_balance', 20, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            // Foreign key to agents table
            $table->foreign('owner_id')->references('id')->on('agents')->onDelete('cascade');

            // Indexes
            $table->index(['owner_id']);
            $table->index(['wallet_type']);
            $table->index(['currency']);
            $table->index(['is_active']);
            $table->index(['last_transaction_at']);
            $table->unique(['owner_id', 'wallet_type']); // One wallet per type per agent
        });

        // Create wallet_transactions table (different from existing transactions)
        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', [
                'credit',
                'debit',
                'transfer_in',
                'transfer_out',
                'bet_placed',
                'bet_won',
                'bet_refund',
                'commission_earned',
                'commission_shared',
                'bonus_added',
                'bonus_used',
                'deposit',
                'withdrawal',
                'adjustment',
                'fee',
            ]);
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->string('reference');
            $table->text('description');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'reversed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('wallet_id')->references('id')->on('agent_multi_wallets')->onDelete('cascade');
            $table->foreign('related_transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Indexes
            $table->index(['wallet_id']);
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['reference']);
            $table->index(['related_transaction_id']);
            $table->index(['order_id']);
            $table->index(['created_at']);
            $table->index(['wallet_id', 'type']);
            $table->index(['wallet_id', 'status']);
            $table->index(['wallet_id', 'created_at']);

            // Unique constraint for reference to prevent duplicates
            $table->unique(['reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('agent_multi_wallets');
    }
};
