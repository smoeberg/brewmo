<?php

namespace BrewMo\Domain\Repository;

use BrewMo\Domain\Recipe\Recipe;

/**
 * Repository interface for Recipe domain entities.
 */
interface RecipeRepositoryInterface
{
    public function findById(int $id): ?Recipe;
    public function findByRef(string $ref): ?Recipe;
    public function save(Recipe $recipe): Recipe;
    public function delete(int $id): bool;
}
