<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\Yeast\YeastBatch;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class YeastBatchTest extends TestCase
{
    public function testYeastHarvestGenerationIncrement(): void
    {
        $gen1 = new YeastBatch(1, 'YEAST-IPA-G1', 501, 1, 98.0);
        $this->assertEquals(1, $gen1->getGeneration());

        $gen2 = $gen1->harvestNewGeneration('YEAST-IPA-G2', 12, 94.5);
        $this->assertEquals(2, $gen2->getGeneration());
        $this->assertEquals(12, $gen2->getHarvestedFromBrewSessionId());
        $this->assertEquals(94.5, $gen2->getViabilityPercentage());
    }

    public function testRetiredYeastCannotBeHarvested(): void
    {
        $gen1 = new YeastBatch(1, 'YEAST-IPA-G1', 501, 1, 98.0);
        $gen1->retire();

        $this->expectException(RuntimeException::class);
        $gen1->harvestNewGeneration('YEAST-IPA-G2', 12, 90.0);
    }
}
