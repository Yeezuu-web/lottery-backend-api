<?php

namespace App\Application\Order\Contracts;

interface NumberExpansionServiceInterface
{
    /**
     * Expand a number based on the selected option
     *
     * @param  string  $number  The base number to expand
     * @param  string  $option  The expansion option ('\\', '>', '\\|', '>|', 'x', 'none')
     * @return array Array of expanded numbers
     */
    public function expandNumbers(string $number, string $option): array;

    /**
     * Get the available expansion options
     *
     * @return array Array of available options
     */
    public function getAvailableOptions(): array;

    /**
     * Validate if an option is valid for expansion
     *
     * @param  string  $option  The option to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidOption(string $option): bool;

    /**
     * Get the expected expansion count for a given number and option
     *
     * @param  string  $number  The base number
     * @param  string  $option  The expansion option
     * @return int Expected number of expanded numbers
     */
    public function getExpansionCount(string $number, string $option): int;
}
