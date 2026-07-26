<?php

namespace BrewMo\Domain\Validation;

use InvalidArgumentException;

class RecipeValidator
{
    public static function validate(array $data): void
    {
        $errors = [];

        if (empty($data['ref']) || !is_string($data['ref'])) {
            $errors[] = "Reference (ref) er påkrævet og skal være en streng.";
        }

        if (empty($data['title']) || !is_string($data['title'])) {
            $errors[] = "Titel (title) er påkrævet og skal være en streng.";
        }

        if (isset($data['og']) && ($data['og'] < 0.900 || $data['og'] > 1.300)) {
            $errors[] = "Original Gravity (og) skal være i intervallet 0.900 til 1.300.";
        }

        if (isset($data['fg']) && ($data['fg'] < 0.800 || $data['fg'] > 1.200)) {
            $errors[] = "Final Gravity (fg) skal være i intervallet 0.800 til 1.200.";
        }

        if (isset($data['og'], $data['fg']) && $data['fg'] > $data['og']) {
            $errors[] = "Final Gravity (fg) kan ikke være højere end Original Gravity (og).";
        }

        if (isset($data['ibu']) && $data['ibu'] < 0) {
            $errors[] = "IBU kan ikke være negativt.";
        }

        if (isset($data['ebc']) && $data['ebc'] < 0) {
            $errors[] = "Farve (EBC) kan ikke være negativt.";
        }

        if (isset($data['target_batch_size']) && $data['target_batch_size'] <= 0) {
            $errors[] = "Målstørrelse (target_batch_size) skal være større end 0 liter.";
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException("Valideringsfejl i opskrift: " . implode(" ", $errors));
        }
    }
}
