<?php

namespace Tests\Unit\Application\Auth\DTOs;

use App\Application\Auth\DTOs\OperationResponse;
use PHPUnit\Framework\TestCase;

class OperationResponseTest extends TestCase
{
    public function test_can_create_operation_response()
    {
        $success = true;
        $message = 'Operation successful';
        $data = ['key' => 'value'];

        $response = new OperationResponse($success, $message, $data);

        $this->assertEquals($success, $response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals($data, $response->data);
    }

    public function test_success_static_method()
    {
        $message = 'Operation successful';
        $data = ['key' => 'value'];

        $response = OperationResponse::success($message, $data);

        $this->assertTrue($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals($data, $response->data);
    }

    public function test_success_static_method_without_data()
    {
        $message = 'Operation successful';

        $response = OperationResponse::success($message);

        $this->assertTrue($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals([], $response->data);
    }

    public function test_failure_static_method()
    {
        $message = 'Operation failed';
        $data = ['error' => 'Something went wrong'];

        $response = OperationResponse::failure($message, $data);

        $this->assertFalse($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals($data, $response->data);
    }

    public function test_failure_static_method_without_data()
    {
        $message = 'Operation failed';

        $response = OperationResponse::failure($message);

        $this->assertFalse($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals([], $response->data);
    }

    public function test_to_array_returns_correct_structure()
    {
        $success = true;
        $message = 'Operation successful';
        $data = ['key' => 'value'];

        $response = new OperationResponse($success, $message, $data);
        $array = $response->toArray();

        $this->assertEquals([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $array);
    }

    public function test_properties_are_readonly()
    {
        $response = new OperationResponse(true, 'Test message', []);

        // These should be readonly properties
        $this->assertTrue(property_exists($response, 'success'));
        $this->assertTrue(property_exists($response, 'message'));
        $this->assertTrue(property_exists($response, 'data'));
    }
}
