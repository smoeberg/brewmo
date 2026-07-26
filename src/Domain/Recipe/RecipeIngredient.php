<?php

namespace BrewMo\Domain\Recipe;

enum IngredientType: string
{
    case MALT = 'MALT';
    case HOPS = 'HOPS';
    case YEAST = 'YEAST';
    case WATER_AGENT = 'WATER_AGENT';
    case OTHER = 'OTHER';
}

class RecipeIngredient
{
    private int $productId; // Relates to Dolibarr llx_product
    private IngredientType $type;
    private float $amount;
    private string $unit; // kg, g, ml, etc.
    private ?string $additionStage; // e.g. Mash, Boil 60m, Dry Hop

    public function __construct(
        int $productId,
        IngredientType $type,
        float $amount,
        string $unit,
        ?string $additionStage = null
    ) {
        $this->productId = $productId;
        $this->type = $type;
        $this->amount = $amount;
        $this->unit = $unit;
        $this->additionStage = $additionStage;
    }

    public function getProductId(): int { return $this->productId; }
    public function getType(): IngredientType { return $this->type; }
    public function getAmount(): float { return $this->amount; }
    public function getUnit(): string { return $this->unit; }
    public function getAdditionStage(): ?string { return $this->additionStage; }
}
