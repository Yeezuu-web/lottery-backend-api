<?php

namespace App\Application\Order\Contracts;

interface ChannelWeightServiceInterface
{
    /**
     * Calculate channel weights for the given parameters
     *
     * @param  array  $channels  Array of channel codes
     * @param  string  $period  The betting period ('evening', 'night')
     * @param  string  $type  The betting type ('2D', '3D')
     * @return array Array of channel weights keyed by channel code
     */
    public function calculateWeights(array $channels, string $period, string $type): array;

    /**
     * Get the weight for a specific channel
     *
     * @param  string  $channel  The channel code
     * @param  string  $period  The betting period
     * @param  string  $type  The betting type
     * @return int The weight for the channel
     */
    public function getChannelWeight(string $channel, string $period, string $type): int;

    /**
     * Get total weight for all channels
     *
     * @param  array  $channels  Array of channel codes
     * @param  string  $period  The betting period
     * @param  string  $type  The betting type
     * @return int Total weight
     */
    public function getTotalWeight(array $channels, string $period, string $type): int;

    /**
     * Get available channels for a period and type
     *
     * @param  string  $period  The betting period
     * @param  string  $type  The betting type
     * @return array Array of available channel codes
     */
    public function getAvailableChannels(string $period, string $type): array;

    /**
     * Validate if channels are available for the given period and type
     *
     * @param  array  $channels  Array of channel codes to validate
     * @param  string  $period  The betting period
     * @param  string  $type  The betting type
     * @return bool True if all channels are available, false otherwise
     */
    public function areChannelsAvailable(array $channels, string $period, string $type): bool;
}
