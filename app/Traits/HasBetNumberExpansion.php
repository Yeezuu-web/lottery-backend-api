<?php

declare(strict_types=1);

namespace App\Traits;

trait HasBetNumberExpansion
{
    public function expandBetNumbers(string $input, string $option): array
    {
        return match ($option) {
            '\\' => $this->expandFirstDigit($input),
            '>' => $this->expandLastDigit($input),
            '\\|' => $this->expandFirstDigitHalf($input),
            '>|' => $this->expandLastDigitHalf($input),
            'x' => $this->expandPermutations($input),
            'none' => [$input],
            default => [$input],
        };
    }

    public function extractBettingOption(string $input): string
    {
        return match (true) {
            str_ends_with($input, 'x') => 'X',
            str_ends_with($input, '\\|') => '\\|',
            str_ends_with($input, '>|') => '>|',
            str_ends_with($input, '\\') => '\\',
            str_ends_with($input, '>') => '>',
            default => 'none',
        };
    }

    public function getBaseNumber(string $input): string
    {
        foreach (['\\|', '>|', '\\', '>', 'x'] as $mod) {
            if (str_ends_with($input, $mod)) {
                return mb_substr($input, 0, -mb_strlen($mod));
            }
        }

        return $input;
    }

    private function expandFirstDigit(string $input): array
    {
        if (mb_strlen($input) === 2) {
            $second = $input[1];

            return array_map(fn ($i): string => $i.$second, range((int) $input[0], 9));
        }

        if (mb_strlen($input) === 3) {
            $first = $input[0];
            $third = $input[2];

            return array_map(fn ($i): string => $first.$i.$third, range((int) $input[1], 9));
        }

        return [$input];
    }

    private function expandLastDigit(string $input): array
    {
        $last = (int) mb_substr($input, -1);
        $prefix = mb_substr($input, 0, -1);

        return array_map(fn ($i): string => $prefix.$i, range($last, 9));
    }

    private function expandFirstDigitHalf(string $input): array
    {
        if (mb_strlen($input) === 2) {
            $second = $input[1];

            return array_map(fn ($i): string => $i.$second, range((int) $input[0], min((int) $input[0] + 5, 9)));
        }

        if (mb_strlen($input) === 3) {
            $first = $input[0];
            $third = $input[2];

            return array_map(fn ($i): string => $first.$i.$third, range((int) $input[1], min((int) $input[1] + 5, 9)));
        }

        return [$input];
    }

    private function expandLastDigitHalf(string $input): array
    {
        $last = (int) mb_substr($input, -1);
        $prefix = mb_substr($input, 0, -1);

        return array_map(fn ($i): string => $prefix.$i, range($last, min($last + 5, 9)));
    }

    private function expandPermutations(string $input): array
    {
        $digits = mb_str_split($input);
        $results = [];
        $this->generatePermutations($digits, 0, $results);

        return array_values(array_unique(array_map(fn ($a): string => implode('', $a), $results)));
    }

    private function generatePermutations(array $arr, int $start, array &$result): void
    {
        if ($start >= count($arr)) {
            $result[] = $arr;

            return;
        }

        $counter = count($arr);
        for ($i = $start; $i < $counter; ++$i) {
            [$arr[$start], $arr[$i]] = [$arr[$i], $arr[$start]];
            $this->generatePermutations($arr, $start + 1, $result);
            [$arr[$start], $arr[$i]] = [$arr[$i], $arr[$start]];
        }
    }
}
