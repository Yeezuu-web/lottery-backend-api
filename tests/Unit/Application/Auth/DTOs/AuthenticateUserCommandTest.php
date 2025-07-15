<?php

declare(strict_types=1);
use App\Application\Auth\DTOs\AuthenticateUserCommand;

test('can create authenticate user command', function (): void {
    $username = 'testuser';
    $password = 'password123';
    $audience = 'upline';

    $command = new AuthenticateUserCommand($username, $password, $audience);

    expect($command->username)->toEqual($username);
    expect($command->password)->toEqual($password);
    expect($command->audience)->toEqual($audience);
});
test('trims username whitespace', function (): void {
    $username = '  testuser  ';
    $password = 'password123';
    $audience = 'upline';

    $command = new AuthenticateUserCommand($username, $password, $audience);

    expect($command->username)->toEqual('testuser');
    expect($command->password)->toEqual($password);
    expect($command->audience)->toEqual($audience);
});
test('to array returns correct structure', function (): void {
    $username = 'testuser';
    $password = 'password123';
    $audience = 'upline';

    $command = new AuthenticateUserCommand($username, $password, $audience);
    $array = $command->toArray();

    expect($array)->toEqual([
        'username' => $username,
        'audience' => $audience,
        'has_request_context' => false,
    ]);

    // Password should not be included in toArray for security
    $this->assertArrayNotHasKey('password', $array);
});
test('properties are readonly', function (): void {
    $command = new AuthenticateUserCommand('testuser', 'password123', 'upline');

    // These should be readonly properties
    expect(property_exists($command, 'username'))->toBeTrue();
    expect(property_exists($command, 'password'))->toBeTrue();
    expect(property_exists($command, 'audience'))->toBeTrue();
});
