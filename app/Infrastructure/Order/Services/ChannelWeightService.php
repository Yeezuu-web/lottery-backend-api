<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Services;

use App\Application\Order\Contracts\ChannelWeightServiceInterface;

final class ChannelWeightService implements ChannelWeightServiceInterface
{
    public function calculateWeights(array $channels, string $period, string $type): array
    {
        $weights = [];

        foreach ($channels as $channel) {
            $weights[$channel] = $this->getChannelWeight($channel, $period, $type);
        }

        return $weights;
    }

    public function getChannelWeight(string $channel, string $period, string $type): int
    {
        // Base weight mapping for different channels
        $baseWeights = [
            'A' => 1,
            'B' => 1,
            'C' => 1,
            'D' => 1,
            'LO' => 15,
            'HO' => 1,
            'I' => 1,
            'N' => 1,
        ];

        return $baseWeights[$channel] ?? 1;
    }

    public function getAvailableChannels(string $period, string $type): array
    {
        // For now, return all available channels
        // In a real implementation, this would depend on the period and type
        return ['A', 'B', 'C', 'D', 'LO', 'HO', 'I', 'N'];
    }

    public function isChannelAvailable(string $channel, string $period, string $type): bool
    {
        $availableChannels = $this->getAvailableChannels($period, $type);

        return in_array($channel, $availableChannels, true);
    }

    public function getTotalWeight(array $channelWeights, string $period = '', string $type = ''): int
    {
        return array_sum($channelWeights);
    }

    public function validateChannels(array $channels, string $period, string $type): bool
    {
        foreach ($channels as $channel) {
            if (! $this->isChannelAvailable($channel, $period, $type)) {
                return false;
            }
        }

        return true;
    }

    public function areChannelsAvailable(array $channels, string $period, string $type): bool
    {
        foreach ($channels as $channel) {
            if (! $this->isChannelAvailable($channel, $period, $type)) {
                return false;
            }
        }

        return true;
    }
}
