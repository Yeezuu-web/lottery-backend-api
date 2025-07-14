# 🏗️ Clean Architecture Guide for Lottery System

## 📋 Overview

This project implements **Clean Architecture** principles to create a maintainable, scalable, and testable lottery system. The architecture is organized into distinct layers with clear dependency rules.

## 🎯 Architecture Principles

### 1. **Dependency Rule**

- **Inner layers** know nothing about **outer layers**
- **Outer layers** depend on **inner layers**
- **Dependencies point inward**

### 2. **Layer Isolation**

- Each layer has a specific responsibility
- Business logic is isolated from infrastructure concerns
- External dependencies are abstracted through interfaces

### 3. **Testability**

- All business logic can be tested in isolation
- Dependencies are injected through interfaces
- Infrastructure can be mocked for testing

## 🏢 Layer Structure

```
app/
├── Domain/              # 🎯 Business Logic & Rules
│   ├── Agent/
│   ├── Order/
│   ├── Result/
│   ├── Settlement/
│   └── Wallet/
├── Application/         # 🔄 Use Cases & Orchestration
│   ├── Agent/
│   ├── Order/
│   ├── Result/
│   ├── Settlement/
│   └── Wallet/
├── Infrastructure/      # 🔧 External Concerns
│   ├── Agent/
│   ├── Order/
│   ├── Result/
│   ├── Settlement/
│   └── Wallet/
├── Shared/              # 🔗 Cross-cutting Concerns
│   ├── Events/
│   ├── Exceptions/
│   ├── Services/
│   └── ValueObjects/
└── Http/Controllers/    # 🌐 Presentation Layer
    ├── Agent/
    ├── Order/
    ├── Result/
    ├── Settlement/
    └── Wallet/
```

## 📚 Layer Responsibilities

### 🎯 **Domain Layer** (Innermost)

**Purpose**: Pure business logic and rules

**Contains**:

- **Models**: Core entities (Agent, Order, Transaction)
- **Contracts**: Interfaces defining what can be done
- **Value Objects**: Immutable data structures (Money, BetData)
- **Events**: Domain events for business occurrences
- **Exceptions**: Domain-specific errors

**Rules**:

- ✅ **NO external dependencies**
- ✅ **NO database or framework code**
- ✅ **Pure PHP business logic**
- ✅ **Define interfaces, don't implement them**

### 🔄 **Application Layer**

**Purpose**: Orchestrates use cases and coordinates domain services

**Contains**:

- **Use Cases**: Application-specific business flows
- **DTOs**: Data Transfer Objects for use case inputs/outputs
- **Commands**: Write operations
- **Queries**: Read operations

**Rules**:

- ✅ **Depends on Domain layer only**
- ✅ **Orchestrates domain services**
- ✅ **NO direct database access**
- ✅ **Uses domain interfaces**

### 🔧 **Infrastructure Layer** (Outermost)

**Purpose**: External concerns and implementation details

**Contains**:

- **Repositories**: Database access implementations
- **Services**: External API integrations
- **External**: Third-party service integrations

**Rules**:

- ✅ **Implements domain interfaces**
- ✅ **Contains all external dependencies**
- ✅ **Database, cache, file system access**
- ✅ **Framework-specific code**

### 🔗 **Shared Layer**

**Purpose**: Common utilities used across layers

**Contains**:

- **Events**: Cross-domain events
- **Exceptions**: Shared exception types
- **Services**: Common utilities
- **Value Objects**: Shared data structures

### 🌐 **Presentation Layer**

**Purpose**: HTTP interface and request/response handling

**Contains**:

- **Controllers**: HTTP request handlers
- **Middleware**: Request processing
- **Resources**: API response formatting

**Rules**:

- ✅ **Depends on Application layer**
- ✅ **Handles HTTP concerns only**
- ✅ **NO business logic**
- ✅ **Validates input, formats output**

## 🛠️ Implementation Guidelines

### 1. **Creating New Features**

#### Step 1: Define Domain Contracts

```php
// app/Domain/Feature/Contracts/FeatureRepositoryInterface.php
interface FeatureRepositoryInterface
{
    public function findById(int $id): ?Feature;
    public function create(array $data): Feature;
    // ... other methods
}
```

#### Step 2: Create Value Objects

```php
// app/Domain/Feature/ValueObjects/FeatureData.php
final class FeatureData
{
    public function __construct(
        private readonly string $name,
        private readonly Money $amount
    ) {}

    // ... validation and getters
}
```

#### Step 3: Build Use Cases

```php
// app/Application/Feature/UseCases/CreateFeatureUseCase.php
class CreateFeatureUseCase
{
    public function __construct(
        private readonly FeatureRepositoryInterface $repository
    ) {}

    public function execute(FeatureData $data): Feature
    {
        // Business logic orchestration
    }
}
```

#### Step 4: Implement Infrastructure

```php
// app/Infrastructure/Feature/Repositories/EloquentFeatureRepository.php
class EloquentFeatureRepository implements FeatureRepositoryInterface
{
    public function findById(int $id): ?Feature
    {
        // Database implementation
    }
}
```

#### Step 5: Create Controller

```php
// app/Http/Controllers/Feature/FeatureController.php
class FeatureController extends Controller
{
    public function __construct(
        private readonly CreateFeatureUseCase $createFeatureUseCase
    ) {}

    public function create(Request $request): JsonResponse
    {
        // Request validation and response formatting
    }
}
```

#### Step 6: Register Dependencies

```php
// app/Providers/DomainServiceProvider.php
$this->app->bind(FeatureRepositoryInterface::class, EloquentFeatureRepository::class);
```

### 2. **Dependency Injection Rules**

#### ✅ **Correct Dependencies**

```php
// Use Case depends on Domain interfaces
class PlaceBetUseCase
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
        private readonly AgentRepositoryInterface $agentRepository
    ) {}
}
```

#### ❌ **Incorrect Dependencies**

```php
// Use Case should NOT depend on infrastructure directly
class PlaceBetUseCase
{
    public function __construct(
        private readonly EloquentOrderRepository $orderRepository // ❌ Wrong!
    ) {}
}
```

### 3. **Communication Between Bounded Contexts**

#### ✅ **Correct: Through Domain Events**

```php
// After placing order, fire event
Event::dispatch(new OrderPlaced($order));

// Other contexts listen to events
class UpdateWalletListener
{
    public function handle(OrderPlaced $event): void
    {
        // Update wallet balance
    }
}
```

#### ❌ **Incorrect: Direct Service Calls**

```php
// Order service should NOT directly call Wallet service
class OrderService
{
    public function placeBet(...)
    {
        // Place bet logic
        $this->walletService->deductFunds(...); // ❌ Wrong!
    }
}
```

## 🚀 **Benefits of This Architecture**

### 1. **Maintainability**

- Clear separation of concerns
- Easy to understand and modify
- Reduced code coupling

### 2. **Testability**

- Business logic can be tested in isolation
- Infrastructure can be mocked
- Fast unit tests without database

### 3. **Flexibility**

- Easy to swap implementations
- Can change databases without affecting business logic
- New features don't break existing code

### 4. **Scalability**

- Multiple teams can work on different layers
- Independent deployment of layers
- Easy to add new bounded contexts

## 🧪 **Testing Strategy**

### Unit Tests (Domain Layer)

```php
// Test business logic in isolation
class MoneyTest extends TestCase
{
    public function test_can_add_money()
    {
        $money1 = new Money(100);
        $money2 = new Money(50);

        $result = $money1->add($money2);

        $this->assertEquals(150, $result->amount());
    }
}
```

### Integration Tests (Application Layer)

```php
// Test use cases with mocked dependencies
class PlaceBetUseCaseTest extends TestCase
{
    public function test_can_place_bet()
    {
        $agentRepository = $this->mock(AgentRepositoryInterface::class);
        $orderService = $this->mock(OrderServiceInterface::class);

        $useCase = new PlaceBetUseCase($agentRepository, $orderService);

        // Test the use case
    }
}
```

### Feature Tests (HTTP Layer)

```php
// Test the complete flow
class OrderControllerTest extends TestCase
{
    public function test_can_place_bet_via_api()
    {
        $response = $this->postJson('/api/orders', [
            'number' => '1234',
            'amount' => 1000
        ]);

        $response->assertStatus(201);
    }
}
```

## 📋 **Development Checklist**

### Before Adding New Features

- [ ] Which bounded context does this belong to?
- [ ] What domain interfaces do I need?
- [ ] What value objects should I create?
- [ ] What business rules need to be enforced?

### Code Review Checklist

- [ ] No business logic in controllers
- [ ] Proper dependency injection
- [ ] Domain interfaces defined
- [ ] Value objects used for data validation
- [ ] Events used for cross-context communication
- [ ] No direct infrastructure dependencies in use cases

## 🔧 **Common Patterns**

### Repository Pattern

```php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
}
```

### Command Query Responsibility Segregation (CQRS)

```php
// Commands (Write operations)
class CreateOrderCommand
{
    public function __construct(
        public readonly int $agentId,
        public readonly BetData $betData
    ) {}
}

// Queries (Read operations)
class GetOrderHistoryQuery
{
    public function __construct(
        public readonly int $agentId,
        public readonly array $filters
    ) {}
}
```

### Event Sourcing

```php
// Domain Events
class OrderPlaced
{
    public function __construct(
        public readonly Order $order,
        public readonly Agent $agent
    ) {}
}

// Event Handlers
class OrderPlacedHandler
{
    public function handle(OrderPlaced $event): void
    {
        // Update projections, send notifications, etc.
    }
}
```

## 📈 **Migration Strategy**

### From Current Modular System

1. **Phase 1**: Create clean architecture structure
2. **Phase 2**: Define domain interfaces
3. **Phase 3**: Implement use cases
4. **Phase 4**: Migrate one bounded context at a time
5. **Phase 5**: Update controllers to use use cases
6. **Phase 6**: Remove old modular dependencies

### Gradual Migration Tips

- Start with the most independent bounded context
- Use facade pattern to bridge old and new code
- Implement new features in clean architecture
- Gradually refactor existing features

This architecture will help you build a scalable, maintainable lottery system that can evolve with your business needs! 🎯
