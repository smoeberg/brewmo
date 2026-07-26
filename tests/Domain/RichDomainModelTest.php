<?php

namespace BrewMo\Tests\Domain;

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\ValueObject\Gravity;
use BrewMo\Domain\ValueObject\Volume;
use BrewMo\Domain\Vessel\Vessel;
use BrewMo\Domain\Vessel\VesselType;
use PHPUnit\Framework\TestCase;

class RichDomainModelTest extends TestCase
{
    public function testRecipeRichDomainLogic(): void
    {
        $recipe = new Recipe(
            1,
            'REC-IPA',
            'India Pale Ale',
            Gravity::fromSpecificGravity(1.060),
            Gravity::fromSpecificGravity(1.012),
            55.0,
            14.0,
            Volume::fromLiters(1000)
        );

        // ABV = (1.060 - 1.012) * 131.25 = 6.3%
        $this->assertEquals(6.3, $recipe->calculateEstimatedABV());
        $this->assertFalse($recipe->isNonAlcoholic());
        $this->assertEquals(80.0, $recipe->calculateApparentAttenuation());
    }

    public function testVesselCapacityAndReadiness(): void
    {
        $vessel = new Vessel(
            1,
            'F-01',
            'Fermenter 01',
            VesselType::FERMENTER,
            Volume::fromLiters(1000),
            true,  // isClean
            false  // isOccupied
        );

        $this->assertTrue($vessel->isReadyForUse());
        $this->assertTrue($vessel->canHandleVolume(Volume::fromLiters(900)));
        $this->assertFalse($vessel->canHandleVolume(Volume::fromLiters(1200)));

        $vessel->occupy();
        $this->assertTrue($vessel->isOccupied());
        $this->assertFalse($vessel->isReadyForUse());

        $vessel->release();
        $this->assertFalse($vessel->isOccupied());
        $this->assertFalse($vessel->isClean()); // Marked dirty after emptying
    }
}
