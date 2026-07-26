<?php

namespace BrewMo\Domain\Yeast;

use RuntimeException;

/**
 * Domain Entity representing a Yeast strain batch / generation pitch.
 */
class YeastBatch
{
    private ?int $id;
    private string $ref;
    private int $productId; // Relates to Dolibarr llx_product (yeast strain)
    private int $generation; // Generation count (1 = fresh pitch, 2+ = harvested repitch)
    private float $viabilityPercentage; // e.g. 95.5%
    private ?int $harvestedFromBrewSessionId;
    private bool $isRetired;

    public function __construct(
        ?int $id,
        string $ref,
        int $productId,
        int $generation = 1,
        float $viabilityPercentage = 100.0,
        ?int $harvestedFromBrewSessionId = null,
        bool $isRetired = false
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->productId = $productId;
        $this->generation = $generation;
        $this->viabilityPercentage = $viabilityPercentage;
        $this->harvestedFromBrewSessionId = $harvestedFromBrewSessionId;
        $this->isRetired = $isRetired;
    }

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getProductId(): int { return $this->productId; }
    public function getGeneration(): int { return $this->generation; }
    public function getViabilityPercentage(): float { return $this->viabilityPercentage; }
    public function getHarvestedFromBrewSessionId(): ?int { return $this->harvestedFromBrewSessionId; }
    public function isRetired(): bool { return $this->isRetired; }

    public function harvestNewGeneration(string $newRef, int $brewSessionId, float $viability): self
    {
        if ($this->isRetired) {
            throw new RuntimeException("Cannot harvest yeast from a retired yeast batch.");
        }

        return new self(
            null,
            $newRef,
            $this->productId,
            $this->generation + 1,
            $viability,
            $brewSessionId,
            false
        );
    }

    public function retire(): void
    {
        $this->isRetired = true;
    }
}
