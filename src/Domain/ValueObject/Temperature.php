<?php

namespace BrewMo\Domain\ValueObject;

use InvalidArgumentException;

readonly class Temperature
{
    public function __construct(
        public float $celsius
    ) {
        if ($celsius < -273.15) {
            throw new InvalidArgumentException("Temperature below absolute zero is impossible.");
        }
    }

    public function toFahrenheit(): float
    {
        return ($this->celsius * 9 / 5) + 32;
    }
}
