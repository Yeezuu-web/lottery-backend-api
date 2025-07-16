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
use App\Domain\AgentSettings\Contracts\BettingLimitValidationServiceInterface;
use App\Domain\Order\Exceptions\OrderException;
use App\Shared\Exceptions\ValidationException;

beforeEach(function (): void {
    $this->cartRepository = Mockery::mock(CartRepositoryInterface::class);
    $this->agentRepository = Mockery::mock(AgentRepositoryInterface::class);
    $this->numberExpansionService = Mockery::mock(NumberExpansionServiceInterface::class);
    $this->channelWeightService = Mockery::mock(ChannelWeightServiceInterface::class);
    $this->walletService = Mockery::mock(WalletServiceInterface::class);
    $this->bettingLimitValidationService = Mockery::mock(BettingLimitValidationServiceInterface::class);

    $this->useCase = new AddToCartUseCase(
        $this->cartRepository,
        $this->agentRepository,
        $this->numberExpansionService,
        $this->channelWeightService,
        $this->walletService,
        $this->bettingLimitValidationService,
    );
});
afterEach(function (): void {
    Mockery::close();
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

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('21', '>')
        ->andReturn(['21', '22', '23', '24', '25', '26', '27', '28', '29']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A', 'B', 'C'], 'evening', '2D')
        ->andReturn(['A' => 1, 'B' => 1, 'C' => 1]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
            27000
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 27000.0]);

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

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('21', 'none')
        ->andReturn(['21']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A'], 'evening', '2D')
        ->andReturn(['A' => 1]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['21'],
            1000
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 1000.0]);

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

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('12', '\\')
        ->andReturn(['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A', 'B', 'C', 'D'], 'evening', '2D')
        ->andReturn(['A' => 1, 'B' => 1, 'C' => 1, 'D' => 1]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00'],
            26000
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 26000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['bet_details']['total_amount'])->toEqual(26000.0);
});
test('add to cart with high weight channels', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A', 'B'],
        option: '\\',
        number: '12',
        amount: 500.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('12', '\\')
        ->andReturn(['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A', 'B'], 'evening', '2D')
        ->andReturn(['A' => 2, 'B' => 3]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00'],
            32500
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 32500.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeArray();
    expect($result['bet_details']['total_amount'])->toEqual(32500.0);
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

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

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
        type: 'invalid',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

    // Act & Assert
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid type. Must be: 2D, 3D');

    $this->useCase->execute($command);
});
test('add to cart with invalid period', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'invalid',
        type: '2D',
        channels: ['A'],
        option: 'none',
        number: '21',
        amount: 1000.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

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

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($agent);

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
        ->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturn(null);

    // Act & Assert
    $this->expectException(OrderException::class);
    $this->expectExceptionMessage('Invalid agent ID: 999');

    $this->useCase->execute($command);
});
test('total amount calculation', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A'],
        option: '>',
        number: '21',
        amount: 1000.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('21', '>')
        ->andReturn(['21', '22', '23', '24', '25', '26', '27', '28', '29']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A'], 'evening', '2D')
        ->andReturn(['A' => 1]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['21', '22', '23', '24', '25', '26', '27', '28', '29'],
            9000
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 9000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result['bet_details']['total_amount'])->toEqual(9000.0);
    expect($result['bet_details']['expansion_count'])->toEqual(9);
    expect($result['bet_details']['total_weight'])->toEqual(1);
    expect($result['bet_details']['multiplier'])->toEqual(9);
});
test('complex total amount calculation', function (): void {
    // Arrange
    $command = new AddToCartCommand(
        agentId: 1,
        period: 'evening',
        type: '2D',
        channels: ['A', 'B', 'C'],
        option: '\\',
        number: '12',
        amount: 500.0
    );

    $agent = createValidAgent();

    $this->agentRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($agent);

    $this->numberExpansionService
        ->shouldReceive('expandNumbers')
        ->once()
        ->with('12', '\\')
        ->andReturn(['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00']);

    $this->channelWeightService
        ->shouldReceive('calculateWeights')
        ->once()
        ->with(['A', 'B', 'C'], 'evening', '2D')
        ->andReturn(['A' => 2, 'B' => 1, 'C' => 3]);

    $this->bettingLimitValidationService
        ->shouldReceive('validateBet')
        ->once()
        ->with(
            1,
            '2D',
            ['12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01', '00'],
            39000
        )
        ->andReturnNull();

    $this->walletService
        ->shouldReceive('hasEnoughBalance')
        ->once()
        ->andReturn(true);

    $this->cartRepository
        ->shouldReceive('addItem')
        ->once()
        ->andReturn(['id' => 1]);

    $this->cartRepository
        ->shouldReceive('getCartSummary')
        ->once()
        ->andReturn(['total' => 39000.0]);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result['bet_details']['total_amount'])->toEqual(39000.0);
    expect($result['bet_details']['expansion_count'])->toEqual(13);
    expect($result['bet_details']['total_weight'])->toEqual(6);
    expect($result['bet_details']['multiplier'])->toEqual(78);
});
function createValidAgent(): Agent
{
    return Agent::create(
        id: 1,
        username: 'AAAAAAAA000',
        agentType: 'member',
        uplineId: 1,
        name: 'Test Agent',
        email: 'test@example.com',
        status: 'active',
        isActive: true,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable
    );
}
