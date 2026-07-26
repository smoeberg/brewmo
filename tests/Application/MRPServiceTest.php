<?php

namespace BrewMo\Tests\Application;

use BrewMo\Application\Service\MRPService;
use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;
use PHPUnit\Framework\TestCase;

class MRPServiceTest extends TestCase
{
    public function testMaterialRequirementsScaling(): void
    {
        $ingredients = [
            new RecipeIngredient(10, IngredientType::MALT, 200.0, 'kg', 'Mash'), // Pilsner Malt
            new RecipeIngredient(20, IngredientType::HOPS, 5.0, 'kg', 'Boil 60m')  // Citra Hops
        ];

        // Base recipe for 1000L
        $recipe = new Recipe(1, 'REC-001', 'House IPA', 1000.0, 1.055, 1.010, 50.0, 12.0, $ingredients);
        $mrpService = new MRPService();

        // Calculate for 2000L batch (2x scale)
        $requirements = $mrpService->calculateMaterialRequirements($recipe, 2000.0);

        $this->assertEquals(400.0, $requirements[10]['requiredAmount']); // Malt doubled to 400kg
        $this->assertEquals(10.0, $requirements[20]['requiredAmount']);  // Hops doubled to 10kg
    }
}
