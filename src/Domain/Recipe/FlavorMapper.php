<?php

namespace BrewMo\Domain\Recipe;

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;

class FlavorMapper
{
    /**
     * Maps a FlavorProfile to a complete Recipe (e.g. 12L pilot batch for 36 bottles)
     */
    public function generateRecipeFromFlavor(
        FlavorProfile $flavor,
        string $recipeRef,
        string $title,
        float $batchVolumeLiters = 12.0
    ): array {
        // 1. Calculate Target ABV & Gravities
        $targetAbv = match ($flavor->getAlcoholTarget()) {
            'LIGHT' => 4.0,
            'STRONG' => 7.8,
            default => 5.4,
        };

        // FG depends on body (1-10) -> 1.008 to 1.020
        $fg = round(1.006 + ($flavor->getBody() * 0.0014), 3);
        // OG calculated backward from target ABV: ABV = (OG - FG) * 131.25 -> OG = FG + (ABV / 131.25)
        $og = round($fg + ($targetAbv / 131.25), 3);

        // 2. Calculate IBU (Bitterness 1-10 -> 10 to 75 IBU)
        $targetIbu = round(8.0 + ($flavor->getBitterness() * 6.5), 1);

        // 3. Calculate Color / EBC (Roastiness 1-10 -> 5 to 65 EBC)
        $targetEbc = round(4.0 + (($flavor->getRoastiness() - 1) * 6.7), 1);

        // 4. Style Classification
        $style = $this->classifyStyle($flavor, $targetAbv, $targetIbu, $targetEbc);

        // 5. Generate Ingredients for this batch size
        $ingredients = $this->generateIngredients($flavor, $batchVolumeLiters, $og, $targetIbu, $targetEbc);

        $recipe = new Recipe(
            null,
            $recipeRef,
            $title,
            $batchVolumeLiters,
            $og,
            $fg,
            $targetIbu,
            $targetEbc,
            $ingredients
        );

        return [
            'recipe' => $recipe,
            'style' => $style,
            'metrics' => [
                'batch_volume_liters' => $batchVolumeLiters,
                'estimated_bottles_33cl' => floor($batchVolumeLiters / 0.33),
                'og' => $og,
                'fg' => $fg,
                'abv' => $recipe->getEstimatedAbv(),
                'ibu' => $targetIbu,
                'ebc' => $targetEbc
            ],
            'ingredients' => array_map(function(RecipeIngredient $ing) {
                return [
                    'product_id' => $ing->getProductId(),
                    'type' => $ing->getType()->value,
                    'amount' => $ing->getAmount(),
                    'unit' => $ing->getUnit(),
                    'stage' => $ing->getAdditionStage()
                ];
            }, $ingredients)
        ];
    }

    /**
     * Scale a pilot recipe to commercial batch size (e.g. 12L -> 1000L).
     * Adjusts hop efficiency (hop utilization is ~12-15% higher in large commercial vessels).
     */
    public function scaleRecipe(Recipe $sourceRecipe, float $targetVolumeLiters): Recipe
    {
        $scaleFactor = $targetVolumeLiters / $sourceRecipe->getTargetBatchSizeLiters();
        $scaledIngredients = [];

        foreach ($sourceRecipe->getIngredients() as $ingredient) {
            $amount = $ingredient->getAmount() * $scaleFactor;

            // Non-linear hop utilization scaling for boil additions in large commercial tanks
            if ($ingredient->getType() === IngredientType::HOPS && str_contains($ingredient->getAdditionStage() ?? '', 'Boil')) {
                if ($targetVolumeLiters >= 500.0) {
                    $amount *= 0.88; // 12% reduction due to higher thermal and kettle efficiency
                }
            }

            $scaledIngredients[] = new RecipeIngredient(
                $ingredient->getProductId(),
                $ingredient->getType(),
                round($amount, 2),
                $ingredient->getUnit(),
                $ingredient->getAdditionStage()
            );
        }

        return new Recipe(
            null,
            $sourceRecipe->getRef() . '-SCALE-' . (int)$targetVolumeLiters . 'L',
            $sourceRecipe->getTitle() . ' (' . (int)$targetVolumeLiters . 'L Commercial Batch)',
            $targetVolumeLiters,
            $sourceRecipe->getOriginalGravity(),
            $sourceRecipe->getFinalGravity(),
            $sourceRecipe->getEstimatedIbu(),
            $sourceRecipe->getEstimatedEbc(),
            $scaledIngredients
        );
    }

    private function classifyStyle(FlavorProfile $f, float $abv, float $ibu, float $ebc): string
    {
        if ($ebc > 45 && $f->getRoastiness() >= 7) {
            return $abv >= 7.0 ? 'Imperial Stout' : 'Oatmeal / Dry Stout';
        }
        if ($ebc > 25) {
            return 'Amber / Brown Ale';
        }
        if ($f->getFruitiness() >= 7 && $ibu >= 40) {
            return $abv <= 4.5 ? 'Session Hazy IPA' : 'New England IPA (NEIPA)';
        }
        if ($ibu >= 45) {
            return 'West Coast IPA';
        }
        if ($f->getSweetness() >= 7 && $ebc <= 12) {
            return 'Belgian Blonde / Wheat Ale';
        }
        return 'Classic Craft Pale Ale';
    }

    private function generateIngredients(
        FlavorProfile $flavor,
        float $volume,
        float $og,
        float $ibu,
        float $ebc
    ): array {
        $ingredients = [];

        // Total Grain Bill Estimation (approx 0.22 kg per liter for 1.050 OG)
        $totalGrainKg = round(($volume * ($og - 1.0) * 4.5), 2);
        
        $roastRatio = min(0.20, ($flavor->getRoastiness() - 1) * 0.02);
        $caraRatio = min(0.25, ($flavor->getBody() + $flavor->getSweetness()) * 0.015);
        $baseRatio = max(0.55, 1.0 - ($roastRatio + $caraRatio));

        // Base Malt (Pilsner / Pale Ale)
        $ingredients[] = new RecipeIngredient(101, IngredientType::MALT, round($totalGrainKg * $baseRatio, 2), 'kg', 'Mash');

        // Specialty Cara Malt
        if ($caraRatio > 0.02) {
            $ingredients[] = new RecipeIngredient(102, IngredientType::MALT, round($totalGrainKg * $caraRatio, 2), 'kg', 'Mash');
        }

        // Dark Roasted Malt
        if ($roastRatio > 0.02) {
            $ingredients[] = new RecipeIngredient(103, IngredientType::MALT, round($totalGrainKg * $roastRatio, 2), 'kg', 'Mash');
        }

        // Bittering Hops (60 min boil)
        $bitterGrams = round(($ibu * $volume * 0.35) / 10.0, 1);
        $ingredients[] = new RecipeIngredient(201, IngredientType::HOPS, $bitterGrams, 'g', 'Boil 60m');

        // Aroma / Flavor Hops (Whirlpool / 10 min)
        if ($flavor->getFruitiness() >= 4) {
            $aromaGrams = round($flavor->getFruitiness() * $volume * 0.8, 1);
            $ingredients[] = new RecipeIngredient(202, IngredientType::HOPS, $aromaGrams, 'g', 'Whirlpool');
        }

        // Dry Hops
        if ($flavor->getFruitiness() >= 7) {
            $dryHopGrams = round($flavor->getFruitiness() * $volume * 1.2, 1);
            $ingredients[] = new RecipeIngredient(203, IngredientType::HOPS, $dryHopGrams, 'g', 'Dry Hop (Day 4)');
        }

        // Yeast
        $ingredients[] = new RecipeIngredient(301, IngredientType::YEAST, 1.0, 'pack', 'Fermentation');

        return $ingredients;
    }
}
