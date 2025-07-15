<?php

declare(strict_types=1);

namespace Database\Factories\Infrastructure\Agent\Models;

use App\Domain\Agent\ValueObjects\AgentType;
use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<EloquentAgent>
 */
final class EloquentAgentFactory extends Factory
{
    protected $model = EloquentAgent::class;

    /**
     * The current password being used by the factory.
     */
    private static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => 'A',
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'agent_type' => AgentType::COMPANY,
            'status' => 'active',
            'is_active' => true,
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
