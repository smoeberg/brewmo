<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\ValueObject\Gravity;
use BrewMo\Domain\ValueObject\Temperature;
use BrewMo\Domain\ValueObject\Volume;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValueObjectTest extends TestCase
{
    public function testTemperature(): void
    {
        $temp = new Temperature(20.0);
        $this->assertEquals(20.0, $temp->celsius);
        $this->assertEquals(68.0, $temp->toFahrenheit());
    }

    public function testInvalidTemperature(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Temperature(-300.0);
    }

    public function testGravityPlatoConversion(): void
    {
        $gravity = new Gravity(1.050);
        $this->assertEquals(12.39, $gravity->toPlato());
    }

    public function testVolumeConversion(): void
    {
        $volume = new Volume(1000.0);
        $this->assertEquals(264.17, $volume->toGallons());
    }
}
