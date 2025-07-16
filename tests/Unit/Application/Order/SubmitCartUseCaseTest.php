<?php

declare(strict_types=1);
use App\Application\Order\Commands\SubmitCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Contracts\OrderRepositoryInterface;
use App\Application\Order\Contracts\WalletServiceInterface;
use App\Application\Order\UseCases\SubmitCartUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\AgentSettings\Contracts\BettingLimitValidationServiceInterface;
use App\Domain\Order\Exceptions\CartException;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Wallet\ValueObjects\Money;

beforeEach(function (): void {
    $this->cartRepository = Mockery::mock(CartRepositoryInterface::class);
    $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
    $this->agentRepository = Mockery::mock(AgentRepositoryInterface::class);
    $this->walletService = Mockery::mock(WalletServiceInterface::class);
    $this->bettingLimitValidationService = Mockery::mock(BettingLimitValidationServiceInterface::class);

    $this->useCase = new SubmitCartUseCase(
        $this->cartRepository,
        $this->orderRepository,
        $this->agentRepository,
        $this->walletService,
        $this->bettingLimitValidationService
    );
});

test('submit cart with valid items', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1,
        'Test Agent',
        'test@example.com',
        'active',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );

    $cartItems = [
        [
            'id' => 1,
            'agent_id' => 1,
            'bet_data' => new BetData('evening', '2D', ['A', 'B'], '>', '21', Money::fromAmount(1000.0, 'KHR')),
            'expanded_numbers' => ['21', '22', '23'],
            'channel_weights' => ['A' => 1, 'B' => 1],
            'total_amount' => 6000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
    ];

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->cartRepository
        ->shouldReceive('getItems')
        ->once()
        ->with($agent)
        ->andReturn($cartItems);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->with($agent, Mockery::type(Money::class))
        ->andReturn(true);

    $this->orderRepository
        ->shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (callable $callback) => $callback());

    $this->walletService
        ->shouldReceive('deductBalance')
        ->once()
        ->with($agent, Mockery::type(Money::class), Mockery::type('string'))
        ->andReturn(true);

    $this->orderRepository
        ->shouldReceive('save')
        ->times(2)
        ->andReturnUsing(fn (Order $order): Order => new Order(
            1, // id
            $order->agentId(),
            $order->orderNumber(),
            $order->groupId(),
            $order->betData(),
            $order->expandedNumbers(),
            $order->channelWeights(),
            $order->totalAmount(),
            'accepted',
            false,
            null,
            new DateTimeImmutable,
            new DateTimeImmutable,
            new DateTimeImmutable
        ));

    $this->bettingLimitValidationService
        ->shouldReceive('recordUsage')
        ->once()
        ->andReturnNull();

    $this->cartRepository
        ->shouldReceive('clearCart')
        ->once()
        ->with($agent)
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('getBalance')
        ->once()
        ->with($agent)
        ->andReturn(Money::fromAmount(4000.0, 'KHR'));

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result)->toHaveKey('success');
    expect($result['success'])->toBeTrue();
});

test('submit cart with empty cart', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1,
        'Test Agent',
        'test@example.com',
        'active',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->cartRepository
        ->shouldReceive('getItems')
        ->once()
        ->with($agent)
        ->andReturn([]);

    // Act & Assert
    $this->expectException(CartException::class);
    $this->expectExceptionMessage('Cart is empty');

    $this->useCase->execute($command);
});

test('submit cart with insufficient funds', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1,
        'Test Agent',
        'test@example.com',
        'active',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );

    $cartItems = [
        [
            'id' => 1,
            'agent_id' => 1,
            'bet_data' => new BetData('evening', '2D', ['A'], 'none', '21', Money::fromAmount(1000.0, 'KHR')),
            'expanded_numbers' => ['21'],
            'channel_weights' => ['A' => 1],
            'total_amount' => 1000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
    ];

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->cartRepository
        ->shouldReceive('getItems')
        ->once()
        ->with($agent)
        ->andReturn($cartItems);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->with($agent, Mockery::type(Money::class))
        ->andReturn(false);

    $this->walletService
        ->shouldReceive('getBalance')
        ->once()
        ->with($agent)
        ->andReturn(Money::fromAmount(500.0, 'KHR'));

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Insufficient balance');

    $this->useCase->execute($command);
});

test('submit cart with agent not found', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 999);

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturn(null);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Invalid agent');

    $this->useCase->execute($command);
});

test('submit cart with agent cannot place bets', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1,
        'Test Agent',
        'test@example.com',
        'inactive',
        false,
        new DateTimeImmutable,
        new DateTimeImmutable
    );

    $cartItems = [
        [
            'id' => 1,
            'agent_id' => 1,
            'bet_data' => new BetData('evening', '2D', ['A'], 'none', '21', Money::fromAmount(1000.0, 'KHR')),
            'expanded_numbers' => ['21'],
            'channel_weights' => ['A' => 1],
            'total_amount' => 1000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
    ];

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->cartRepository
        ->shouldReceive('getItems')
        ->once()
        ->with($agent)
        ->andReturn($cartItems);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Agent 1 is not allowed to place bets');

    $this->useCase->execute($command);
});

afterEach(function (): void {
    Mockery::close();
});
