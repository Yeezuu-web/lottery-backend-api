<?php

declare(strict_types=1);
use App\Application\Auth\DTOs\OperationResponse;

test('can create operation response', function (): void {
    $success = true;
    $message = 'Operation successful';
    $data = ['key' => 'value'];

    $response = new OperationResponse($success, $message, $data);

    expect($response->success)->toEqual($success);
    expect($response->message)->toEqual($message);
    expect($response->data)->toEqual($data);
});
test('success static method', function (): void {
    $message = 'Operation successful';
    $data = ['key' => 'value'];

    $response = OperationResponse::success($message, $data);

    expect($response->success)->toBeTrue();
    expect($response->message)->toEqual($message);
    expect($response->data)->toEqual($data);
});
test('success static method without data', function (): void {
    $message = 'Operation successful';

    $response = OperationResponse::success($message);

    expect($response->success)->toBeTrue();
    expect($response->message)->toEqual($message);
    expect($response->data)->toEqual([]);
});
test('failure static method', function (): void {
    $message = 'Operation failed';
    $data = ['error' => 'Something went wrong'];

    $response = OperationResponse::failure($message, $data);

    expect($response->success)->toBeFalse();
    expect($response->message)->toEqual($message);
    expect($response->data)->toEqual($data);
});
test('failure static method without data', function (): void {
    $message = 'Operation failed';

    $response = OperationResponse::failure($message);

    expect($response->success)->toBeFalse();
    expect($response->message)->toEqual($message);
    expect($response->data)->toEqual([]);
});
test('to array returns correct structure', function (): void {
    $success = true;
    $message = 'Operation successful';
    $data = ['key' => 'value'];

    $response = new OperationResponse($success, $message, $data);
    $array = $response->toArray();

    expect($array)->toEqual([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ]);
});
test('properties are readonly', function (): void {
    $response = new OperationResponse(true, 'Test message', []);

    // These should be readonly properties
    expect(property_exists($response, 'success'))->toBeTrue();
    expect(property_exists($response, 'message'))->toBeTrue();
    expect(property_exists($response, 'data'))->toBeTrue();
});
