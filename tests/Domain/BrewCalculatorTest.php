<?php

namespace BrewMo\Tests\Domain;

PHPUnit\Framework\TestCase;
use BrewMo\Domain\ValueObject\Gravity;
use BrewMo\Domain\ValueObject\Temperature;
use BrewMo\Domain\ValueObject\Volume;
use PHPUnit\Framework\TestCase as BaseTestCase;

class BrewCalculatorTest extends BaseTestCase
{
    public function testABVCalculationWithValidGravity(): void
    {
        $og = Gravity::fromSpecificGravity(1.050);
        $fg = Gravity::fromSpecificGravity(1.010);

        // ABV = (1.050 - 1.010) * 131.25 = 5.25%
        $abv = ($og->getSpecificGravity() - $fg->getSpecificGravity()) * 131.25;
        
        $this->assertEquals(5.25, round($abv, 2));
    }

    public function testPlatoConversion(): void
    {
        $og = Gravity::fromSpecificGravity(1.050);
        
        // 1.050 SG is approximately 12.39 °Plato
        $this->assertEquals(12.39, round($og->getPlato(), 2));
    }

    public function testTemperatureFahrenheitConversion(): void
    {
        $temp = Temperature::fromCelsius(20.0);
        
        $this->assertEquals(20.0, $temp->getCelsius());
        $this->assertEquals(68.0, $temp->getFahrenheit());
    }

    public function testVolumeGallonConversion(): void
    {
        $vol = Volume::fromLiters(100.0);
        
        $this->assertEquals(100.0, $vol->getLiters());
        $this->assertEquals(26.42, round($vol->getGallons(), 2));
    }
}
