<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Recipe\RecipeIngredient;
use BrewMo\Domain\Recipe\IngredientType;
use BrewMo\Domain\Repository\RecipeRepositoryInterface;
use RuntimeException;

/**
 * Dolibarr implementation of RecipeRepositoryInterface
 */
class DolibarrRecipeRepository implements RecipeRepositoryInterface
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Recipe
    {
        $sql = "SELECT rowid, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipes WHERE rowid = ?";

        $res = $this->db->query($sql, [(int)$id]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        
        // Load ingredients
        $ingredients = $this->loadIngredientsForRecipe((int)$obj->rowid);

        return new Recipe(
            (int)$obj->rowid,
            $obj->ref,
            $obj->label,
            (float)$obj->batch_volume_l,
            (float)$obj->og_sg,
            (float)$obj->fg_sg,
            (float)$obj->ibu,
            (float)$obj->color_ebc,
            $ingredients
        );
    }

    public function findByRef(string $ref): ?Recipe
    {
        $sql = "SELECT rowid, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipes WHERE ref = ?";

        $res = $this->db->query($sql, [$ref]);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        
        // Load ingredients
        $ingredients = $this->loadIngredientsForRecipe((int)$obj->rowid);

        return new Recipe(
            (int)$obj->rowid,
            $obj->ref,
            $obj->label,
            (float)$obj->batch_volume_l,
            (float)$obj->og_sg,
            (float)$obj->fg_sg,
            (float)$obj->ibu,
            (float)$obj->color_ebc,
            $ingredients
        );
    }

    public function findAll(): array
    {
        $sql = "SELECT rowid, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipes ORDER BY ref";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $recipes = [];
        while ($obj = $this->db->fetch_object($res)) {
            $ingredients = $this->loadIngredientsForRecipe((int)$obj->rowid);
            $recipes[] = new Recipe(
                (int)$obj->rowid,
                $obj->ref,
                $obj->label,
                (float)$obj->batch_volume_l,
                (float)$obj->og_sg,
                (float)$obj->fg_sg,
                (float)$obj->ibu,
                (float)$obj->color_ebc,
                $ingredients
            );
        }

        return $recipes;
    }

    public function save(Recipe $recipe): Recipe
    {
        if ($recipe->getId() === null) {
            // INSERT
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipes ";
            $sql .= "(entity, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description, datec)";
            $sql .= " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $entity = 1; // Default entity, should be configurable
            $params = [
                $entity,
                $recipe->getRef(),
                $recipe->getTitle(),
                $recipe->getId() ?? null, // fk_product - not set in domain
                $recipe->getEstimatedAbv(),
                $recipe->getEstimatedIbu(),
                $recipe->getEstimatedEbc(),
                $recipe->getOriginalGravity(),
                $recipe->getFinalGravity(),
                $recipe->getTargetBatchSizeLiters(),
                $recipe->getDescription() ?? ''
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error creating Recipe: " . $this->db->lasterror());
            }

            $insertedId = $this->db->last_insert_id(MAIN_DB_PREFIX . "brew_recipes");
            
            // Save ingredients
            $this->saveIngredients($insertedId, $recipe->getIngredients());

            return new Recipe(
                $insertedId,
                $recipe->getRef(),
                $recipe->getTitle(),
                $recipe->getTargetBatchSizeLiters(),
                $recipe->getOriginalGravity(),
                $recipe->getFinalGravity(),
                $recipe->getEstimatedIbu(),
                $recipe->getEstimatedEbc(),
                $recipe->getIngredients()
            );
        } else {
            // UPDATE
            $sql = "UPDATE " . MAIN_DB_PREFIX . "brew_recipes SET";
            $sql .= " ref = ?, label = ?, fk_product = ?, abv = ?, ibu = ?, color_ebc = ?, og_sg = ?, fg_sg = ?, batch_volume_l = ?, description = ?";
            $sql .= " WHERE rowid = ?";

            $params = [
                $recipe->getRef(),
                $recipe->getTitle(),
                $recipe->getId() ?? null, // fk_product
                $recipe->getEstimatedAbv(),
                $recipe->getEstimatedIbu(),
                $recipe->getEstimatedEbc(),
                $recipe->getOriginalGravity(),
                $recipe->getFinalGravity(),
                $recipe->getTargetBatchSizeLiters(),
                $recipe->getDescription() ?? '',
                $recipe->getId()
            ];

            $res = $this->db->query($sql, $params);
            if (!$res) {
                throw new RuntimeException("Database error updating Recipe: " . $this->db->lasterror());
            }

            // Update ingredients
            $this->saveIngredients($recipe->getId(), $recipe->getIngredients());

            return $recipe;
        }
    }

    public function delete(int $id): bool
    {
        // Delete ingredients first
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_malt WHERE fk_recipe = ?";
        $this->db->query($sql, [(int)$id]);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_hop WHERE fk_recipe = ?";
        $this->db->query($sql, [(int)$id]);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_yeast WHERE fk_recipe = ?";
        $this->db->query($sql, [(int)$id]);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_extra WHERE fk_recipe = ?";
        $this->db->query($sql, [(int)$id]);

        // Delete recipe
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipes WHERE rowid = ?";
        return (bool)$this->db->query($sql, [(int)$id]);
    }

    /**
     * Load ingredients for a recipe from all ingredient tables
     */
    private function loadIngredientsForRecipe(int $recipeId): array
    {
        $ingredients = [];

        // Load malts
        $sql = "SELECT fk_product as product_id, qty_kg as amount, 'kg' as unit, 'Mash' as addition_stage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipe_malt WHERE fk_recipe = ?";
        $res = $this->db->query($sql, [(int)$recipeId]);
        while ($obj = $this->db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->product_id,
                IngredientType::MALT,
                (float)$obj->amount,
                $obj->unit,
                $obj->addition_stage
            );
        }

        // Load hops
        $sql = "SELECT fk_product as product_id, qty_g as amount, 'g' as unit, use_phase as addition_stage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipe_hop WHERE fk_recipe = ?";
        $res = $this->db->query($sql, [(int)$recipeId]);
        while ($obj = $this->db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->product_id,
                IngredientType::HOPS,
                (float)$obj->amount,
                $obj->unit,
                $obj->addition_stage
            );
        }

        // Load yeast
        $sql = "SELECT fk_product as product_id, qty_g as amount, 'g' as unit, NULL as addition_stage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipe_yeast WHERE fk_recipe = ?";
        $res = $this->db->query($sql, [(int)$recipeId]);
        while ($obj = $this->db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->product_id,
                IngredientType::YEAST,
                (float)$obj->amount,
                $obj->unit,
                null
            );
        }

        // Load extras
        $sql = "SELECT fk_product as product_id, qty_unit as amount, note as unit, note as addition_stage";
        $sql .= " FROM " . MAIN_DB_PREFIX . "brew_recipe_extra WHERE fk_recipe = ?";
        $res = $this->db->query($sql, [(int)$recipeId]);
        while ($obj = $this->db->fetch_object($res)) {
            $ingredients[] = new RecipeIngredient(
                (int)$obj->product_id,
                IngredientType::OTHER,
                (float)$obj->amount,
                $obj->unit ?? 'unit',
                $obj->addition_stage ?? null
            );
        }

        return $ingredients;
    }

    /**
     * Save ingredients for a recipe
     */
    private function saveIngredients(int $recipeId, array $ingredients): void
    {
        // Delete existing ingredients
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_malt WHERE fk_recipe = ?", [(int)$recipeId]);
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_hop WHERE fk_recipe = ?", [(int)$recipeId]);
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_yeast WHERE fk_recipe = ?", [(int)$recipeId]);
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe_extra WHERE fk_recipe = ?", [(int)$recipeId]);

        // Save new ingredients
        foreach ($ingredients as $ingredient) {
            switch ($ingredient->getType()) {
                case IngredientType::MALT:
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipe_malt ";
                    $sql .= "(entity, fk_recipe, fk_product, qty_kg, note) VALUES (1, ?, ?, ?, ?)";
                    $this->db->query($sql, [
                        (int)$recipeId,
                        (int)$ingredient->getProductId(),
                        (float)$ingredient->getAmount(),
                        $ingredient->getAdditionStage() ?? ''
                    ]);
                    break;

                case IngredientType::HOPS:
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipe_hop ";
                    $sql .= "(entity, fk_recipe, fk_product, qty_g, use_phase, time_min) VALUES (1, ?, ?, ?, ?, 0)";
                    $this->db->query($sql, [
                        (int)$recipeId,
                        (int)$ingredient->getProductId(),
                        (float)$ingredient->getAmount(),
                        $ingredient->getAdditionStage() ?? 'Boil'
                    ]);
                    break;

                case IngredientType::YEAST:
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipe_yeast ";
                    $sql .= "(entity, fk_recipe, fk_product, qty_g, note) VALUES (1, ?, ?, ?, ?)";
                    $this->db->query($sql, [
                        (int)$recipeId,
                        (int)$ingredient->getProductId(),
                        (float)$ingredient->getAmount(),
                        $ingredient->getAdditionStage() ?? ''
                    ]);
                    break;

                default:
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipe_extra ";
                    $sql .= "(entity, fk_recipe, fk_product, qty_unit, note) VALUES (1, ?, ?, ?, ?)";
                    $this->db->query($sql, [
                        (int)$recipeId,
                        (int)$ingredient->getProductId(),
                        (float)$ingredient->getAmount(),
                        $ingredient->getUnit()
                    ]);
                    break;
            }
        }
    }
}
