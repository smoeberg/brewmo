<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\ValueObject\Volume;
use BrewMo\Domain\Vessel\Vessel;
use BrewMo\Domain\Vessel\VesselType;
use PHPUnit\Framework\TestCase;

class VesselTest extends TestCase
{
    public function testVesselLifecycle(): void
    {
        $vessel = new Vessel(1, 'FVT-01', 'Fermenter 1', VesselType::FERMENTER, new Volume(2000.0));

        $this->assertTrue($vessel->isClean());
        $this->assertFalse($vessel->isOccupied());

        $vessel->occupy();
        $this->assertTrue($vessel->isOccupied());

        $vessel->release();
        $this->assertFalse($vessel->isOccupied());
        $this->assertFalse($vessel->isClean()); // Must be dirty after release until CIP
    }
}
