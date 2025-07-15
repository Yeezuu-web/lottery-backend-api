<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Infrastructure\Agent\Models\EloquentAgent;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('successful upline login', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $response = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]]);
});
test('upline login with invalid credentials', function (): void {
    $response = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Invalid username or password']);
});
test('upline login with validation errors', function (): void {
    // Act
    $response = $this->postJson('/api/v1/auth/upline/login', [
        'username' => '',
        'password' => '',
    ]);

    // Assert
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['username', 'password']);
});
test('successful upline token refresh', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $response = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'password',
    ]);

    $tokens = $response->json()['data']['tokens'];

    $response = $this->postJson('/api/v1/auth/upline/refresh', [
        'refresh_token' => $tokens['refresh_token'],
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]]);
});
test('upline token refresh with invalid token', function (): void {
    $response = $this->postJson('/api/v1/auth/upline/refresh', [
        'refresh_token' => 'invalid_token',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Invalid refresh token']);
});
test('upline token refresh without token', function (): void {
    $response = $this->postJson('/api/v1/auth/upline/refresh', []);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Validation failed',
    ]);
    $response->assertJsonValidationErrors(['refresh_token']);
});
test('successful upline logout', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'password',
    ]);

    $tokens = $loginResponse->json()['data']['tokens'];

    // Add bearer token to the request
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $tokens['access_token'],
    ]);

    $response = $this->postJson('/api/v1/auth/upline/logout', [
        'refresh_token' => $tokens['refresh_token'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Logged out successfully']);
});
test('upline logout handles exceptions', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'password',
    ]);

    $tokens = $loginResponse->json()['data']['tokens'];

    $response = $this->postJson('/api/v1/auth/upline/logout');

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Authentication required']);
    $response->assertJson(['error' => 'No token provided']);
});
test('successful upline profile retrieval', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/upline/login', [
        'username' => 'A',
        'password' => 'password',
    ]);

    $tokens = $loginResponse->json()['data']['tokens'];

    $response = $this->getJson('/api/v1/auth/upline/profile', [
        'Authorization' => 'Bearer ' . $tokens['access_token'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Profile retrieved successfully']);
});
test('upline profile without authentication', function (): void {
    // Act (without middleware, this should be handled at the route level)
    $response = $this->getJson('/api/v1/auth/upline/profile');

    // Assert - depends on middleware implementation
    // This might be 401 or handle differently based on middleware
    expect(in_array($response->status(), [401, 403, 422]))->toBeTrue();
});
test('upline profile handles exceptions', function (): void {
    $user = EloquentAgent::factory()->create([
        'username' => 'A',
        'password' => Hash::make('password'), // important!
        'agent_type' => AgentType::COMPANY,
        'status' => 'active',
        'is_active' => true,
        'email' => 'A@example.com',
        'name' => 'A',
    ]);

    $response = $this->getJson('/api/v1/auth/upline/profile');

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Authentication required']);
    $response->assertJson(['error' => 'No token provided']);
});
test('routes are defined', function (): void {
    // Test that the routes exist
    assertRouteExists('POST', '/api/v1/auth/upline/login');
    assertRouteExists('POST', '/api/v1/auth/upline/refresh');
    assertRouteExists('POST', '/api/v1/auth/upline/logout');
    assertRouteExists('GET', '/api/v1/auth/upline/profile');
});
function assertRouteExists(string $method, string $uri): void
{
    $routes = app('router')->getRoutes();
    $foundRoute = false;

    foreach ($routes as $route) {
        if (in_array($method, $route->methods()) && $route->uri() === mb_ltrim($uri, '/')) {
            $foundRoute = true;
            break;
        }
    }

    expect($foundRoute)->toBeTrue(sprintf('Route %s %s does not exist', $method, $uri));
}
