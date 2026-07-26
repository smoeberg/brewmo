<?php

namespace BrewMo\Domain\Container;

/**
 * Enumeration of Container statuses for BrewMo 2.0.
 * Represents the physical and operational state of a container (keg, cask, tank, etc.)
 */
enum ContainerStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case IN_USE = 'IN_USE';
    case FULL = 'FULL';
    case EMPTY = 'EMPTY';
    case IN_BREWERY = 'IN_BREWERY';
    case AT_CUSTOMER = 'AT_CUSTOMER';
    case IN_TRANSIT = 'IN_TRANSIT';
    case MAINTENANCE = 'MAINTENANCE';
    case CLEANING = 'CLEANING';
    case LOST = 'LOST';
    case RETIRED = 'RETIRED';
}
