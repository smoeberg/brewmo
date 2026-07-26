<?php

namespace BrewMo\Domain\Validation;

use InvalidArgumentException;

class BrewSessionValidator
{
    public static function validate(array $data): void
    {
        $errors = [];

        if (empty($data['ref']) || !is_string($data['ref'])) {
            $errors[] = "Reference (ref) er påkrævet.";
        }

        if (empty($data['title']) || !is_string($data['title'])) {
            $errors[] = "Titel (title) er påkrævet.";
        }

        if (isset($data['planned_volume_liters']) && $data['planned_volume_liters'] <= 0) {
            $errors[] = "Planlagt volumen skal være større end 0 liter.";
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException("Valideringsfejl i brygsession: " . implode(" ", $errors));
        }
    }
}
