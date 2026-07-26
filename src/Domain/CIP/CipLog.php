<?php

namespace BrewMo\Domain\CIP;

enum CipType: string
{
    case CAUSTIC_WASH = 'CAUSTIC_WASH';
    case ACID_WASH = 'ACID_WASH';
    case SANITIZATION = 'SANITIZATION';
    case FULL_CIP = 'FULL_CIP';
}

class CipLog
{
    private ?int $id;
    private int $vesselId; // Relates to vessel/tank
    private CipType $type;
    private string $chemicalUsed;
    private float $temperatureCelsius;
    private int $durationMinutes;
    private string $performedAt;
    private string $performedBy;

    public function __construct(
        ?int $id,
        int $vesselId,
        CipType $type,
        string $chemicalUsed,
        float $temperatureCelsius,
        int $durationMinutes,
        string $performedAt,
        string $performedBy
    ) {
        $this->id = $id;
        $this->vesselId = $vesselId;
        $this->type = $type;
        $this->chemicalUsed = $chemicalUsed;
        $this->temperatureCelsius = $temperatureCelsius;
        $this->durationMinutes = $durationMinutes;
        $this->performedAt = $performedAt;
        $this->performedBy = $performedBy;
    }

    public function getId(): ?int { return $this->id; }
    public function getVesselId(): int { return $this->vesselId; }
    public function getType(): CipType { return $this->type; }
    public function getChemicalUsed(): string { return $this->chemicalUsed; }
    public function getTemperatureCelsius(): float { return $this->temperatureCelsius; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function getPerformedAt(): string { return $this->performedAt; }
    public function getPerformedBy(): string { return $this->performedBy; }
}
