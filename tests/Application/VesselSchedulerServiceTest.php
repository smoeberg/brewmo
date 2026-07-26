<?php

namespace BrewMo\Tests\Application;

use BrewMo\Application\Service\VesselSchedulerService;
use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\ValueObject\Volume;
use BrewMo\Domain\Vessel\Vessel;
use BrewMo\Domain\Vessel\VesselType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class VesselSchedulerServiceTest extends TestCase
{
    public function testSuccessfulVesselAssignment(): void
    {
        $session = new BrewSession(1, 'BREW-2026-001', 'Wheat Beer', 10, 1000.0);
        $vessel = new Vessel(5, 'FVT-01', 'Fermenter 1', VesselType::FERMENTER, new Volume(1200.0), true, false);

        $scheduler = new VesselSchedulerService();
        $scheduler->assignVesselToSession($session, $vessel);

        $this->assertEquals(5, $session->getVesselId());
        $this->assertTrue($vessel->isOccupied());
    }

    public function testCannotAssignDirtyVessel(): void
    {
        $session = new BrewSession(1, 'BREW-2026-001', 'Wheat Beer', 10, 1000.0);
        $dirtyVessel = new Vessel(5, 'FVT-01', 'Fermenter 1', VesselType::FERMENTER, new Volume(1200.0), false, false);

        $scheduler = new VesselSchedulerService();

        $this->expectException(RuntimeException::class);
        $scheduler->assignVesselToSession($session, $dirtyVessel);
    }
}
