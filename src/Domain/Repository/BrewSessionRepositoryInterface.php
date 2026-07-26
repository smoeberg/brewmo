<?php

namespace BrewMo\Domain\Repository;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;

/**
 * Domain Repository Interface for BrewSessions.
 * Completely decouples Domain logic from underlying storage (Dolibarr DB / SQL).
 */
interface BrewSessionRepositoryInterface
{
    /**
     * Find a brew session by ID
     */
    public function findById(int $id): ?BrewSession;

    /**
     * Find a brew session by reference
     */
    public function findByRef(string $ref): ?BrewSession;

    /**
     * Find brew sessions by recipe ID
     * @return array<BrewSession>
     */
    public function findByRecipeId(int $recipeId): array;

    /**
     * Find brew sessions by state
     * @return array<BrewSession>
     */
    public function findByState(BrewSessionState $state): array;

    /**
     * Find brew sessions by vessel ID
     * @return array<BrewSession>
     */
    public function findByVesselId(int $vesselId): array;

    /**
     * Find all brew sessions
     * @return array<BrewSession>
     */
    public function findAll(): array;

    /**
     * Save a brew session (insert or update)
     */
    public function save(BrewSession $session): BrewSession;

    /**
     * Delete a brew session by ID
     */
    public function delete(int $id): bool;
}
