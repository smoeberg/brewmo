<?php

namespace BrewMo\Domain\ValueObject;

use InvalidArgumentException;

readonly class Volume
{
    public function __construct(
        public float $liters
    ) {
        if ($liters < 0) {
            throw new InvalidArgumentException("Volume cannot be negative.");
        }
    }

    public function toGallons(): float
    {
        return round($this->liters * 0.264172, 2);
    }
}
