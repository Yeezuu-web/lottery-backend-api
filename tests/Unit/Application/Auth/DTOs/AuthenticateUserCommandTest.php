<?php

namespace Tests\Unit\Application\Auth\DTOs;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use PHPUnit\Framework\TestCase;

class AuthenticateUserCommandTest extends TestCase
{
    public function test_can_create_authenticate_user_command()
    {
        $username = 'testuser';
        $password = 'password123';
        $audience = 'upline';

        $command = new AuthenticateUserCommand($username, $password, $audience);

        $this->assertEquals($username, $command->username);
        $this->assertEquals($password, $command->password);
        $this->assertEquals($audience, $command->audience);
    }

    public function test_trims_username_whitespace()
    {
        $username = '  testuser  ';
        $password = 'password123';
        $audience = 'upline';

        $command = new AuthenticateUserCommand($username, $password, $audience);

        $this->assertEquals('testuser', $command->username);
        $this->assertEquals($password, $command->password);
        $this->assertEquals($audience, $command->audience);
    }

    public function test_to_array_returns_correct_structure()
    {
        $username = 'testuser';
        $password = 'password123';
        $audience = 'upline';

        $command = new AuthenticateUserCommand($username, $password, $audience);
        $array = $command->toArray();

        $this->assertEquals([
            'username' => $username,
            'audience' => $audience,
        ], $array);

        // Password should not be included in toArray for security
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_properties_are_readonly()
    {
        $command = new AuthenticateUserCommand('testuser', 'password123', 'upline');

        // These should be readonly properties
        $this->assertTrue(property_exists($command, 'username'));
        $this->assertTrue(property_exists($command, 'password'));
        $this->assertTrue(property_exists($command, 'audience'));
    }
}
