<?php

namespace BrewMo\Domain\BrewSession;

use InvalidArgumentException;
use RuntimeException;

/**
 * Domain Entity representing a Brew Session in BrewMo 2.0.
 */
class BrewSession
{
    private ?int $id;
    private string $ref;
    private string $title;
    private int $recipeId;
    private BrewSessionState $state;
    private float $plannedVolumeLiters;
    private ?int $vesselId;
    private ?string $lotNumber;
    private ?string $startDate;
    private ?string $endDate;

    public function __construct(
        ?int $id,
        string $ref,
        string $title,
        int $recipeId,
        float $plannedVolumeLiters,
        BrewSessionState $state = BrewSessionState::DRAFT,
        ?int $vesselId = null,
        ?string $lotNumber = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->title = $title;
        $this->recipeId = $recipeId;
        $this->plannedVolumeLiters = $plannedVolumeLiters;
        $this->state = $state;
        $this->vesselId = $vesselId;
        $this->lotNumber = $lotNumber;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getTitle(): string { return $this->title; }
    public function getRecipeId(): int { return $this->recipeId; }
    public function getState(): BrewSessionState { return $this->state; }
    public function getPlannedVolumeLiters(): float { return $this->plannedVolumeLiters; }
    public function getVesselId(): ?int { return $this->vesselId; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getStartDate(): ?string { return $this->startDate; }
    public function getEndDate(): ?string { return $this->endDate; }

    /**
     * Transition brew session to a new state.
     */
    public function transitionTo(BrewSessionState $newState): void
    {
        if (!$this->state->canTransitionTo($newState)) {
            throw new RuntimeException("Invalid state transition from {$this->state->value} to {$newState->value}");
        }
        $this->state = $newState;
    }

    public function assignVessel(int $vesselId): void
    {
        $this->vesselId = $vesselId;
    }

    public function setLotNumber(string $lotNumber): void
    {
        $this->lotNumber = $lotNumber;
    }
}
