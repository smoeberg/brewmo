<?php

namespace BrewMo\Infrastructure\Repository;

use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Domain\Repository\RecipeRepositoryInterface;
use RuntimeException;

class DolibarrRecipeRepository implements RecipeRepositoryInterface
{
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Recipe
    {
        $sql = "SELECT rowid, ref, title, target_batch_size, og, fg, ibu, ebc FROM " . MAIN_DB_PREFIX . "brew_recipe WHERE rowid = " . (int)$id;
        $res = $this->db->query($sql);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        return new Recipe(
            (int)$obj->rowid,
            $obj->ref,
            $obj->title,
            (float)$obj->target_batch_size,
            (float)$obj->og,
            (float)$obj->fg,
            (float)$obj->ibu,
            (float)$obj->ebc
        );
    }

    public function findByRef(string $ref): ?Recipe
    {
        $sql = "SELECT rowid, ref, title, target_batch_size, og, fg, ibu, ebc FROM " . MAIN_DB_PREFIX . "brew_recipe WHERE ref = '" . $this->db->escape($ref) . "'";
        $res = $this->db->query($sql);
        if (!$res || $this->db->num_rows($res) === 0) {
            return null;
        }

        $obj = $this->db->fetch_object($res);
        return new Recipe(
            (int)$obj->rowid,
            $obj->ref,
            $obj->title,
            (float)$obj->target_batch_size,
            (float)$obj->og,
            (float)$obj->fg,
            (float)$obj->ibu,
            (float)$obj->ebc
        );
    }

    public function save(Recipe $recipe): Recipe
    {
        if ($recipe->getId() === null) {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_recipe (ref, title, target_batch_size, og, fg, ibu, ebc) VALUES (";
            $sql .= "'" . $this->db->escape($recipe->getRef()) . "', ";
            $sql .= "'" . $this->db->escape($recipe->getTitle()) . "', ";
            $sql .= (float)$recipe->getTargetBatchSizeLiters() . ", ";
            $sql .= (float)$recipe->getOriginalGravity() . ", ";
            $sql .= (float)$recipe->getFinalGravity() . ", ";
            $sql .= (float)$recipe->getEstimatedIbu() . ", ";
            $sql .= (float)$recipe->getEstimatedEbc() . ")";

            $res = $this->db->query($sql);
            if (!$res) {
                throw new RuntimeException("Database error saving Recipe: " . $this->db->lasterror());
            }

            $id = $this->db->last_insert_id(MAIN_DB_PREFIX . "brew_recipe");
            return new Recipe(
                $id,
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
            $sql = "UPDATE " . MAIN_DB_PREFIX . "brew_recipe SET ";
            $sql .= "ref = '" . $this->db->escape($recipe->getRef()) . "', ";
            $sql .= "title = '" . $this->db->escape($recipe->getTitle()) . "', ";
            $sql .= "target_batch_size = " . (float)$recipe->getTargetBatchSizeLiters() . ", ";
            $sql .= "og = " . (float)$recipe->getOriginalGravity() . ", ";
            $sql .= "fg = " . (float)$recipe->getFinalGravity() . ", ";
            $sql .= "ibu = " . (float)$recipe->getEstimatedIbu() . ", ";
            $sql .= "ebc = " . (float)$recipe->getEstimatedEbc() . " ";
            $sql .= "WHERE rowid = " . (int)$recipe->getId();

            $res = $this->db->query($sql);
            if (!$res) {
                throw new RuntimeException("Database error updating Recipe: " . $this->db->lasterror());
            }

            return $recipe;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "brew_recipe WHERE rowid = " . (int)$id;
        return (bool)$this->db->query($sql);
    }
}
