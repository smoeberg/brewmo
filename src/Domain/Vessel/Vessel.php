<?php

namespace BrewMo\Domain\Vessel;

use BrewMo\Domain\ValueObject\Volume;
use RuntimeException;

class Vessel
{
    private ?int $id;
    private string $ref;
    private string $name;
    private VesselType $type;
    private Volume $capacity;
    private bool $isClean;
    private bool $isOccupied;

    public function __construct(
        ?int $id,
        string $ref,
        string $name,
        VesselType $type,
        Volume $capacity,
        bool $isClean = true,
        bool $isOccupied = false
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->name = $name;
        $this->type = $type;
        $this->capacity = $capacity;
        $this->isClean = $isClean;
        $this->isOccupied = $isOccupied;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): VesselType
    {
        return $this->type;
    }

    public function getCapacity(): Volume
    {
        return $this->capacity;
    }

    public function isClean(): bool
    {
        return $this->isClean;
    }

    public function isOccupied(): bool
    {
        return $this->isOccupied;
    }

    /**
     * Rich Domain Method: Check if tank can handle planned batch volume
     */
    public function canHandleVolume(Volume $plannedVolume): bool
    {
        return $plannedVolume->getLiters() <= $this->capacity->getLiters();
    }

    /**
     * Rich Domain Method: Check if tank is ready for brewing/fermenting
     */
    public function isReadyForUse(): bool
    {
        return $this->isClean && !$this->isOccupied;
    }

    public function occupy(): void
    {
        if (!$this->isClean) {
            throw new RuntimeException("Kan ikke benytte tank '{$this->ref}': Tanken skal rengøres (CIP) først.");
        }
        if ($this->isOccupied) {
            throw new RuntimeException("Tank '{$this->ref}' er allerede optaget af et andet bryg.");
        }
        $this->isOccupied = true;
    }

    public function release(): void
    {
        $this->isOccupied = false;
        $this->isClean = false; // Tank automatically becomes dirty after emptying
    }

    public function markAsClean(): void
    {
        $this->isClean = true;
    }
}
