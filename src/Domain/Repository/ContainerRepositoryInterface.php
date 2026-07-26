<?php

namespace BrewMo\Domain\Repository;

use BrewMo\Domain\Container\Container;

/**
 * Interface for Container Repository - DDD Pattern
 */
interface ContainerRepositoryInterface
{
    /**
     * Find a container by ID
     */
    public function findById(int $id): ?Container;

    /**
     * Find a container by reference
     */
    public function findByRef(string $ref): ?Container;

    /**
     * Find containers by status
     * @return array<Container>
     */
    public function findByStatus(string $status): array;

    /**
     * Find containers assigned to a brew session
     * @return array<Container>
     */
    public function findByBrewSession(int $brewSessionId): array;

    /**
     * Find all containers
     * @return array<Container>
     */
    public function findAll(): array;

    /**
     * Save a container (insert or update)
     */
    public function save(Container $container): Container;

    /**
     * Delete a container by ID
     */
    public function delete(int $id): bool;
}
