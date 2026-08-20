<?php

namespace BrewMo\Domain\CustomOrder;

use InvalidArgumentException;

class CustomOrder
{
    private ?int $id;
    private string $ref;
    private int $customerId;
    private ?int $testOrderId;
    private ?int $fullOrderId;
    private int $testRecipeId;
    private ?int $fullRecipeId;
    private CustomOrderStage $stage;
    private ?int $customerRating;
    private ?string $customerNotes;

    public function __construct(
        ?int $id,
        string $ref,
        int $customerId,
        int $testRecipeId,
        CustomOrderStage $stage = CustomOrderStage::TEST_36_BOTTLES,
        ?int $testOrderId = null,
        ?int $fullOrderId = null,
        ?int $fullRecipeId = null,
        ?int $customerRating = null,
        ?string $customerNotes = null
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->customerId = $customerId;
        $this->testRecipeId = $testRecipeId;
        $this->stage = $stage;
        $this->testOrderId = $testOrderId;
        $this->fullOrderId = $fullOrderId;
        $this->fullRecipeId = $fullRecipeId;
        $this->customerRating = $customerRating;
        $this->customerNotes = $customerNotes;
    }

    public function advanceStage(CustomOrderStage $newStage): void
    {
        if (!$this->stage->canAdvanceTo($newStage)) {
            throw new InvalidArgumentException("Cannot transition custom order from {$this->stage->value} to {$newStage->value}");
        }
        $this->stage = $newStage;
    }

    public function recordFeedback(int $rating, ?string $notes): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException("Feedback rating must be between 1 and 5 stars");
        }
        $this->customerRating = $rating;
        $this->customerNotes = $notes;
        $this->stage = CustomOrderStage::FEEDBACK_RECEIVED;
    }

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getTestOrderId(): ?int { return $this->testOrderId; }
    public function getFullOrderId(): ?int { return $this->fullOrderId; }
    public function getTestRecipeId(): int { return $this->testRecipeId; }
    public function getFullRecipeId(): ?int { return $this->fullRecipeId; }
    public function getStage(): CustomOrderStage { return $this->stage; }
    public function getCustomerRating(): ?int { return $this->customerRating; }
    public function getCustomerNotes(): ?string { return $this->customerNotes; }
}
