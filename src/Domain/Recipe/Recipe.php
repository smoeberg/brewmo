<?php

namespace BrewMo\Domain\Recipe;

class Recipe
{
    private ?int $id;
    private string $ref;
    private string $title;
    private float $targetBatchSizeLiters;
    private float $originalGravity; // OG (e.g. 1.050)
    private float $finalGravity;    // FG (e.g. 1.010)
    private float $estimatedAbv;    // Calculated ABV %
    private float $estimatedIbu;    // Bitterness
    private float $estimatedEbc;    // Color
    private array $ingredients;     // Array of RecipeIngredient

    public function __construct(
        ?int $id,
        string $ref,
        string $title,
        float $targetBatchSizeLiters,
        float $originalGravity,
        float $finalGravity,
        float $estimatedIbu = 0.0,
        float $estimatedEbc = 0.0,
        array $ingredients = []
    ) {
        $this->id = $id;
        $this->ref = $ref;
        $this->title = $title;
        $this->targetBatchSizeLiters = $targetBatchSizeLiters;
        $this->originalGravity = $originalGravity;
        $this->finalGravity = $finalGravity;
        $this->estimatedAbv = self::calculateAbv($originalGravity, $finalGravity);
        $this->estimatedIbu = $estimatedIbu;
        $this->estimatedEbc = $estimatedEbc;
        $this->ingredients = $ingredients;
    }

    public static function calculateAbv(float $og, float $fg): float
    {
        if ($og <= $fg) {
            return 0.0;
        }
        return round(($og - $fg) * 131.25, 2);
    }

    public function getId(): ?int { return $this->id; }
    public function getRef(): string { return $this->ref; }
    public function getTitle(): string { return $this->title; }
    public function getTargetBatchSizeLiters(): float { return $this->targetBatchSizeLiters; }
    public function getOriginalGravity(): float { return $this->originalGravity; }
    public function getFinalGravity(): float { return $this->finalGravity; }
    public function getEstimatedAbv(): float { return $this->estimatedAbv; }
    public function getEstimatedIbu(): float { return $this->estimatedIbu; }
    public function getEstimatedEbc(): float { return $this->estimatedEbc; }
    public function getIngredients(): array { return $this->ingredients; }
}
