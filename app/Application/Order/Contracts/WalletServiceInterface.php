<?php

declare(strict_types=1);

namespace App\Application\Order\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Wallet\ValueObjects\Money;

interface WalletServiceInterface
{
    /**
     * Check if agent has enough balance
     *
     * @param  Agent  $agent  The agent to check
     * @param  Money  $amount  The amount to check
     * @return bool True if enough balance, false otherwise
     */
    public function hasEnoughBalance(Agent $agent, Money $amount): bool;

    /**
     * Get agent's current balance
     *
     * @param  Agent  $agent  The agent
     * @return Money The current balance
     */
    public function getBalance(Agent $agent): Money;

    /**
     * Deduct balance from agent's wallet
     *
     * @param  Agent  $agent  The agent
     * @param  Money  $amount  The amount to deduct
     * @param  string  $description  Transaction description
     */
    public function deductBalance(Agent $agent, Money $amount, string $description): void;

    /**
     * Add balance to agent's wallet
     *
     * @param  Agent  $agent  The agent
     * @param  Money  $amount  The amount to add
     * @param  string  $description  Transaction description
     */
    public function addBalance(Agent $agent, Money $amount, string $description): void;

    /**
     * Get agent's wallet transaction history
     *
     * @param  Agent  $agent  The agent
     * @param  int  $limit  Number of transactions to return
     * @return array Array of transactions
     */
    public function getTransactionHistory(Agent $agent, int $limit = 10): array;
}
