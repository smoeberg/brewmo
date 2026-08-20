<?php

namespace BrewMo\Tests\Domain;

use PHPUnit\Framework\TestCase;
use BrewMo\Domain\Recipe\FlavorProfile;
use BrewMo\Domain\Recipe\FlavorMapper;
use BrewMo\Domain\Recipe\IngredientType;

class FlavorMapperTest extends TestCase
{
    private FlavorMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new FlavorMapper();
    }

    public function testGenerateRecipeFromFlavorProfile(): void
    {
        // High fruitiness, standard alcohol, moderate bitterness
        $flavor = new FlavorProfile(
            bitterness: 6,
            sweetness: 4,
            roastiness: 2,
            fruitiness: 8,
            body: 5,
            alcoholTarget: 'STANDARD'
        );

        $result = $this->mapper->generateRecipeFromFlavor($flavor, 'TEST-001', 'Test Pale Ale', 12.0);

        $this->assertArrayHasKey('recipe', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(12.0, $result['metrics']['batch_volume_liters']);
        $this->assertGreaterThan(4.5, $result['metrics']['abv']);
        $this->assertGreaterThan(30, $result['metrics']['ibu']);
        $this->assertNotEmpty($result['ingredients']);
    }

    public function testScaleRecipeToCommercialBatch(): void
    {
        $flavor = new FlavorProfile(bitterness: 7, sweetness: 5, roastiness: 1, fruitiness: 5, body: 5);
        $pilot = $this->mapper->generateRecipeFromFlavor($flavor, 'PILOT-36', 'Pilot IPA', 12.0);

        $scaled = $this->mapper->scaleRecipe($pilot['recipe'], 1000.0);

        $this->assertEquals(1000.0, $scaled->getTargetBatchSizeLiters());
        $this->assertStringContainsString('1000L', $scaled->getTitle());
        $this->assertNotEmpty($scaled->getIngredients());
    }
}
