<?php

declare(strict_types=1);

namespace App\Application\Wallet\Services;

use App\Application\Wallet\Commands\TransferFundsCommand;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\Models\Wallet;
use App\Domain\Wallet\ValueObjects\WalletType;
use App\Shared\Exceptions\ValidationException;

final readonly class InterAgentTransferService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private AgentRepositoryInterface $agentRepository
    ) {}

    /**
     * Validate inter-agent transfer and return transfer metadata
     */
    public function validateInterAgentTransfer(TransferFundsCommand $command): array
    {
        if (! $command->isInterAgentTransfer()) {
            throw new ValidationException('Transfer must specify initiator agent ID for inter-agent transfers');
        }

        // Get wallets and their owners
        $fromWallet = $this->walletRepository->findById($command->fromWalletId);
        $toWallet = $this->walletRepository->findById($command->toWalletId);

        if (! $fromWallet instanceof Wallet || ! $toWallet instanceof Wallet) {
            throw WalletException::notFound($command->fromWalletId);
        }

        // Get agents
        $fromAgent = $this->agentRepository->findById($fromWallet->getOwnerId());
        $toAgent = $this->agentRepository->findById($toWallet->getOwnerId());
        $initiatorAgent = $this->agentRepository->findById($command->initiatorAgentId);

        if (! $fromAgent instanceof Agent || ! $toAgent instanceof Agent || ! $initiatorAgent instanceof Agent) {
            throw new ValidationException('Invalid agent in transfer');
        }

        // Validate transfer permissions
        $this->validateTransferPermissions($initiatorAgent, $fromAgent, $toAgent, $command);

        // Validate business rules
        $this->validateBusinessRules($fromAgent, $toAgent, $fromWallet, $toWallet, $command);

        return [
            'from_agent' => $fromAgent,
            'to_agent' => $toAgent,
            'initiator_agent' => $initiatorAgent,
            'from_wallet' => $fromWallet,
            'to_wallet' => $toWallet,
            'transfer_relationship' => $this->getTransferRelationship($fromAgent, $toAgent),
            'validation_metadata' => [
                'transfer_type' => $command->transferType,
                'hierarchy_validated' => true,
                'currency_compatible' => $fromWallet->getCurrency() === $toWallet->getCurrency(),
                'wallet_types_compatible' => $fromWallet->getWalletType()->canTransferTo($toWallet->getWalletType()),
            ],
        ];
    }

    /**
     * Get recommended transfer types for agent relationship
     */
    public function getRecommendedTransferTypes(Agent $fromAgent, Agent $toAgent): array
    {
        $types = [];

        // Upline transfers - usually commission or profit sharing
        if ($fromAgent->uplineId() === $toAgent->id()) {
            $types[] = 'commission';
            $types[] = 'manual';
        }

        // Downline transfers - usually funding or bonuses
        if ($fromAgent->canManage($toAgent)) {
            $types[] = 'bonus';
            $types[] = 'manual';
        }

        // Peer transfers - usually manual
        if ($fromAgent->uplineId() === $toAgent->uplineId() && $fromAgent->uplineId() !== null) {
            $types[] = 'manual';
        }

        return $types;
    }

    /**
     * Validate transfer permissions based on agent hierarchy
     */
    private function validateTransferPermissions(Agent $initiator, Agent $fromAgent, Agent $toAgent, TransferFundsCommand $command): void
    {
        // Rule 1: Initiator must be authorized to perform this transfer
        if (! $this->canInitiateTransfer($initiator, $fromAgent, $toAgent, $command)) {
            throw new ValidationException(
                sprintf("Agent '%s' is not authorized to transfer from '%s' to '%s'",
                    $initiator->username()->value(),
                    $fromAgent->username()->value(),
                    $toAgent->username()->value()
                )
            );
        }

        // Rule 2: Transfer type specific validations
        $this->validateTransferTypePermissions($initiator, $fromAgent, $toAgent, $command);
    }

    /**
     * Check if initiator can initiate transfer between agents
     */
    private function canInitiateTransfer(Agent $initiator, Agent $fromAgent, Agent $toAgent, TransferFundsCommand $command): bool
    {
        // System transfers are always allowed
        if ($command->isSystemTransfer()) {
            return true;
        }

        // Commission transfers: Only upline can initiate commission transfers to downlines
        if ($command->isCommissionTransfer()) {
            return $initiator->canManage($toAgent) && $fromAgent->canManage($toAgent);
        }

        // Manual transfers: Different rules based on hierarchy
        if ($command->isManualTransfer()) {
            return $this->canInitiateManualTransfer($initiator, $fromAgent, $toAgent);
        }

        // Bonus transfers: Only higher hierarchy can give bonuses
        if ($command->isBonusTransfer()) {
            return $initiator->canManage($toAgent);
        }

        return false;
    }

    /**
     * Validate manual transfer permissions
     */
    private function canInitiateManualTransfer(Agent $initiator, Agent $fromAgent, Agent $toAgent): bool
    {
        // 1. Agent can transfer their own funds
        if ($initiator->id() === $fromAgent->id()) {
            return $this->canTransferTo($fromAgent, $toAgent);
        }

        // 2. Upline can transfer funds from/to their downlines (for management purposes)
        if ($initiator->canManage($fromAgent) && $initiator->canManage($toAgent)) {
            return true;
        }

        // 3. Company level can transfer between any agents
        if ($initiator->isCompany()) {
            return true;
        }

        return false;
    }

    /**
     * Check if agent can transfer to another agent
     */
    private function canTransferTo(Agent $fromAgent, Agent $toAgent): bool
    {
        // 1. Can transfer to upline (paying commission, sharing profits)
        if ($fromAgent->uplineId() === $toAgent->id()) {
            return true;
        }

        // 2. Can transfer to direct downlines (funding, bonuses)
        if ($fromAgent->canManage($toAgent)) {
            return true;
        }

        // 3. Same level agents can transfer to each other if they have the same upline
        if ($fromAgent->uplineId() === $toAgent->uplineId() && $fromAgent->uplineId() !== null) {
            return true;
        }

        return false;
    }

    /**
     * Validate business rules for transfer
     */
    private function validateBusinessRules(Agent $fromAgent, Agent $toAgent, Wallet $fromWallet, Wallet $toWallet, TransferFundsCommand $command): void
    {
        // Rule 1: Currency compatibility
        if ($fromWallet->getCurrency() !== $toWallet->getCurrency()) {
            throw WalletException::currencyMismatch($fromWallet->getCurrency(), $toWallet->getCurrency());
        }

        // Rule 2: Wallet type compatibility
        if (! $fromWallet->getWalletType()->canTransferTo($toWallet->getWalletType())) {
            throw WalletException::transferNotAllowed($fromWallet->getWalletType()->value, $toWallet->getWalletType()->value);
        }

        // Rule 3: Transfer type specific rules
        $this->validateTransferTypeRules($fromAgent, $toAgent, $fromWallet, $toWallet, $command);

        // Rule 4: Amount limits based on agent type and relationship
        $this->validateAmountLimits($fromAgent, $toAgent, $command);
    }

    /**
     * Validate transfer type specific rules
     */
    private function validateTransferTypeRules(Agent $fromAgent, Agent $toAgent, Wallet $fromWallet, Wallet $toWallet, TransferFundsCommand $command): void
    {
        switch ($command->transferType) {
            case 'commission':
                // Commission transfers must be from commission wallet to main/commission wallet
                if ($fromWallet->getWalletType() !== WalletType::COMMISSION) {
                    throw new ValidationException('Commission transfers must originate from commission wallet');
                }
                break;

            case 'bonus':
                // Bonus transfers must go to main or bonus wallet
                if (! in_array($toWallet->getWalletType(), [WalletType::MAIN, WalletType::BONUS])) {
                    throw new ValidationException('Bonus transfers must go to main or bonus wallet');
                }
                break;

            case 'manual':
                // Manual transfers have fewer restrictions but must follow hierarchy
                if (! $this->canTransferTo($fromAgent, $toAgent)) {
                    throw new ValidationException('Manual transfer not allowed between these agents');
                }
                break;
        }
    }

    /**
     * Validate amount limits based on agent hierarchy
     */
    private function validateAmountLimits(Agent $fromAgent, Agent $toAgent, TransferFundsCommand $command): void
    {
        $amount = $command->amount->amount();

        // Higher hierarchy agents have higher limits
        $maxAmount = match ($fromAgent->agentType()->value()) {
            'company' => 1000000000, // $1B
            'super_senior' => 500000000, // $500M
            'senior' => 100000000, // $100M
            'master' => 100000000, // $50M
            'agent' => 100000000, // $10M
            'member' => 100000000, // $10M
            default => 100000000
        };

        if ($amount > $maxAmount) {
            throw new ValidationException(
                sprintf('Transfer amount %s exceeds maximum allowed %s for agent type %s',
                    $command->amount,
                    $maxAmount,
                    $fromAgent->agentType()->value()
                )
            );
        }
    }

    /**
     * Get transfer relationship description
     */
    private function getTransferRelationship(Agent $fromAgent, Agent $toAgent): string
    {
        if ($fromAgent->uplineId() === $toAgent->id()) {
            return 'upline_transfer'; // Transferring to upline
        }

        if ($fromAgent->canManage($toAgent)) {
            return 'downline_transfer'; // Transferring to downline
        }

        if ($fromAgent->uplineId() === $toAgent->uplineId() && $fromAgent->uplineId() !== null) {
            return 'peer_transfer'; // Transferring to peer (same upline)
        }

        if ($fromAgent->id() === $toAgent->id()) {
            return 'self_transfer'; // Transferring to own wallet
        }

        return 'cross_hierarchy_transfer'; // Transferring across different hierarchies
    }

    /**
     * Validate transfer type permissions
     */
    private function validateTransferTypePermissions(Agent $initiator, Agent $fromAgent, Agent $toAgent, TransferFundsCommand $command): void
    {
        // Additional permission checks based on transfer type
        switch ($command->transferType) {
            case 'commission':
                // Only agents who can manage both parties can do commission transfers
                if (! $initiator->canManage($fromAgent) || ! $initiator->canManage($toAgent)) {
                    throw new ValidationException('Insufficient permissions for commission transfer');
                }
                break;

            case 'bonus':
                // Only upline can give bonuses
                if (! $initiator->canManage($toAgent)) {
                    throw new ValidationException('Insufficient permissions for bonus transfer');
                }
                break;

            case 'manual':
                // Manual transfers need either self-authorization or management permissions
                if ($initiator->id() !== $fromAgent->id() && ! $initiator->canManage($fromAgent)) {
                    throw new ValidationException('Insufficient permissions for manual transfer');
                }
                break;
        }
    }
}
