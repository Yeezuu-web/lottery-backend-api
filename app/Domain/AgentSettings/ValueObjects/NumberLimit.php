<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\ValueObjects;

use App\Domain\AgentSettings\Exceptions\AgentSettingsException;

final readonly class NumberLimit
{
    public function __construct(
        private string $gameType,
        private string $number,
        private int $limit
    ) {
        $this->validateGameType($gameType);
        $this->validateNumber($number);
        $this->validateLimit($limit);
    }

    public static function create(string $gameType, string $number, int $limit): self
    {
        return new self($gameType, $number, $limit);
    }

    public function getGameType(): string
    {
        return $this->gameType;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function isExceeded(int $currentUsage): bool
    {
        return $currentUsage > $this->limit;
    }

    public function remainingAmount(int $currentUsage): int
    {
        $remaining = $this->limit - $currentUsage;

        return max(0, $remaining);
    }

    public function toArray(): array
    {
        return [
            'game_type' => $this->gameType,
            'number' => $this->number,
            'limit' => $this->limit,
        ];
    }

    private function validateGameType(string $gameType): void
    {
        if (! in_array($gameType, ['2D', '3D'])) {
            throw AgentSettingsException::invalidGameType($gameType);
        }
    }

    private function validateNumber(string $number): void
    {
        if ($number === '') {
            throw AgentSettingsException::invalidNumberLimit($this->gameType, $number, $this->limit);
        }
    }

    private function validateLimit(int $limit): void
    {
        if ($limit < 0) {
            throw AgentSettingsException::invalidNumberLimit($this->gameType, $this->number, $limit);
        }
    }
}
