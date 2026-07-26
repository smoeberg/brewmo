<?php

namespace BrewMo\Domain\Recipe;

use BrewMo\Domain\ValueObject\Gravity;
use BrewMo\Domain\ValueObject\Volume;

class Recipe
{
    private ?int $id;
    private string $ref;
    private string $title;
    private Gravity $og;
    private Gravity $fg;
    private float $ibu;
    private float $ebc;
    private Volume $targetBatchSize;
    /** @var RecipeIngredient[] */
    private array $ingredients = [];

    public function __construct(
        ?int $id,
        string $ref,
        string $title,
        Gravity $og,
        Gravity $fg,
        float $ibu,
        float $ebc,
        Volume $targetBatchSize
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->title = $title;
        $this->og = $og;
        $this->fg = $fg;
        $this->ibu = $ibu;
        $this->ebc = $ebc;
        $this->targetBatchSize = $targetBatchSize;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOg(): Gravity
    {
        return $this->og;
    }

    public function getFg(): Gravity
    {
        return $this->fg;
    }

    public function getIbu(): float
    {
        return $this->ibu;
    }

    public function getEbc(): float
    {
        return $this->ebc;
    }

    public function getTargetBatchSize(): Volume
    {
        return $this->targetBatchSize;
    }

    /**
     * Rich Domain Method: Calculate Estimated ABV (Alcohol By Volume)
     */
    public function calculateEstimatedABV(): float
    {
        $abv = ($this->og->getSpecificGravity() - $this->fg->getSpecificGravity()) * 131.25;
        return round(max(0, $abv), 2);
    }

    /**
     * Rich Domain Method: Is Non-Alcoholic Beer (< 0.5% ABV)
     */
    public function isNonAlcoholic(): bool
    {
        return $this->calculateEstimatedABV() < 0.5;
    }

    /**
     * Rich Domain Method: Apparent Attenuation Percentage
     */
    public function calculateApparentAttenuation(): float
    {
        $og = $this->og->getSpecificGravity();
        $fg = $this->fg->getSpecificGravity();

        if ($og <= 1.000) {
            return 0.0;
        }

        $attenuation = (($og - $fg) / ($og - 1.000)) * 100;
        return round($attenuation, 1);
    }

    public function addIngredient(RecipeIngredient $ingredient): void
    {
        $this->ingredients[] = $ingredient;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }
}
