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
use App\Domain\Order\Exceptions\CartException;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Wallet\ValueObjects\Money;

beforeEach(function (): void {
    $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
    $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
    $this->agentRepository = $this->createMock(AgentRepositoryInterface::class);
    $this->walletService = $this->createMock(WalletServiceInterface::class);

    $this->useCase = new SubmitCartUseCase(
        $this->cartRepository,
        $this->orderRepository,
        $this->agentRepository,
        $this->walletService
    );
});
test('submit cart with valid items', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );
    $agentBalance = Money::fromAmount(50000.0, 'KHR');

    $cartItems = [
        [
            'id' => 1,
            'agent_id' => 1,
            'bet_data' => new BetData('evening', '2D', ['A', 'B'], '>', '21', Money::fromAmount(1000.0, 'KHR')),
            'expanded_numbers' => ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
            'channel_weights' => ['A' => 1, 'B' => 1],
            'total_amount' => 18000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
        [
            'id' => 2,
            'agent_id' => 1,
            'bet_data' => new BetData('evening', '2D', ['C'], 'none', '15', Money::fromAmount(2000.0, 'KHR')),
            'expanded_numbers' => ['15'],
            'channel_weights' => ['C' => 1],
            'total_amount' => 2000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
    ];

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->with($agent, $this->isInstanceOf(Money::class))
        ->willReturn(true);

    $this->walletService
        ->expects($this->once())
        ->method('getBalance')
        ->with($agent)
        ->willReturn($agentBalance);

    $this->walletService
        ->expects($this->once())
        ->method('deductBalance')
        ->with($agent, $this->isInstanceOf(Money::class), $this->isType('string'));

    // Mock the save method to return actual Order instances with IDs
    $this->orderRepository
        ->expects($this->exactly(4)) // 2 orders * 2 saves each
        ->method('save')
        ->willReturnCallback(function (Order $order): Order {
            static $idCounter = 0;
            ++$idCounter;

            return new Order(
                $idCounter, // Set unique ID
                $order->agentId(),
                $order->orderNumber(),
                $order->groupId(),
                $order->betData(),
                $order->expandedNumbers(),
                $order->channelWeights(),
                $order->totalAmount(),
                $order->status(),
                $order->isPrinted(),
                $order->printedAt(),
                $order->placedAt(),
                $order->createdAt(),
                $order->updatedAt()
            );
        });

    $this->orderRepository
        ->expects($this->once())
        ->method('transaction')
        ->willReturnCallback(fn (callable $callback) => $callback());

    $this->cartRepository
        ->expects($this->once())
        ->method('clearCart')
        ->with($agent);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('group_id');
    expect($result)->toHaveKey('orders');
    expect($result['orders'])->toHaveCount(2);
    expect($result['order_count'])->toEqual(2);
    expect($result['total_amount'])->toEqual(20000.0);
    expect($result['currency'])->toEqual('KHR');
});
test('submit cart with empty cart', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn([]);

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
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );
    $agentBalance = Money::fromAmount(5000.0, 'KHR');

    // Less than cart total
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
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->with($agent, $this->isInstanceOf(Money::class))
        ->willReturn(false);

    $this->walletService
        ->expects($this->once())
        ->method('getBalance')
        ->with($agent)
        ->willReturn($agentBalance);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Insufficient balance');

    $this->useCase->execute($command);
});
test('submit cart with agent not found', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 999);

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

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
        1, // upline ID
        'Test Agent',
        'test@example.com',
        false, // Set to inactive
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
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Agent 1 is not allowed to place bets');

    $this->useCase->execute($command);
});
test('submit cart generates unique group id', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );
    $agentBalance = Money::fromAmount(50000.0, 'KHR');

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
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->with($agent, $this->isInstanceOf(Money::class))
        ->willReturn(true);

    $this->walletService
        ->expects($this->once())
        ->method('getBalance')
        ->with($agent)
        ->willReturn($agentBalance);

    $this->walletService
        ->expects($this->once())
        ->method('deductBalance')
        ->with($agent, $this->isInstanceOf(Money::class), $this->isType('string'));

    // Mock the save method to return actual Order instances with IDs
    $this->orderRepository
        ->expects($this->exactly(2))
        ->method('save')
        ->willReturnCallback(fn (Order $order): Order => new Order(
            1, // Set ID
            $order->agentId(),
            $order->orderNumber(),
            $order->groupId(),
            $order->betData(),
            $order->expandedNumbers(),
            $order->channelWeights(),
            $order->totalAmount(),
            $order->status(),
            $order->isPrinted(),
            $order->printedAt(),
            $order->placedAt(),
            $order->createdAt(),
            $order->updatedAt()
        ));

    $this->orderRepository
        ->expects($this->once())
        ->method('transaction')
        ->willReturnCallback(fn (callable $callback) => $callback());

    $this->cartRepository
        ->expects($this->once())
        ->method('clearCart')
        ->with($agent);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('group_id');
    expect($result['group_id'])->not->toBeEmpty();
    expect($result)->toHaveKey('orders');
    expect($result['orders'])->toHaveCount(1);
    expect($result['order_count'])->toEqual(1);
});
test('submit cart orders have correct status', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );
    $agentBalance = Money::fromAmount(50000.0, 'KHR');

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
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->with($agent, $this->isInstanceOf(Money::class))
        ->willReturn(true);

    $this->walletService
        ->expects($this->once())
        ->method('getBalance')
        ->with($agent)
        ->willReturn($agentBalance);

    $this->walletService
        ->expects($this->once())
        ->method('deductBalance')
        ->with($agent, $this->isInstanceOf(Money::class), $this->isType('string'));

    // Mock the save method to return actual Order instances with IDs
    $this->orderRepository
        ->expects($this->exactly(2))
        ->method('save')
        ->willReturnCallback(fn (Order $order): Order => new Order(
            1, // Set ID
            $order->agentId(),
            $order->orderNumber(),
            $order->groupId(),
            $order->betData(),
            $order->expandedNumbers(),
            $order->channelWeights(),
            $order->totalAmount(),
            $order->status(),
            $order->isPrinted(),
            $order->printedAt(),
            $order->placedAt(),
            $order->createdAt(),
            $order->updatedAt()
        ));

    $this->orderRepository
        ->expects($this->once())
        ->method('transaction')
        ->willReturnCallback(fn (callable $callback) => $callback());

    $this->cartRepository
        ->expects($this->once())
        ->method('clearCart')
        ->with($agent);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('orders');
    expect($result['orders'])->toHaveCount(1);
    expect($result['orders'][0]->status())->toEqual('accepted');
});
test('submit cart with mixed bet types', function (): void {
    // Arrange
    $command = new SubmitCartCommand(agentId: 1);
    $agent = new Agent(
        1,
        new Username('AAAAAAAA000'),
        AgentType::member(),
        1, // upline ID
        'Test Agent',
        'test@example.com',
        true,
        new DateTimeImmutable,
        new DateTimeImmutable
    );
    $agentBalance = Money::fromAmount(50000.0, 'KHR');

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
        [
            'id' => 2,
            'agent_id' => 1,
            'bet_data' => new BetData('night', '3D', ['B'], 'none', '123', Money::fromAmount(2000.0, 'KHR')),
            'expanded_numbers' => ['123'],
            'channel_weights' => ['B' => 1],
            'total_amount' => 2000.0,
            'currency' => 'KHR',
            'status' => 'active',
            'created_at' => new DateTimeImmutable,
            'updated_at' => new DateTimeImmutable,
        ],
    ];

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->cartRepository
        ->expects($this->once())
        ->method('getItems')
        ->with($agent)
        ->willReturn($cartItems);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->with($agent, $this->isInstanceOf(Money::class))
        ->willReturn(true);

    $this->walletService
        ->expects($this->once())
        ->method('getBalance')
        ->with($agent)
        ->willReturn($agentBalance);

    $this->walletService
        ->expects($this->once())
        ->method('deductBalance')
        ->with($agent, $this->isInstanceOf(Money::class), $this->isType('string'));

    // Mock the save method to return actual Order instances with IDs
    $this->orderRepository
        ->expects($this->exactly(4)) // 2 orders * 2 saves each
        ->method('save')
        ->willReturnCallback(function (Order $order): Order {
            static $idCounter = 0;
            ++$idCounter;

            return new Order(
                $idCounter, // Set unique ID
                $order->agentId(),
                $order->orderNumber(),
                $order->groupId(),
                $order->betData(),
                $order->expandedNumbers(),
                $order->channelWeights(),
                $order->totalAmount(),
                $order->status(),
                $order->isPrinted(),
                $order->printedAt(),
                $order->placedAt(),
                $order->createdAt(),
                $order->updatedAt()
            );
        });

    $this->orderRepository
        ->expects($this->once())
        ->method('transaction')
        ->willReturnCallback(fn (callable $callback) => $callback());

    $this->cartRepository
        ->expects($this->once())
        ->method('clearCart')
        ->with($agent);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('orders');
    expect($result['orders'])->toHaveCount(2);
    expect($result['order_count'])->toEqual(2);
    expect($result['total_amount'])->toEqual(3000.0);
    expect($result['currency'])->toEqual('KHR');
});
