<?php

namespace BrewMo\Domain\Vessel;

enum VesselType: string
{
    case MASH_TUN = 'MASH_TUN';
    case BREW_KETTLE = 'BREW_KETTLE';
    case FERMENTER = 'FERMENTER';
    case BRITE_TANK = 'BRITE_TANK';
    case SERVING_TANK = 'SERVING_TANK';
}
