<?php

namespace BrewMo\Infrastructure\Adapter;

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;

/**
 * Adapter to convert between Legacy BrewmoRecipe and new Recipe domain entity
 * This allows gradual migration from old code to new DDD architecture
 */
class LegacyRecipeAdapter
{
    /**
     * Convert Legacy BrewmoRecipe object to new Recipe entity
     */
    public static function toDomain(object $legacyRecipe): Recipe
    {
        // Load ingredients from legacy tables
        $ingredients = [];
        
        // In a real implementation, we would load from the database
        // For now, return empty array as ingredients need to be loaded separately
        
        return new Recipe(
            $legacyRecipe->id ?? null,
            $legacyRecipe->ref ?? '',
            $legacyRecipe->label ?? '',
            $legacyRecipe->batch_volume_l ?? 0.0,
            $legacyRecipe->og_sg ?? 1.0,
            $legacyRecipe->fg_sg ?? 1.0,
            $legacyRecipe->ibu ?? 0.0,
            $legacyRecipe->color_ebc ?? 0.0,
            $ingredients
        );
    }

    /**
     * Convert new Recipe entity to array for Legacy BrewmoRecipe
     */
    public static function toLegacyArray(Recipe $recipe): array
    {
        return [
            'id' => $recipe->getId(),
            'ref' => $recipe->getRef(),
            'label' => $recipe->getTitle(),
            'fk_product' => null, // Not set in domain
            'abv' => $recipe->getEstimatedAbv(),
            'ibu' => $recipe->getEstimatedIbu(),
            'color_ebc' => $recipe->getEstimatedEbc(),
            'og_sg' => $recipe->getOriginalGravity(),
            'fg_sg' => $recipe->getFinalGravity(),
            'batch_volume_l' => $recipe->getTargetBatchSizeLiters(),
            'description' => null // Not set in domain
        ];
    }

    /**
     * Load ingredients for a legacy recipe from database
     * This is a helper method to load ingredients when converting from legacy
     */
    public static function loadIngredientsFromLegacy(object $db, int $recipeId): array
    {
        $ingredients = [];

        // Load malts
        $sql = "SELECT fk_product, qty_kg FROM " . MAIN_DB_PREFIX . "brew_recipe_malt WHERE fk_recipe = ?";
        $res = $db->query($sql, [(int)$recipeId]);
        while ($obj = $db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->fk_product,
                IngredientType::MALT,
                (float)$obj->qty_kg,
                'kg',
                'Mash'
            );
        }

        // Load hops
        $sql = "SELECT fk_product, qty_g, use_phase FROM " . MAIN_DB_PREFIX . "brew_recipe_hop WHERE fk_recipe = ?";
        $res = $db->query($sql, [(int)$recipeId]);
        while ($obj = $db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->fk_product,
                IngredientType::HOPS,
                (float)$obj->qty_g,
                'g',
                $obj->use_phase ?? 'Boil'
            );
        }

        // Load yeast
        $sql = "SELECT fk_product, qty_g FROM " . MAIN_DB_PREFIX . "brew_recipe_yeast WHERE fk_recipe = ?";
        $res = $db->query($sql, [(int)$recipeId]);
        while ($obj = $db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->fk_product,
                IngredientType::YEAST,
                (float)$obj->qty_g,
                'g',
                null
            );
        }

        // Load extras
        $sql = "SELECT fk_product, qty_unit, note FROM " . MAIN_DB_PREFIX . "brew_recipe_extra WHERE fk_recipe = ?";
        $res = $db->query($sql, [(int)$recipeId]);
        while ($obj = $db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->fk_product,
                IngredientType::OTHER,
                (float)$obj->qty_unit,
                $obj->note ?? 'unit',
                $obj->note ?? null
            );
        }

        return $ingredients;
    }
}
