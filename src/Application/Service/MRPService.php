<?php

namespace BrewMo\Application\Service;

use BrewMo\Domain\Recipe\Recipe;

/**
 * Service to calculate raw material requirements (MRP) based on planned brew sessions and batch volumes.
 */
class MRPService
{
    /**
     * Calculate total required ingredient quantities for a planned brew volume.
     * 
     * @param Recipe $recipe
     * @param float $plannedVolumeLiters
     * @return array Inverted ingredient scaling map [productId => ['type' => ..., 'requiredAmount' => ..., 'unit' => ...]]
     */
    public function calculateMaterialRequirements(Recipe $recipe, float $plannedVolumeLiters): array
    {
        $baseVolume = $recipe->getTargetBatchSizeLiters();
        if ($baseVolume <= 0) {
            $scalingFactor = 1.0;
        } else {
            $scalingFactor = $plannedVolumeLiters / $baseVolume;
        }

        $requirements = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            $productId = $ingredient->getProductId();
            $scaledAmount = $ingredient->getAmount() * $scalingFactor;

            if (!isset($requirements[$productId])) {
                $requirements[$productId] = [
                    'productId' => $productId,
                    'type' => $ingredient->getType()->value,
                    'requiredAmount' => 0.0,
                    'unit' => $ingredient->getUnit()
                ];
            }

            $requirements[$productId]['requiredAmount'] += $scaledAmount;
        }

        return $requirements;
    }
}
