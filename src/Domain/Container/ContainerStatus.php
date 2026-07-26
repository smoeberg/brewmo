<?php

namespace BrewMo\Domain\Container;

enum ContainerStatus: string
{
    case IN_BREWERY = 'IN_BREWERY';
    case AT_CUSTOMER = 'AT_CUSTOMER';
    case IN_TRANSIT = 'IN_TRANSIT';
    case MAINTENANCE = 'MAINTENANCE';
    case RETIRED = 'RETIRED';
}
