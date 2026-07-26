<?php

namespace BrewMo\Domain\QualityControl;

class QCLog
{
    private ?int $id;
    private int $brewSessionId;
    private MeasurementType $type;
    private float $value;
    private string $unit;
    private string $recordedAt;
    private ?string $notes;
    private ?string $sensorId;

    public function __construct(
        ?int $id,
        int $brewSessionId,
        MeasurementType $type,
        float $value,
        string $unit,
        string $recordedAt,
        ?string $notes = null,
        ?string $sensorId = null
    ) {
        $this->id = $id;
        $this->brewSessionId = $brewSessionId;
        $this->type = $type;
        $this->value = $value;
        $this->unit = $unit;
        $this->recordedAt = $recordedAt;
        $this->notes = $notes;
        $this->sensorId = $sensorId;
    }

    public function getId(): ?int { return $this->id; }
    public function getBrewSessionId(): int { return $this->brewSessionId; }
    public function getType(): MeasurementType { return $this->type; }
    public function getValue(): float { return $this->value; }
    public function getUnit(): string { return $this->unit; }
    public function getRecordedAt(): string { return $this->recordedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getSensorId(): ?string { return $this->sensorId; }
}
