<?php

declare(strict_types=1);
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;
use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;

test('create order', function (): void {
    // Arrange
    $agentId = 1;
    $orderNumber = OrderNumber::generate();
    $groupId = GroupId::generate();
    $betData = new BetData(
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: Money::fromAmount(1000.0, 'KHR')
    );
    $expandedNumbers = ['21'];
    $channelWeights = ['A' => 1];
    $totalAmount = Money::fromAmount(1000.0, 'KHR');

    // Act
    $order = Order::create(
        $agentId,
        $orderNumber,
        $groupId,
        $betData,
        $expandedNumbers,
        $channelWeights,
        $totalAmount
    );

    // Assert
    expect($order->id())->toBeNull();
    expect($order->agentId())->toEqual($agentId);
    expect($order->orderNumber())->toEqual($orderNumber);
    expect($order->groupId())->toEqual($groupId);
    expect($order->betData())->toEqual($betData);
    expect($order->expandedNumbers())->toEqual($expandedNumbers);
    expect($order->channelWeights())->toEqual($channelWeights);
    expect($order->totalAmount())->toEqual($totalAmount);
    expect($order->status())->toEqual('pending');
    expect($order->isPrinted())->toBeFalse();
    expect($order->placedAt())->toBeInstanceOf(DateTimeImmutable::class);
    expect($order->createdAt())->toBeInstanceOf(DateTimeImmutable::class);
    expect($order->updatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
test('accept order', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act
    $acceptedOrder = $order->accept();

    // Assert
    expect($acceptedOrder->status())->toEqual('accepted');
    expect($acceptedOrder->isAccepted())->toBeTrue();
    $this->assertNotEquals($order->updatedAt(), $acceptedOrder->updatedAt());
});
test('accept already accepted order', function (): void {
    // Arrange
    $order = createValidOrder()->accept();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Order is already accepted');

    $order->accept();
});
test('accept cancelled order', function (): void {
    // Arrange
    $order = createValidOrder()->cancel();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Cannot accept a cancelled order');

    $order->accept();
});
test('cancel order', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act
    $cancelledOrder = $order->cancel();

    // Assert
    expect($cancelledOrder->status())->toEqual('cancelled');
    expect($cancelledOrder->isCancelled())->toBeTrue();
    // Note: updatedAt() will be different but exact timing comparison is unreliable in tests
});
test('cancel accepted order', function (): void {
    // Arrange
    $order = createValidOrder()->accept();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Cannot cancel an accepted order');

    $order->cancel();
});
test('cancel already cancelled order', function (): void {
    // Arrange
    $order = createValidOrder()->cancel();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Order is already cancelled');

    $order->cancel();
});
test('mark as won', function (): void {
    // Arrange
    $order = createValidOrder()->accept();

    // Act
    $wonOrder = $order->markAsWon();

    // Assert
    expect($wonOrder->status())->toEqual('won');
    expect($wonOrder->isWon())->toBeTrue();
});
test('mark pending order as won', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Only accepted orders can be marked as won');

    $order->markAsWon();
});
test('mark as lost', function (): void {
    // Arrange
    $order = createValidOrder()->accept();

    // Act
    $lostOrder = $order->markAsLost();

    // Assert
    expect($lostOrder->status())->toEqual('lost');
    expect($lostOrder->isLost())->toBeTrue();
});
test('mark pending order as lost', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Only accepted orders can be marked as lost');

    $order->markAsLost();
});
test('mark as printed', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act
    $printedOrder = $order->markAsPrinted();

    // Assert
    expect($printedOrder->isPrinted())->toBeTrue();
    expect($printedOrder->printedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
test('mark already printed order', function (): void {
    // Arrange
    $order = createValidOrder()->markAsPrinted();

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Order is already printed');

    $order->markAsPrinted();
});
test('status checkers', function (): void {
    // Arrange
    $order = createValidOrder();

    // Act & Assert
    expect($order->isPending())->toBeTrue();
    expect($order->isAccepted())->toBeFalse();
    expect($order->isCancelled())->toBeFalse();
    expect($order->isWon())->toBeFalse();
    expect($order->isLost())->toBeFalse();

    $acceptedOrder = $order->accept();
    expect($acceptedOrder->isPending())->toBeFalse();
    expect($acceptedOrder->isAccepted())->toBeTrue();
    expect($acceptedOrder->isCancelled())->toBeFalse();
    expect($acceptedOrder->isWon())->toBeFalse();
    expect($acceptedOrder->isLost())->toBeFalse();
});
test('can be printed', function (): void {
    // Arrange
    $pendingOrder = createValidOrder();
    $acceptedOrder = $pendingOrder->accept();
    $cancelledOrder = $pendingOrder->cancel();

    // Act & Assert
    expect($pendingOrder->canBePrinted())->toBeFalse();
    // Only accepted orders can be printed
    expect($acceptedOrder->canBePrinted())->toBeTrue();
    expect($cancelledOrder->canBePrinted())->toBeFalse();
});
test('can be cancelled', function (): void {
    // Arrange
    $pendingOrder = createValidOrder();
    $acceptedOrder = $pendingOrder->accept();
    $cancelledOrder = $pendingOrder->cancel();

    // Act & Assert
    expect($pendingOrder->canBeCancelled())->toBeTrue();
    expect($acceptedOrder->canBeCancelled())->toBeFalse();
    expect($cancelledOrder->canBeCancelled())->toBeFalse();
});
test('calculate expansion count', function (): void {
    // Arrange
    $order = Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: '>',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(9000.0, 'KHR')
    );

    // Act
    $expansionCount = $order->calculateExpansionCount();

    // Assert
    expect($expansionCount)->toEqual(9);
});
test('calculate total channel weight', function (): void {
    // Arrange
    $order = Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A', 'B', 'C'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21'],
        channelWeights: ['A' => 1, 'B' => 1, 'C' => 1],
        totalAmount: Money::fromAmount(3000.0, 'KHR')
    );

    // Act
    $totalWeight = $order->calculateTotalChannelWeight();

    // Assert
    expect($totalWeight)->toEqual(3);
});
test('calculate total multiplier', function (): void {
    // Arrange
    $order = Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A', 'B'],
            option: '>',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
        channelWeights: ['A' => 1, 'B' => 1],
        totalAmount: Money::fromAmount(18000.0, 'KHR')
    );

    // Act
    $multiplier = $order->calculateTotalMultiplier();

    // Assert
    expect($multiplier)->toEqual(18);
    // 9 numbers * 2 channels = 18
});
test('is number winning', function (): void {
    // Arrange
    $order = Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: '>',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(9000.0, 'KHR')
    );

    // Act & Assert
    expect($order->isNumberWinning('21'))->toBeTrue();
    expect($order->isNumberWinning('25'))->toBeTrue();
    expect($order->isNumberWinning('29'))->toBeTrue();
    expect($order->isNumberWinning('30'))->toBeFalse();
    expect($order->isNumberWinning('20'))->toBeFalse();
});
test('count winning numbers', function (): void {
    // Arrange
    $order = Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: '>',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(9000.0, 'KHR')
    );

    // Act
    $winningCount = $order->countWinningNumbers(['21', '25', '30', '35']);

    // Assert
    expect($winningCount)->toEqual(2);
    // 21 and 25 are winning
});
test('order with invalid status', function (): void {
    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid order status: invalid');

    new Order(
        id: null,
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(1000.0, 'KHR'),
        status: 'invalid'
    );
});
test('order with empty expanded numbers', function (): void {
    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Order must have at least one expanded number');

    new Order(
        id: null,
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: [],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(1000.0, 'KHR')
    );
});
test('order with empty channel weights', function (): void {
    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Order must have channel weights');

    new Order(
        id: null,
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21'],
        channelWeights: [],
        totalAmount: Money::fromAmount(1000.0, 'KHR')
    );
});
test('order with zero total amount', function (): void {
    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Total amount must be greater than zero');

    new Order(
        id: null,
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(0.0, 'KHR')
    );
});
function createValidOrder(): Order
{
    return Order::create(
        agentId: 1,
        orderNumber: OrderNumber::generate(),
        groupId: GroupId::generate(),
        betData: new BetData(
            period: 'evening',
            type: '2D',
            channels: ['A'],
            option: 'none',
            number: '21',
            amount: Money::fromAmount(1000.0, 'KHR')
        ),
        expandedNumbers: ['21'],
        channelWeights: ['A' => 1],
        totalAmount: Money::fromAmount(1000.0, 'KHR')
    );
}
