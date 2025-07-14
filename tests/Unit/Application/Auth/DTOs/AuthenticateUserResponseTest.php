<?php

namespace Tests\Unit\Application\Auth\DTOs;

use App\Application\Auth\DTOs\AuthenticateUserResponse;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\AuthTestHelper;

class AuthenticateUserResponseTest extends TestCase
{
    public function test_can_create_authenticate_user_response()
    {
        $agent = AuthTestHelper::createTestAgent();
        $tokenPair = AuthTestHelper::createTestTokenPair();

        $response = new AuthenticateUserResponse(
            $agent,
            $tokenPair,
            true,
            'Authentication successful'
        );

        $this->assertEquals($agent, $response->agent);
        $this->assertEquals($tokenPair, $response->tokenPair);
        $this->assertTrue($response->success);
        $this->assertEquals('Authentication successful', $response->message);
    }

    public function test_success_static_method()
    {
        $agent = AuthTestHelper::createTestAgent();
        $tokenPair = AuthTestHelper::createTestTokenPair();

        $response = AuthenticateUserResponse::success($agent, $tokenPair);

        $this->assertEquals($agent, $response->agent);
        $this->assertEquals($tokenPair, $response->tokenPair);
        $this->assertTrue($response->success);
        $this->assertEquals('Authentication successful', $response->message);
    }

    public function test_success_static_method_with_custom_message()
    {
        $agent = AuthTestHelper::createTestAgent();
        $tokenPair = AuthTestHelper::createTestTokenPair();
        $customMessage = 'Custom success message';

        $response = AuthenticateUserResponse::success($agent, $tokenPair, $customMessage);

        $this->assertEquals($customMessage, $response->message);
        $this->assertTrue($response->success);
    }

    public function test_failure_static_method_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Use exceptions for failure cases in use cases');

        AuthenticateUserResponse::failure('Authentication failed');
    }

    public function test_to_array_returns_correct_structure()
    {
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

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('agent', $array);
        $this->assertArrayHasKey('tokens', $array);

        $this->assertTrue($array['success']);
        $this->assertEquals('Authentication successful', $array['message']);

        // Check agent structure
        $this->assertEquals(123, $array['agent']['id']);
        $this->assertEquals('A', $array['agent']['username']);
        $this->assertEquals('test@example.com', $array['agent']['email']);
        $this->assertEquals('Test User', $array['agent']['name']);
        $this->assertEquals('company', $array['agent']['agent_type']);
        $this->assertTrue($array['agent']['is_active']);

        // Check tokens structure
        $this->assertArrayHasKey('access_token', $array['tokens']);
        $this->assertArrayHasKey('refresh_token', $array['tokens']);
        $this->assertArrayHasKey('access_expires_at', $array['tokens']);
        $this->assertArrayHasKey('refresh_expires_at', $array['tokens']);
    }

    public function test_properties_are_readonly()
    {
        $agent = AuthTestHelper::createTestAgent();
        $tokenPair = AuthTestHelper::createTestTokenPair();

        $response = new AuthenticateUserResponse($agent, $tokenPair);

        // These should be readonly properties
        $this->assertTrue(property_exists($response, 'agent'));
        $this->assertTrue(property_exists($response, 'tokenPair'));
        $this->assertTrue(property_exists($response, 'success'));
        $this->assertTrue(property_exists($response, 'message'));
    }
}
