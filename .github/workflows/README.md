# GitHub Actions Workflows

This directory contains GitHub Actions workflows for automated testing and deployment.

## Workflows

### 1. Development Workflow (`development.yml`)

-   **Triggers**: Push and pull requests to `development` branch
-   **PHP Version**: 8.4
-   **Database**: SQLite (in-memory for testing)
-   **Tests**: Runs all tests with Pest in parallel mode
-   **Code Style**: Runs Laravel Pint for code formatting checks

### 2. Production Workflow (`production.yml`)

-   **Triggers**: Push and pull requests to `main`/`master` branch
-   **PHP Versions**: Matrix testing on 8.2, 8.3, 8.4
-   **Database**: SQLite (in-memory for testing)
-   **Tests**: Runs all tests with Pest in parallel mode with coverage reporting (minimum 15%)
-   **Security**: Runs composer audit for security vulnerabilities
-   **Code Style**: Runs Laravel Pint for code formatting checks
-   **Static Analysis**: Runs PHPStan (if configured)
-   **Deployment**: Placeholder for production deployment

## Local Testing

To run tests locally:

```bash
# Run all tests with Pest
./vendor/bin/pest

# Run tests in parallel (faster)
./vendor/bin/pest --parallel

# Run tests with coverage
./vendor/bin/pest --coverage

# Run tests with coverage and minimum threshold
./vendor/bin/pest --coverage --min=15

# Run specific test file
./vendor/bin/pest tests/Feature/AgentSettings/AgentSettingsTest.php

# Run specific test directory
./vendor/bin/pest tests/Unit/

# Run code style checks
./vendor/bin/pint --test

# Run code style fixes
./vendor/bin/pint

# Run security audit
composer audit
```

## Testing Framework

The project uses **Pest 3.8.2** for testing with the following features:

-   **Parallel Testing**: Tests run across multiple CPU cores for faster execution
-   **Modern Syntax**: Clean, readable test syntax with `test()` and `expect()` functions
-   **Laravel Integration**: Full Laravel application testing support
-   **Additional Plugins**:
    -   Architecture testing (`pest-plugin-arch`)
    -   Mutation testing (`pest-plugin-mutate`)

## Database Configuration

The workflows use SQLite with in-memory database for testing, which matches the `phpunit.xml` configuration:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Environment Variables

The workflows automatically set these environment variables:

-   `APP_ENV=testing`
-   `DB_CONNECTION=sqlite`
-   `DB_DATABASE=:memory:`

## Test Organization

```
tests/
├── Feature/     # Laravel feature tests (use TestCase)
├── Unit/        # Unit tests (use TestCase)
├── Helpers/     # Test helper classes
├── Pest.php     # Pest configuration
└── TestCase.php # Base test case for Laravel
```

## Requirements

-   PHP 8.2+ (8.4 recommended)
-   SQLite extension
-   Composer dependencies from `composer.json`
-   Pest 3.8.2 with parallel testing support

## Performance

-   **Parallel Testing**: 3x faster test execution
-   **Unit Tests**: ~111 tests in 1.29s (parallel)
-   **Feature Tests**: ~27 tests with Laravel integration
-   **Total**: 138 tests with 778 assertions

## Notes

-   Tests run in parallel by default for better performance
-   Coverage reporting requires xdebug extension
-   Static analysis step is optional and continues on error
-   Production deployment step is a placeholder and needs customization
