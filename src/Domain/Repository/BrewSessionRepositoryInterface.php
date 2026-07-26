<?php

namespace BrewMo\Domain\Repository;

use BrewMo\Domain\BrewSession\BrewSession;

/**
 * Domain Repository Interface for BrewSessions.
 * Completely decouples Domain logic from underlying storage (Dolibarr DB / SQL).
 */
interface BrewSessionRepositoryInterface
{
    public function findById(int $id): ?BrewSession;
    public function findByRef(string $ref): ?BrewSession;
    public function save(BrewSession $session): BrewSession;
    public function delete(int $id): bool;
}
