<?php

namespace BrewMo\Domain\Container;

/**
 * Enumeration of Container types for BrewMo 2.0.
 */
enum ContainerType: string
{
    case KEG = 'KEG';
    case KEYKEG = 'KEYKEG';
    case CASK = 'CASK';
    case TANK = 'TANK';
    case BOTTLE = 'BOTTLE';
    case CAN = 'CAN';
    case OTHER = 'OTHER';
}
