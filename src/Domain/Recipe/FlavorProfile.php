<?php

namespace BrewMo\Domain\Recipe;

use InvalidArgumentException;

class FlavorProfile
{
    private int $bitterness;  // 1 to 10
    private int $sweetness;   // 1 to 10
    private int $roastiness;  // 1 to 10
    private int $fruitiness;  // 1 to 10
    private int $body;        // 1 to 10
    private string $alcoholTarget; // 'LIGHT' (3.5-4.5%), 'STANDARD' (4.8-6.0%), 'STRONG' (6.5-8.5%)

    public function __construct(
        int $bitterness,
        int $sweetness,
        int $roastiness,
        int $fruitiness,
        int $body,
        string $alcoholTarget = 'STANDARD'
    ) {
        $this->validateRange('bitterness', $bitterness);
        $this->validateRange('sweetness', $sweetness);
        $this->validateRange('roastiness', $roastiness);
        $this->validateRange('fruitiness', $fruitiness);
        $this->validateRange('body', $body);

        $allowedAlcohol = ['LIGHT', 'STANDARD', 'STRONG'];
        $upperAlc = strtoupper($alcoholTarget);
        if (!in_array($upperAlc, $allowedAlcohol, true)) {
            throw new InvalidArgumentException("Invalid alcoholTarget: $alcoholTarget. Allowed: " . implode(', ', $allowedAlcohol));
        }

        $this->bitterness = $bitterness;
        $this->sweetness = $sweetness;
        $this->roastiness = $roastiness;
        $this->fruitiness = $fruitiness;
        $this->body = $body;
        $this->alcoholTarget = $upperAlc;
    }

    private function validateRange(string $field, int $value): void
    {
        if ($value < 1 || $value > 10) {
            throw new InvalidArgumentException("Flavor dimension '$field' must be between 1 and 10. Received: $value");
        }
    }

    public function getBitterness(): int { return $this->bitterness; }
    public function getSweetness(): int { return $this->sweetness; }
    public function getRoastiness(): int { return $this->roastiness; }
    public function getFruitiness(): int { return $this->fruitiness; }
    public function getBody(): int { return $this->body; }
    public function getAlcoholTarget(): string { return $this->alcoholTarget; }

    public function toArray(): array
    {
        return [
            'bitterness' => $this->bitterness,
            'sweetness' => $this->sweetness,
            'roastiness' => $this->roastiness,
            'fruitiness' => $this->fruitiness,
            'body' => $this->body,
            'alcoholTarget' => $this->alcoholTarget
        ];
    }
}
