<?php

namespace Tests\Unit\Application\Order;

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
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddToCartUseCaseTest extends TestCase
{
    private AddToCartUseCase $useCase;

    private MockObject|CartRepositoryInterface $cartRepository;

    private MockObject|AgentRepositoryInterface $agentRepository;

    private MockObject|NumberExpansionServiceInterface $numberExpansionService;

    private MockObject|ChannelWeightServiceInterface $channelWeightService;

    private MockObject|WalletServiceInterface $walletService;

    private MockObject|Dispatcher $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->agentRepository = $this->createMock(AgentRepositoryInterface::class);
        $this->numberExpansionService = $this->createMock(NumberExpansionServiceInterface::class);
        $this->channelWeightService = $this->createMock(ChannelWeightServiceInterface::class);
        $this->walletService = $this->createMock(WalletServiceInterface::class);
        $this->eventDispatcher = $this->createMock(Dispatcher::class);

        $this->useCase = new AddToCartUseCase(
            $this->cartRepository,
            $this->agentRepository,
            $this->numberExpansionService,
            $this->channelWeightService,
            $this->walletService
        );
    }

    private function createValidAgent(): Agent
    {
        return new Agent(
            id: 1,
            username: new Username('AAAAAAAA'),
            agentType: new AgentType('agent'),
            uplineId: 1,
            name: 'Test Agent',
            email: 'test@example.com',
            isActive: true,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable
        );
    }

    public function test_add_to_cart_with_basic_bet(): void
    {
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
        $agent = $this->createValidAgent();

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
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cart_item', $result);
        $this->assertArrayHasKey('bet_details', $result);
        $this->assertEquals(27000.0, $result['bet_details']['total_amount']);
    }

    public function test_add_to_cart_with_no_expansion(): void
    {
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
        $agent = $this->createValidAgent();

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
        $this->assertIsArray($result);
        $this->assertEquals(1000.0, $result['bet_details']['total_amount']);
    }

    public function test_add_to_cart_with_complex_expansion(): void
    {
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
        $agent = $this->createValidAgent();

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
        $this->assertIsArray($result);
        // 500 * 13 numbers * 4 channels = 26,000
        $this->assertEquals(26000.0, $result['bet_details']['total_amount']);
    }

    public function test_add_to_cart_with_high_weight_channels(): void
    {
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
        $agent = $this->createValidAgent();

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
        $this->assertIsArray($result);
        // 1000 * 1 number * 6 total weight = 6,000
        $this->assertEquals(6000.0, $result['bet_details']['total_amount']);
    }

    public function test_add_to_cart_with_invalid_amount(): void
    {
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
        $agent = $this->createValidAgent();

        $this->agentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($agent);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->useCase->execute($command);
    }

    public function test_add_to_cart_with_invalid_type(): void
    {
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
        $agent = $this->createValidAgent();

        $this->agentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($agent);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid type. Must be: 2D, 3D');

        $this->useCase->execute($command);
    }

    public function test_add_to_cart_with_invalid_period(): void
    {
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
        $agent = $this->createValidAgent();

        $this->agentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($agent);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid period. Must be: evening, night');

        $this->useCase->execute($command);
    }

    public function test_add_to_cart_with_empty_channels(): void
    {
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
        $agent = $this->createValidAgent();

        $this->agentRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($agent);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('At least one channel must be selected');

        $this->useCase->execute($command);
    }

    public function test_add_to_cart_with_agent_not_found(): void
    {
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
    }

    public function test_total_amount_calculation(): void
    {
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

        $agent = $this->createValidAgent();

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
        $this->assertEquals(1000.0, $result['bet_details']['total_amount']);
    }

    public function test_complex_total_amount_calculation(): void
    {
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

        $agent = $this->createValidAgent();

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
        $this->assertEquals(27000.0, $result['bet_details']['total_amount']);
    }
}
