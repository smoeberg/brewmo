<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\Validation\RecipeValidator;
use BrewMo\Domain\Validation\BrewSessionValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidRecipePassesValidation(): void
    {
        $this->expectNotToPerformAssertions();

        RecipeValidator::validate([
            'ref' => 'REC-001',
            'title' => 'IPA Recipe',
            'og' => 1.055,
            'fg' => 1.012,
            'ibu' => 45,
            'ebc' => 12,
            'target_batch_size' => 500
        ]);
    }

    public function testRecipeWithFgGreaterThanOgThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RecipeValidator::validate([
            'ref' => 'REC-002',
            'title' => 'Broken IPA',
            'og' => 1.010,
            'fg' => 1.050,
            'target_batch_size' => 500
        ]);
    }

    public function testRecipeWithInvalidOgRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RecipeValidator::validate([
            'ref' => 'REC-003',
            'title' => 'Extreme IPA',
            'og' => 2.500,
            'target_batch_size' => 500
        ]);
    }

    public function testBrewSessionWithNegativeVolumeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BrewSessionValidator::validate([
            'ref' => 'BATCH-001',
            'title' => 'Test Batch',
            'planned_volume_liters' => -100
        ]);
    }
}
