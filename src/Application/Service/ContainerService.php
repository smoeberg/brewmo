<?php

namespace BrewMo\Application\Service;

use BrewMo\Domain\Container\Container;
use BrewMo\Domain\Container\ContainerStatus;
use BrewMo\Domain\Container\ContainerType;
use BrewMo\Domain\Repository\ContainerRepositoryInterface;
use RuntimeException;

/**
 * Application Service for managing Containers (Kegs, Casks, Tanks)
 * Handles business logic and orchestration for container operations
 */
class ContainerService
{
    private ContainerRepositoryInterface $containerRepository;

    public function __construct(ContainerRepositoryInterface $containerRepository)
    {
        $this->containerRepository = $containerRepository;
    }

    /**
     * Create a new container
     */
    public function createContainer(
        string $ref,
        string $label,
        float $capacityLiters,
        ContainerType $type,
        ?string $location = null,
        ?string $notes = null
    ): Container {
        $container = new Container(
            null,
            $ref,
            $label,
            $capacityLiters,
            $type,
            ContainerStatus::AVAILABLE,
            null,
            null,
            $location,
            true,
            $notes
        );

        return $this->containerRepository->save($container);
    }

    /**
     * Assign a container to a brew session
     */
    public function assignToBrewSession(int $containerId, int $brewSessionId, string $batchNumber): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        // Check if container is available
        if ($container->getStatus() !== ContainerStatus::AVAILABLE) {
            throw new RuntimeException("Container {$container->getRef()} is not available for assignment");
        }

        if (!$container->isActive()) {
            throw new RuntimeException("Container {$container->getRef()} is not active");
        }

        $container->assignToBrewSession($brewSessionId, $batchNumber);
        return $this->containerRepository->save($container);
    }

    /**
     * Release a container from a brew session
     */
    public function releaseContainer(int $containerId): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->release();
        return $this->containerRepository->save($container);
    }

    /**
     * Mark container as in maintenance
     */
    public function markForMaintenance(int $containerId, ?string $notes = null): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->markForMaintenance();
        if ($notes) {
            $container = new Container(
                $container->getId(),
                $container->getRef(),
                $container->getLabel(),
                $container->getCapacityLiters(),
                $container->getType(),
                ContainerStatus::MAINTENANCE,
                $container->getCurrentBrewSessionId(),
                $container->getCurrentBatchNumber(),
                $container->getLocation(),
                $container->isActive(),
                $notes
            );
        }
        
        return $this->containerRepository->save($container);
    }

    /**
     * Mark container as lost
     */
    public function markAsLost(int $containerId, ?string $notes = null): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->markAsLost();
        if ($notes) {
            $container = new Container(
                $container->getId(),
                $container->getRef(),
                $container->getLabel(),
                $container->getCapacityLiters(),
                $container->getType(),
                ContainerStatus::LOST,
                $container->getCurrentBrewSessionId(),
                $container->getCurrentBatchNumber(),
                $container->getLocation(),
                false, // Deactivate
                $notes
            );
        }
        
        return $this->containerRepository->save($container);
    }

    /**
     * Update container location
     */
    public function updateLocation(int $containerId, string $location): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->updateLocation($location);
        return $this->containerRepository->save($container);
    }

    /**
     * Get all containers assigned to a brew session
     * @return array<Container>
     */
    public function getContainersByBrewSession(int $brewSessionId): array
    {
        return $this->containerRepository->findByBrewSession($brewSessionId);
    }

    /**
     * Get all available containers
     * @return array<Container>
     */
    public function getAvailableContainers(): array
    {
        return $this->containerRepository->findByStatus(ContainerStatus::AVAILABLE->value);
    }

    /**
     * Get all containers in use
     * @return array<Container>
     */
    public function getContainersInUse(): array
    {
        return $this->containerRepository->findByStatus(ContainerStatus::IN_USE->value);
    }

    /**
     * Get all containers by type
     * @return array<Container>
     */
    public function getContainersByType(ContainerType $type): array
    {
        $allContainers = $this->containerRepository->findAll();
        return array_filter($allContainers, function($container) use ($type) {
            return $container->getType() === $type;
        });
    }

    /**
     * Deactivate a container
     */
    public function deactivateContainer(int $containerId): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->deactivate();
        return $this->containerRepository->save($container);
    }

    /**
     * Activate a container
     */
    public function activateContainer(int $containerId): Container
    {
        $container = $this->containerRepository->findById($containerId);
        if (!$container) {
            throw new RuntimeException("Container not found: {$containerId}");
        }

        $container->activate();
        return $this->containerRepository->save($container);
    }
}
