<?php

namespace App\Infrastructure\Order\Services;

use App\Application\Order\Contracts\NumberExpansionServiceInterface;

final class NumberExpansionService implements NumberExpansionServiceInterface
{
    public function getAvailableOptions(): array
    {
        return ['none', 'X', '\\', '>', '\\|', '>|'];
    }

    public function isValidOption(string $option): bool
    {
        return in_array($option, $this->getAvailableOptions(), true);
    }

    public function getExpansionCount(string $number, string $option): int
    {
        $expanded = $this->expandNumbers($number, $option);
        return count($expanded);
    }

    public function expandNumbers(string $number, string $option): array
    {
        switch ($option) {
            case 'none':
                return [$number];

            case 'X':
                return $this->expandCross($number);

            case '\\':
                return $this->expandBackSlash($number);

            case '>':
                return $this->expandGreater($number);

            case '\\|':
                return $this->expandBackSlashPipe($number);

            case '>|':
                return $this->expandGreaterPipe($number);

            default:
                return [$number];
        }
    }

    private function expandCross(string $number): array
    {
        // Cross expansion: for 2D number like "21", returns ["21", "12"]
        if (strlen($number) === 2) {
            $reversed = strrev($number);
            return $number === $reversed ? [$number] : [$number, $reversed];
        }

        // For 3D, more complex cross expansion
        if (strlen($number) === 3) {
            $digits = str_split($number);
            $expanded = [];

            // Generate all unique permutations
            $permutations = $this->generatePermutations($digits);

            foreach ($permutations as $perm) {
                $expanded[] = implode('', $perm);
            }

            return array_unique($expanded);
        }

        return [$number];
    }

    private function expandBackSlash(string $number): array
    {
        // Back slash expansion: for number like "21", returns numbers ending with 1
        if (strlen($number) === 2) {
            $lastDigit = substr($number, -1);
            $expanded = [];

            for ($i = 0; $i <= 9; $i++) {
                $expanded[] = $i . $lastDigit;
            }

            return $expanded;
        }

        return [$number];
    }

    private function expandGreater(string $number): array
    {
        // Greater expansion: for number like "21", returns numbers starting with 2
        if (strlen($number) === 2) {
            $firstDigit = substr($number, 0, 1);
            $expanded = [];

            for ($i = 0; $i <= 9; $i++) {
                $expanded[] = $firstDigit . $i;
            }

            return $expanded;
        }

        return [$number];
    }

    private function expandBackSlashPipe(string $number): array
    {
        // Combined back slash and pipe expansion
        return array_merge(
            $this->expandBackSlash($number),
            $this->expandCross($number)
        );
    }

    private function expandGreaterPipe(string $number): array
    {
        // Combined greater and pipe expansion
        return array_merge(
            $this->expandGreater($number),
            $this->expandCross($number)
        );
    }

    private function generatePermutations(array $elements): array
    {
        if (count($elements) <= 1) {
            return [$elements];
        }

        $permutations = [];

        for ($i = 0; $i < count($elements); $i++) {
            $current = $elements[$i];
            $remaining = array_merge(
                array_slice($elements, 0, $i),
                array_slice($elements, $i + 1)
            );

            $subPermutations = $this->generatePermutations($remaining);

            foreach ($subPermutations as $subPerm) {
                $permutations[] = array_merge([$current], $subPerm);
            }
        }

        return $permutations;
    }
}
