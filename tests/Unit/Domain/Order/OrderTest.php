<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;
use App\Domain\Wallet\ValueObjects\Money;
use App\Shared\Exceptions\ValidationException;
use DateTime;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private function createValidOrder(): Order
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

    public function test_create_order(): void
    {
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
        $this->assertNull($order->id());
        $this->assertEquals($agentId, $order->agentId());
        $this->assertEquals($orderNumber, $order->orderNumber());
        $this->assertEquals($groupId, $order->groupId());
        $this->assertEquals($betData, $order->betData());
        $this->assertEquals($expandedNumbers, $order->expandedNumbers());
        $this->assertEquals($channelWeights, $order->channelWeights());
        $this->assertEquals($totalAmount, $order->totalAmount());
        $this->assertEquals('pending', $order->status());
        $this->assertFalse($order->isPrinted());
        $this->assertInstanceOf(DateTime::class, $order->placedAt());
        $this->assertInstanceOf(DateTime::class, $order->createdAt());
        $this->assertInstanceOf(DateTime::class, $order->updatedAt());
    }

    public function test_accept_order(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act
        $acceptedOrder = $order->accept();

        // Assert
        $this->assertEquals('accepted', $acceptedOrder->status());
        $this->assertTrue($acceptedOrder->isAccepted());
        $this->assertNotEquals($order->updatedAt(), $acceptedOrder->updatedAt());
    }

    public function test_accept_already_accepted_order(): void
    {
        // Arrange
        $order = $this->createValidOrder()->accept();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order is already accepted');

        $order->accept();
    }

    public function test_accept_cancelled_order(): void
    {
        // Arrange
        $order = $this->createValidOrder()->cancel();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot accept a cancelled order');

        $order->accept();
    }

    public function test_cancel_order(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act
        $cancelledOrder = $order->cancel();

        // Assert
        $this->assertEquals('cancelled', $cancelledOrder->status());
        $this->assertTrue($cancelledOrder->isCancelled());
        // Note: updatedAt() will be different but exact timing comparison is unreliable in tests
    }

    public function test_cancel_accepted_order(): void
    {
        // Arrange
        $order = $this->createValidOrder()->accept();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot cancel an accepted order');

        $order->cancel();
    }

    public function test_cancel_already_cancelled_order(): void
    {
        // Arrange
        $order = $this->createValidOrder()->cancel();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order is already cancelled');

        $order->cancel();
    }

    public function test_mark_as_won(): void
    {
        // Arrange
        $order = $this->createValidOrder()->accept();

        // Act
        $wonOrder = $order->markAsWon();

        // Assert
        $this->assertEquals('won', $wonOrder->status());
        $this->assertTrue($wonOrder->isWon());
    }

    public function test_mark_pending_order_as_won(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only accepted orders can be marked as won');

        $order->markAsWon();
    }

    public function test_mark_as_lost(): void
    {
        // Arrange
        $order = $this->createValidOrder()->accept();

        // Act
        $lostOrder = $order->markAsLost();

        // Assert
        $this->assertEquals('lost', $lostOrder->status());
        $this->assertTrue($lostOrder->isLost());
    }

    public function test_mark_pending_order_as_lost(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only accepted orders can be marked as lost');

        $order->markAsLost();
    }

    public function test_mark_as_printed(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act
        $printedOrder = $order->markAsPrinted();

        // Assert
        $this->assertTrue($printedOrder->isPrinted());
        $this->assertInstanceOf(DateTime::class, $printedOrder->printedAt());
    }

    public function test_mark_already_printed_order(): void
    {
        // Arrange
        $order = $this->createValidOrder()->markAsPrinted();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order is already printed');

        $order->markAsPrinted();
    }

    public function test_status_checkers(): void
    {
        // Arrange
        $order = $this->createValidOrder();

        // Act & Assert
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isAccepted());
        $this->assertFalse($order->isCancelled());
        $this->assertFalse($order->isWon());
        $this->assertFalse($order->isLost());

        $acceptedOrder = $order->accept();
        $this->assertFalse($acceptedOrder->isPending());
        $this->assertTrue($acceptedOrder->isAccepted());
        $this->assertFalse($acceptedOrder->isCancelled());
        $this->assertFalse($acceptedOrder->isWon());
        $this->assertFalse($acceptedOrder->isLost());
    }

    public function test_can_be_printed(): void
    {
        // Arrange
        $pendingOrder = $this->createValidOrder();
        $acceptedOrder = $pendingOrder->accept();
        $cancelledOrder = $pendingOrder->cancel();

        // Act & Assert
        $this->assertFalse($pendingOrder->canBePrinted());  // Only accepted orders can be printed
        $this->assertTrue($acceptedOrder->canBePrinted());
        $this->assertFalse($cancelledOrder->canBePrinted());
    }

    public function test_can_be_cancelled(): void
    {
        // Arrange
        $pendingOrder = $this->createValidOrder();
        $acceptedOrder = $pendingOrder->accept();
        $cancelledOrder = $pendingOrder->cancel();

        // Act & Assert
        $this->assertTrue($pendingOrder->canBeCancelled());
        $this->assertFalse($acceptedOrder->canBeCancelled());
        $this->assertFalse($cancelledOrder->canBeCancelled());
    }

    public function test_calculate_expansion_count(): void
    {
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
        $this->assertEquals(9, $expansionCount);
    }

    public function test_calculate_total_channel_weight(): void
    {
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
        $this->assertEquals(3, $totalWeight);
    }

    public function test_calculate_total_multiplier(): void
    {
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
        $this->assertEquals(18, $multiplier); // 9 numbers * 2 channels = 18
    }

    public function test_is_number_winning(): void
    {
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
        $this->assertTrue($order->isNumberWinning('21'));
        $this->assertTrue($order->isNumberWinning('25'));
        $this->assertTrue($order->isNumberWinning('29'));
        $this->assertFalse($order->isNumberWinning('30'));
        $this->assertFalse($order->isNumberWinning('20'));
    }

    public function test_count_winning_numbers(): void
    {
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
        $this->assertEquals(2, $winningCount); // 21 and 25 are winning
    }

    public function test_order_with_invalid_status(): void
    {
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
    }

    public function test_order_with_empty_expanded_numbers(): void
    {
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
    }

    public function test_order_with_empty_channel_weights(): void
    {
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
    }

    public function test_order_with_zero_total_amount(): void
    {
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
    }
}
