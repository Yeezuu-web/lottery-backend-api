<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet\Services;

use App\Application\Wallet\Commands\CreateWalletCommand;
use App\Application\Wallet\Commands\CreditWalletCommand;
use App\Application\Wallet\Commands\DebitWalletCommand;
use App\Application\Wallet\Commands\TransferFundsCommand;
use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\Contracts\WalletServiceInterface;
use App\Application\Wallet\Responses\WalletOperationResponse;
use App\Application\Wallet\Services\InterAgentTransferService;
use App\Application\Wallet\UseCases\CreateWalletUseCase;
use App\Application\Wallet\UseCases\CreditWalletUseCase;
use App\Application\Wallet\UseCases\DebitWalletUseCase;
use App\Domain\Wallet\Exceptions\TransactionException;
use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Domain\Wallet\ValueObjects\WalletType;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class WalletService implements WalletServiceInterface
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private CreateWalletUseCase $createWalletUseCase,
        private CreditWalletUseCase $creditWalletUseCase,
        private InterAgentTransferService $interAgentTransferService
    ) {}

    /**
     * Initialize wallets for a new agent/user
     */
    public function initializeWalletsForOwner(int $ownerId, string $currency = 'KHR'): WalletOperationResponse
    {
        try {
            return DB::transaction(function () use ($ownerId, $currency): WalletOperationResponse {
                $wallets = [];
                $errors = [];

                // Create main wallet
                $mainWalletCommand = new CreateWalletCommand(
                    ownerId: $ownerId,
                    walletType: WalletType::MAIN,
                    currency: $currency
                );
                $mainWalletResult = $this->createWalletUseCase->execute($mainWalletCommand);

                if ($mainWalletResult->success) {
                    $wallets['main'] = $mainWalletResult->data;
                } else {
                    $errors['main'] = $mainWalletResult->errors;
                }

                // Create commission wallet
                $commissionWalletCommand = new CreateWalletCommand(
                    ownerId: $ownerId,
                    walletType: WalletType::COMMISSION,
                    currency: $currency
                );
                $commissionWalletResult = $this->createWalletUseCase->execute($commissionWalletCommand);

                if ($commissionWalletResult->success) {
                    $wallets['commission'] = $commissionWalletResult->data;
                } else {
                    $errors['commission'] = $commissionWalletResult->errors;
                }

                // Create bonus wallet
                $bonusWalletCommand = new CreateWalletCommand(
                    ownerId: $ownerId,
                    walletType: WalletType::BONUS,
                    currency: $currency
                );
                $bonusWalletResult = $this->createWalletUseCase->execute($bonusWalletCommand);

                if ($bonusWalletResult->success) {
                    $wallets['bonus'] = $bonusWalletResult->data;
                } else {
                    $errors['bonus'] = $bonusWalletResult->errors;
                }

                if ($errors !== []) {
                    throw new WalletException('Failed to initialize some wallets: '.json_encode($errors));
                }

                Log::info('Wallets initialized successfully', [
                    'owner_id' => $ownerId,
                    'currency' => $currency,
                    'wallet_count' => count($wallets),
                ]);

                return WalletOperationResponse::success(
                    message: 'Wallets initialized successfully',
                    data: $wallets
                );
            });
        } catch (Exception $exception) {
            Log::error('Failed to initialize wallets', [
                'owner_id' => $ownerId,
                'currency' => $currency,
                'error' => $exception->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to initialize wallets',
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Transfer funds between wallets
     */
    public function transferFunds(TransferFundsCommand $command): WalletOperationResponse
    {
        try {
            return DB::transaction(function () use ($command): WalletOperationResponse {
                // Get source and destination wallets
                $fromWallet = $this->walletRepository->findById($command->fromWalletId);
                if (! $fromWallet instanceof \App\Domain\Wallet\Models\Wallet) {
                    throw WalletException::notFound($command->fromWalletId);
                }

                $toWallet = $this->walletRepository->findById($command->toWalletId);
                if (! $toWallet instanceof \App\Domain\Wallet\Models\Wallet) {
                    throw WalletException::notFound($command->toWalletId);
                }

                // Enhanced validation for inter-agent transfers
                $transferMetadata = null;
                if ($command->isInterAgentTransfer()) {
                    // Validate inter-agent transfer with business rules
                    $transferMetadata = $this->interAgentTransferService->validateInterAgentTransfer($command);

                    Log::info('Inter-agent transfer validated', [
                        'from_agent' => $transferMetadata['from_agent']->username()->value(),
                        'to_agent' => $transferMetadata['to_agent']->username()->value(),
                        'initiator' => $transferMetadata['initiator_agent']->username()->value(),
                        'transfer_type' => $command->transferType,
                        'relationship' => $transferMetadata['transfer_relationship'],
                    ]);
                } else {
                    // Standard wallet-to-wallet validation (same owner)
                    if ($fromWallet->getOwnerId() !== $toWallet->getOwnerId()) {
                        throw new WalletException('Cross-agent transfers require initiator agent ID');
                    }

                    // Check if transfer is allowed between wallet types
                    if (! $fromWallet->getWalletType()->canTransferTo($toWallet->getWalletType())) {
                        throw WalletException::transferNotAllowed(
                            $fromWallet->getWalletType()->value,
                            $toWallet->getWalletType()->value
                        );
                    }

                    // Check currency compatibility
                    if ($fromWallet->getCurrency() !== $toWallet->getCurrency()) {
                        throw WalletException::currencyMismatch(
                            $fromWallet->getCurrency(),
                            $toWallet->getCurrency()
                        );
                    }
                }

                // Generate transfer reference
                $transferRef = 'TRF_'.time().'_'.$command->fromWalletId.'_'.$command->toWalletId;

                // Prepare enhanced metadata
                $baseMetadata = [
                    'transfer_type' => 'outgoing',
                    'counterpart_wallet_id' => $command->toWalletId,
                    'transfer_reference' => $transferRef,
                ];

                // Add inter-agent transfer metadata if applicable
                if ($transferMetadata !== null) {
                    $baseMetadata = array_merge($baseMetadata, [
                        'inter_agent_transfer' => true,
                        'from_agent_id' => $transferMetadata['from_agent']->id(),
                        'to_agent_id' => $transferMetadata['to_agent']->id(),
                        'initiator_agent_id' => $transferMetadata['initiator_agent']->id(),
                        'transfer_relationship' => $transferMetadata['transfer_relationship'],
                        'business_transfer_type' => $command->transferType,
                    ]);
                }

                // Debit from source wallet
                $debitCommand = new DebitWalletCommand(
                    walletId: $command->fromWalletId,
                    amount: $command->amount,
                    transactionType: TransactionType::TRANSFER_OUT,
                    reference: $transferRef.'_OUT',
                    description: $command->description.' (Transfer Out)',
                    metadata: array_merge($command->metadata ?? [], $baseMetadata),
                    orderId: $command->orderId
                );

                $debitUseCase = new DebitWalletUseCase($this->walletRepository, $this->transactionRepository);
                $debitResult = $debitUseCase->execute($debitCommand);

                if (! $debitResult->success) {
                    throw new WalletException('Failed to debit source wallet: '.$debitResult->message);
                }

                // Prepare incoming metadata
                $incomingMetadata = [
                    'transfer_type' => 'incoming',
                    'counterpart_wallet_id' => $command->fromWalletId,
                    'transfer_reference' => $transferRef,
                ];

                // Add inter-agent transfer metadata if applicable
                if ($transferMetadata !== null) {
                    $incomingMetadata = array_merge($incomingMetadata, [
                        'inter_agent_transfer' => true,
                        'from_agent_id' => $transferMetadata['from_agent']->id(),
                        'to_agent_id' => $transferMetadata['to_agent']->id(),
                        'initiator_agent_id' => $transferMetadata['initiator_agent']->id(),
                        'transfer_relationship' => $transferMetadata['transfer_relationship'],
                        'business_transfer_type' => $command->transferType,
                    ]);
                }

                // Credit to destination wallet
                $creditCommand = new CreditWalletCommand(
                    walletId: $command->toWalletId,
                    amount: $command->amount,
                    transactionType: TransactionType::TRANSFER_IN,
                    reference: $transferRef.'_IN',
                    description: $command->description.' (Transfer In)',
                    metadata: array_merge($command->metadata ?? [], $incomingMetadata),
                    orderId: $command->orderId,
                    relatedTransactionId: $debitResult->data['transaction']['id']
                );

                $creditResult = $this->creditWalletUseCase->execute($creditCommand);

                if (! $creditResult->success) {
                    throw new WalletException('Failed to credit destination wallet: '.$creditResult->message);
                }

                Log::info('Transfer completed successfully', [
                    'transfer_reference' => $transferRef,
                    'from_wallet_id' => $command->fromWalletId,
                    'to_wallet_id' => $command->toWalletId,
                    'amount' => $command->amount->toArray(),
                ]);

                return WalletOperationResponse::success(
                    message: 'Transfer completed successfully',
                    data: [
                        'transfer_reference' => $transferRef,
                        'from_wallet' => $debitResult->data['wallet'],
                        'to_wallet' => $creditResult->data['wallet'],
                        'from_transaction' => $debitResult->data['transaction'],
                        'to_transaction' => $creditResult->data['transaction'],
                    ]
                );
            });
        } catch (WalletException|TransactionException $e) {
            Log::error('Transfer failed', [
                'from_wallet_id' => $command->fromWalletId,
                'to_wallet_id' => $command->toWalletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['transfer' => $e->getMessage()]
            );
        } catch (Exception $e) {
            Log::error('Unexpected error during transfer', [
                'from_wallet_id' => $command->fromWalletId,
                'to_wallet_id' => $command->toWalletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Transfer failed due to system error',
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Get wallet summary for an owner
     */
    public function getWalletSummary(int $ownerId): WalletOperationResponse
    {
        try {
            $wallets = $this->walletRepository->findByOwnerId($ownerId);
            $balances = $this->walletRepository->getBalancesByOwner($ownerId);

            $summary = [
                'owner_id' => $ownerId,
                'wallet_count' => count($wallets),
                'balances' => $balances,
                'wallets' => array_map(fn ($wallet) => $wallet->toArray(), $wallets),
            ];

            // Calculate totals by currency
            $totalsByCurrency = [];
            foreach ($balances as $balance) {
                $currency = $balance['currency'];
                if (! isset($totalsByCurrency[$currency])) {
                    $totalsByCurrency[$currency] = [
                        'currency' => $currency,
                        'total_balance' => 0,
                        'total_locked' => 0,
                    ];
                }

                // Extract amounts from Money objects
                $balanceAmount = $balance['balance']->amount();
                $lockedAmount = $balance['locked_balance']->amount();

                $totalsByCurrency[$currency]['total_balance'] += $balanceAmount;
                $totalsByCurrency[$currency]['total_locked'] += $lockedAmount;
            }

            $summary['totals_by_currency'] = array_values($totalsByCurrency);

            return WalletOperationResponse::success(
                message: 'Wallet summary retrieved successfully',
                data: $summary
            );
        } catch (Exception $exception) {
            Log::error('Failed to get wallet summary', [
                'owner_id' => $ownerId,
                'error' => $exception->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to retrieve wallet summary',
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get or create wallet for owner and type
     */
    public function getOrCreateWallet(int $ownerId, WalletType $walletType, string $currency = 'KHR'): WalletOperationResponse
    {
        try {
            // Try to find existing wallet
            $existingWallet = $this->walletRepository->findByOwnerIdAndType($ownerId, $walletType);

            if ($existingWallet instanceof \App\Domain\Wallet\Models\Wallet) {
                return WalletOperationResponse::success(
                    message: 'Wallet found',
                    data: $existingWallet->toArray()
                );
            }

            // Create new wallet if not found
            $createCommand = new CreateWalletCommand(
                ownerId: $ownerId,
                walletType: $walletType,
                currency: $currency
            );

            return $this->createWalletUseCase->execute($createCommand);
        } catch (Exception $exception) {
            Log::error('Failed to get or create wallet', [
                'owner_id' => $ownerId,
                'wallet_type' => $walletType->value,
                'currency' => $currency,
                'error' => $exception->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to get or create wallet',
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Process commission payment
     */
    public function processCommissionPayment(
        int $fromAgentId,
        int $toAgentId,
        Money $commissionAmount,
        string $reference,
        array $metadata = []
    ): WalletOperationResponse {
        try {
            return DB::transaction(function () use ($fromAgentId, $toAgentId, $commissionAmount, $reference, $metadata): WalletOperationResponse {
                // Get or create commission wallets
                $fromWalletResult = $this->getOrCreateWallet($fromAgentId, WalletType::COMMISSION, $commissionAmount->currency());
                if (! $fromWalletResult->success) {
                    throw new WalletException('Failed to get source commission wallet: '.$fromWalletResult->message);
                }

                $toWalletResult = $this->getOrCreateWallet($toAgentId, WalletType::COMMISSION, $commissionAmount->currency());
                if (! $toWalletResult->success) {
                    throw new WalletException('Failed to get destination commission wallet: '.$toWalletResult->message);
                }

                $fromWallet = $fromWalletResult->data;
                $toWallet = $toWalletResult->data;

                // Transfer commission
                $transferCommand = new TransferFundsCommand(
                    fromWalletId: $fromWallet['id'],
                    toWalletId: $toWallet['id'],
                    amount: $commissionAmount,
                    reference: $reference,
                    description: 'Commission payment from agent '.$fromAgentId.' to agent '.$toAgentId,
                    metadata: array_merge($metadata, [
                        'payment_type' => 'commission',
                        'from_agent_id' => $fromAgentId,
                        'to_agent_id' => $toAgentId,
                    ])
                );

                return $this->transferFunds($transferCommand);
            });
        } catch (Exception $exception) {
            Log::error('Commission payment failed', [
                'from_agent_id' => $fromAgentId,
                'to_agent_id' => $toAgentId,
                'amount' => $commissionAmount->toArray(),
                'reference' => $reference,
                'error' => $exception->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Commission payment failed',
                errors: ['commission' => $exception->getMessage()]
            );
        }
    }
}
