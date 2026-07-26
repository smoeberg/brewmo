<?php

namespace BrewMo\Domain\Repository;

use BrewMo\Domain\Recipe\Recipe;

/**
 * Interface for Recipe Repository - DDD Pattern
 */
interface RecipeRepositoryInterface
{
    /**
     * Find a recipe by ID
     */
    public function findById(int $id): ?Recipe;

    /**
     * Find a recipe by reference
     */
    public function findByRef(string $ref): ?Recipe;

    /**
     * Find all recipes
     * @return array<Recipe>
     */
    public function findAll(): array;

    /**
     * Save a recipe (insert or update)
     */
    public function save(Recipe $recipe): Recipe;

    /**
     * Delete a recipe by ID
     */
    public function delete(int $id): bool;
}
