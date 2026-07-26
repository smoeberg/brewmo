<?php

namespace BrewMo\Domain\Container;

use RuntimeException;

/**
 * Domain Entity representing a Container (Keg, Cask, etc.) in BrewMo 2.0.
 */
class Container
{
    private ?int $id;
    private string $ref;
    private string $label;
    private float $capacityLiters;
    private ContainerType $type;
    private ContainerStatus $status;
    private ?int $currentBrewSessionId;
    private ?string $currentBatchNumber;
    private ?string $location;
    private bool $isActive;
    private ?string $notes;

    public function __construct(
        ?int $id,
        string $ref,
        string $label,
        float $capacityLiters,
        ContainerType $type,
        ContainerStatus $status = ContainerStatus::AVAILABLE,
        ?int $currentBrewSessionId = null,
        ?string $currentBatchNumber = null,
        ?string $location = null,
        bool $isActive = true,
        ?string $notes = null
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->label = $label;
        $this->capacityLiters = $capacityLiters;
        $this->type = $type;
        $this->status = $status;
        $this->currentBrewSessionId = $currentBrewSessionId;
        $this->currentBatchNumber = $currentBatchNumber;
        $this->location = $location;
        $this->isActive = $isActive;
        $this->notes = $notes;
    }

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getLabel(): string { return $this->label; }
    public function getCapacityLiters(): float { return $this->capacityLiters; }
    public function getType(): ContainerType { return $this->type; }
    public function getStatus(): ContainerStatus { return $this->status; }
    public function getCurrentBrewSessionId(): ?int { return $this->currentBrewSessionId; }
    public function getCurrentBatchNumber(): ?string { return $this->currentBatchNumber; }
    public function getLocation(): ?string { return $this->location; }
    public function isActive(): bool { return $this->isActive; }
    public function getNotes(): ?string { return $this->notes; }

    /**
     * Assign container to a brew session
     */
    public function assignToBrewSession(int $brewSessionId, string $batchNumber): void
    {
        $this->currentBrewSessionId = $brewSessionId;
        $this->currentBatchNumber = $batchNumber;
        $this->status = ContainerStatus::IN_USE;
    }

    /**
     * Release container from brew session
     */
    public function release(): void
    {
        $this->currentBrewSessionId = null;
        $this->currentBatchNumber = null;
        $this->status = ContainerStatus::AVAILABLE;
    }

    /**
     * Mark container as in maintenance
     */
    public function markForMaintenance(): void
    {
        $this->status = ContainerStatus::MAINTENANCE;
    }

    /**
     * Mark container as lost
     */
    public function markAsLost(): void
    {
        $this->status = ContainerStatus::LOST;
        $this->isActive = false;
    }

    /**
     * Update location
     */
    public function updateLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * Deactivate container
     */
    public function deactivate(): void
    {
        $this->isActive = false;
    }

    /**
     * Activate container
     */
    public function activate(): void
    {
        $this->isActive = true;
    }
}
