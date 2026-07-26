<?php

namespace BrewMo\Domain\ValueObject;

use InvalidArgumentException;

readonly class Gravity
{
    public function __construct(
        public float $sg // Specific Gravity, e.g. 1.050
    ) {
        if ($sg < 0.900 || $sg > 1.200) {
            throw new InvalidArgumentException("Gravity must be a valid specific gravity value between 0.900 and 1.200.");
        }
    }

    public function toPlato(): float
    {
        return round((-1 * 616.868) + (1111.14 * $this->sg) - (630.272 * pow($this->sg, 2)) + (135.997 * pow($this->sg, 3)), 2);
    }
}
