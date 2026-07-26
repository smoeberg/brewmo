<?php

namespace BrewMo\Domain\QualityControl;

enum MeasurementType: string
{
    case GRAVITY = 'GRAVITY'; // OG, SG, FG (Plato / SG)
    case TEMPERATURE = 'TEMPERATURE'; // Celsius
    case PH = 'PH';
    case DISSOLVED_OXYGEN = 'DISSOLVED_OXYGEN'; // ppm / ppb
    case PRESSURE = 'PRESSURE'; // Bar / PSI
    case SENSORY_RATING = 'SENSORY_RATING';
}
