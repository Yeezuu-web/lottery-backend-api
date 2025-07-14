<?php

declare(strict_types=1);
use App\Application\Order\Commands\AddToCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Contracts\ChannelWeightServiceInterface;
use App\Application\Order\Contracts\NumberExpansionServiceInterface;
use App\Application\Order\Contracts\WalletServiceInterface;
use App\Application\Order\UseCases\AddToCartUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Order\Exceptions\OrderException;
use App\Shared\Exceptions\ValidationException;

beforeEach(function (): void {
    $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
    $this->agentRepository = $this->createMock(AgentRepositoryInterface::class);
    $this->numberExpansionService = $this->createMock(NumberExpansionServiceInterface::class);
    $this->channelWeightService = $this->createMock(ChannelWeightServiceInterface::class);
    $this->walletService = $this->createMock(WalletServiceInterface::class);

    $this->useCase = new AddToCartUseCase(
        $this->cartRepository,
        $this->agentRepository,
        $this->numberExpansionService,
        $this->channelWeightService,
        $this->walletService
    );
});
test('add to cart with basic bet', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A', 'B', 'C'],
        option: '>',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->with('21', '>')
        ->willReturn(['21', '22', '23', '24', '25', '26', '27', '28', '29']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->with(['A', 'B', 'C'], 'evening', '2D')
        ->willReturn(['A' => 1, 'B' => 1, 'C' => 1]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 27000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result)->toHaveKey('cart_item');
    expect($result)->toHaveKey('bet_details');
    expect($result['bet_details']['total_amount'])->toEqual(27000.0);
});
test('add to cart with no expansion', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->with('21', 'none')
        ->willReturn(['21']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->with(['A'], 'evening', '2D')
        ->willReturn(['A' => 1]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 1000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['bet_details']['total_amount'])->toEqual(1000.0);
});
test('add to cart with complex expansion', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A', 'B', 'C', 'D'],
        option: '\\',
        number: '12',
        amount: 500.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->with('12', '\\')
        ->willReturn(['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->with(['A', 'B', 'C', 'D'], 'evening', '2D')
        ->willReturn(['A' => 1, 'B' => 1, 'C' => 1, 'D' => 1]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 26000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();

    // 500 * 13 numbers * 4 channels = 26,000
    expect($result['bet_details']['total_amount'])->toEqual(26000.0);
});
test('add to cart with high weight channels', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['LO', 'HO'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->with('21', 'none')
        ->willReturn(['21']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->with(['LO', 'HO'], 'evening', '2D')
        ->willReturn(['LO' => 3, 'HO' => 3]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 6000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();

    // 1000 * 1 number * 6 total weight = 6,000
    expect($result['bet_details']['total_amount'])->toEqual(6000.0);
});
test('add to cart with invalid amount', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 0.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Amount must be greater than zero');

    $this->useCase->execute($command);
});
test('add to cart with invalid type', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '4D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid type. Must be: 2D, 3D');

    $this->useCase->execute($command);
});
test('add to cart with invalid period', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'morning',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid period. Must be: evening, night');

    $this->useCase->execute($command);
});
test('add to cart with empty channels', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: [],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    // Create a valid agent
    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($agent);

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('At least one channel must be selected');

    $this->useCase->execute($command);
});
test('add to cart with agent not found', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 999,
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Invalid agent ID: 999');

    $this->useCase->execute($command);
});
test('total amount calculation', function (): void {
    // Test simple calculation: 1000 * 1 * 1 = 1000
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->willReturn(['21']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->willReturn(['A' => 1]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 1000.0]);

    $result = $this->useCase->execute($command);
    expect($result['bet_details']['total_amount'])->toEqual(1000.0);
});
test('complex total amount calculation', function (): void {
    // Test complex calculation: 1000 * 9 * 3 = 27000
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A', 'B', 'C'],
        option: '>',
        number: '21',
        amount: 1000.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->expects($this->once())
        ->method('findById')
        ->willReturn($agent);

    $this->numberExpansionService
        ->expects($this->once())
        ->method('expandNumbers')
        ->willReturn(['21', '22', '23', '24', '25', '26', '27', '28', '29']);

    $this->channelWeightService
        ->expects($this->once())
        ->method('calculateWeights')
        ->willReturn(['A' => 1, 'B' => 1, 'C' => 1]);

    $this->walletService
        ->expects($this->once())
        ->method('hasEnoughBalance')
        ->willReturn(true);

    $this->cartRepository
        ->expects($this->once())
        ->method('hasExistingItem')
        ->willReturn(false);

    $this->cartRepository
        ->expects($this->once())
        ->method('addItem')
        ->willReturn(['id' => 1]);

    $this->cartRepository
        ->expects($this->once())
        ->method('getCartSummary')
        ->willReturn(['total' => 27000.0]);

    $result = $this->useCase->execute($command);
    expect($result['bet_details']['total_amount'])->toEqual(27000.0);
});
function createValidAgent(): Agent
{
    return new Agent(
        id: 1,
        username: new Username('AAAAAAAA'),
        agentType: new AgentType('agent'),
        uplineId: 1,
        name: 'Test Agent',
        email: 'test@example.com',
        isActive: true,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable
    );
}
