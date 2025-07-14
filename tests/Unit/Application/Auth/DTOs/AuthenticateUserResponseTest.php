<?php

declare(strict_types=1);
use App\Application\Auth\DTOs\AuthenticateUserResponse;
use Tests\Helpers\AuthTestHelper;

test('can create authenticate user response', function (): void {
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();

    $response = new AuthenticateUserResponse(
        $agent,
        $tokenPair,
        true,
        'Authentication successful'
    );

    expect($response->agent)->toEqual($agent);
    expect($response->tokenPair)->toEqual($tokenPair);
    expect($response->success)->toBeTrue();
    expect($response->message)->toEqual('Authentication successful');
});
test('success static method', function (): void {
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();

    $response = AuthenticateUserResponse::success($agent, $tokenPair);

    expect($response->agent)->toEqual($agent);
    expect($response->tokenPair)->toEqual($tokenPair);
    expect($response->success)->toBeTrue();
    expect($response->message)->toEqual('Authentication successful');
});
test('success static method with custom message', function (): void {
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();
    $customMessage = 'Custom success message';

    $response = AuthenticateUserResponse::success($agent, $tokenPair, $customMessage);

    expect($response->message)->toEqual($customMessage);
    expect($response->success)->toBeTrue();
});
test('failure static method throws exception', function (): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Use exceptions for failure cases in use cases');

    AuthenticateUserResponse::failure();
});
test('to array returns correct structure', function (): void {
    $agent = AuthTestHelper::createTestAgent(
        123,
        'A',
        'test@example.com',
        'Test User',
        'company',
        true
    );
    $tokenPair = AuthTestHelper::createTestTokenPair();

    $response = AuthenticateUserResponse::success($agent, $tokenPair);
    $array = $response->toArray();

    expect($array)->toHaveKey('success');
    expect($array)->toHaveKey('message');
    expect($array)->toHaveKey('agent');
    expect($array)->toHaveKey('tokens');

    expect($array['success'])->toBeTrue();
    expect($array['message'])->toEqual('Authentication successful');

    // Check agent structure
    expect($array['agent']['id'])->toEqual(123);
    expect($array['agent']['username'])->toEqual('A');
    expect($array['agent']['email'])->toEqual('test@example.com');
    expect($array['agent']['name'])->toEqual('Test User');
    expect($array['agent']['agent_type'])->toEqual('company');
    expect($array['agent']['is_active'])->toBeTrue();

    // Check tokens structure
    expect($array['tokens'])->toHaveKey('access_token');
    expect($array['tokens'])->toHaveKey('refresh_token');
    expect($array['tokens'])->toHaveKey('access_expires_at');
    expect($array['tokens'])->toHaveKey('refresh_expires_at');
});
test('properties are readonly', function (): void {
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();

    $response = new AuthenticateUserResponse($agent, $tokenPair);

    // These should be readonly properties
    expect(property_exists($response, 'agent'))->toBeTrue();
    expect(property_exists($response, 'tokenPair'))->toBeTrue();
    expect(property_exists($response, 'success'))->toBeTrue();
    expect(property_exists($response, 'message'))->toBeTrue();
});
