# Global Exception Handling Guide

## Overview

This application uses **global exception handling** through Laravel 11's `withExceptions()` method in `bootstrap/app.php`. This approach provides:

-   ✅ **Centralized** - All exception handling logic in one place
-   ✅ **Consistent** - Same error response format across entire application
-   ✅ **Maintainable** - Easy to update exception handling rules
-   ✅ **DRY** - No code duplication across controllers
-   ✅ **Laravel 11 Style** - Uses modern Laravel configuration

## How It Works

### 1. Global Exception Handler (`bootstrap/app.php`)

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\App\Shared\Exceptions\DomainException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            $message = $e->getMessage();

            // HTTP Status Code Mapping
            if (str_contains($message, 'not found')) return 404;
            if (str_contains($message, 'cannot manage')) return 403;
            if (str_contains($message, 'already exists')) return 422;
            // ... more rules
        }
    });
})
```

### 2. Clean Controller Methods

Controllers only focus on **business logic**, not exception handling:

```php
// ❌ OLD WAY - Duplicate try-catch in every method
public function index(GetAgentsRequest $request): JsonResponse
{
    try {
        $response = $this->getAgentsUseCase->execute($command);
        return $this->success($response->toArray(), 'Success');
    } catch (Exception $e) {
        return $this->handleAgentException($e); // Duplicate code!
    }
}

// ✅ NEW WAY - Clean and simple
public function index(GetAgentsRequest $request): JsonResponse
{
    $response = $this->getAgentsUseCase->execute($command);
    return $this->success($response->toArray(), 'Success');
}
```

## HTTP Status Code Mapping

Our global handler maps domain exceptions to appropriate HTTP status codes:

| Exception Pattern                                     | HTTP Status | Description           |
| ----------------------------------------------------- | ----------- | --------------------- |
| `not found`                                           | **404**     | Resource not found    |
| `cannot manage`, `cannot drill down`, `cannot create` | **403**     | Permission denied     |
| `already exists`, `is not valid`, `invalid`           | **422**     | Validation error      |
| Other business logic violations                       | **400**     | Bad request           |
| Unexpected exceptions                                 | **500**     | Internal server error |

## Exception Response Format

All API exceptions return consistent JSON format:

```json
{
    "success": false,
    "message": "Agent with ID 2 not found",
    "timestamp": "2025-07-10T22:08:13.396422Z"
}
```

## Domain Exception Structure

All domain exceptions should extend `DomainException`:

```php
namespace App\Domain\Agent\Exceptions;

use App\Shared\Exceptions\DomainException;

class AgentException extends DomainException
{
    // Domain-specific exception logic
}
```

## Best Practices

### ✅ DO

-   Throw domain exceptions from Use Cases
-   Use descriptive exception messages
-   Let the global handler determine HTTP status codes
-   Keep controllers clean and focused

### ❌ DON'T

-   Add try-catch blocks in controllers
-   Duplicate exception handling logic
-   Manually set HTTP status codes in controllers
-   Handle exceptions in multiple places

## Example Implementation

### Domain Layer (Use Case)

```php
public function execute(GetAgentsCommand $command): GetAgentsResponse
{
    $viewer = $this->agentRepository->findById($command->getViewerId());

    if (!$viewer) {
        throw new AgentException("Agent with ID {$command->getViewerId()} not found");
    }

    // Business logic...
}
```

### Controller Layer

```php
public function index(GetAgentsRequest $request): JsonResponse
{
    $command = new GetAgentsCommand(/* ... */);
    $response = $this->getAgentsUseCase->execute($command);

    return $this->success($response->toArray(), 'Agents retrieved successfully');
}
```

### Automatic Exception Handling

When the Use Case throws `AgentException("Agent with ID 2 not found")`, the global handler:

1. Catches the exception
2. Sees "not found" in the message
3. Returns 404 status with consistent JSON format
4. No controller code needed!

## Benefits

1. **Consistency**: Same error format across entire application
2. **Maintainability**: Change exception handling rules in one place
3. **Testability**: Easy to test exception scenarios
4. **Clean Code**: Controllers focus on business logic only
5. **Scalability**: Easy to add new exception types and rules

## Adding New Exception Types

To add a new exception pattern:

1. Add pattern check in `bootstrap/app.php`:

```php
if (str_contains($message, 'your_pattern')) {
    return response()->json([...], YOUR_STATUS_CODE);
}
```

2. Use the pattern in your domain exceptions:

```php
throw new YourException("Something your_pattern happened");
```

The global handler will automatically map it to the correct HTTP status code!
