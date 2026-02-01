<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NumbersMatchGameType implements ValidationRule
{
    public function __construct(
        protected ?string $gameType
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->gameType) {
            $fail('Game type is required to validate numbers.');
            return;
        }

        $config = config('stl.games.' . $this->gameType);

        if (!$config) {
            $fail('Invalid game type.');
            return;
        }

        if (!is_array($value)) {
            $fail('Numbers must be an array.');
            return;
        }

        $requiredDigits = $config['digits'];

        if (count($value) !== $requiredDigits) {
            $fail("The {$this->gameType} game requires exactly {$requiredDigits} numbers.");
            return;
        }

        // Validate each number is a valid two-digit number (00-99)
        foreach ($value as $number) {
            if (!preg_match('/^\d{1,2}$/', (string) $number)) {
                $fail('Each number must be between 00 and 99.');
                return;
            }
        }
    }
}
