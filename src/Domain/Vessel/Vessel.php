<?php

namespace BrewMo\Domain\Vessel;

use BrewMo\Domain\ValueObject\Volume;

/**
 * Domain Entity representing a Brewery Tank / Vessel.
 */
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

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getName(): string { return $this->name; }
    public function getType(): VesselType { return $this->type; }
    public function getCapacity(): Volume { return $this->capacity; }
    public function isClean(): bool { return $this->isClean; }
    public function isOccupied(): bool { return $this->isOccupied; }

    public function markAsDirty(): void
    {
        $this->isClean = false;
    }

    public function markAsClean(): void
    {
        $this->isClean = true;
    }

    public function occupy(): void
    {
        $this->isOccupied = true;
    }

    public function release(): void
    {
        $this->isOccupied = false;
        $this->isClean = false; // Requiring CIP after use
    }
}
