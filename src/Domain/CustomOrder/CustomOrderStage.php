<?php

namespace BrewMo\Domain\CustomOrder;

enum CustomOrderStage: string
{
    case TEST_36_BOTTLES = 'TEST_36_BOTTLES';
    case TEST_IN_PRODUCTION = 'TEST_IN_PRODUCTION';
    case TEST_DELIVERED = 'TEST_DELIVERED';
    case FEEDBACK_RECEIVED = 'FEEDBACK_RECEIVED';
    case FULL_PROD_ORDERED = 'FULL_PROD_ORDERED';
    case COMPLETED = 'COMPLETED';

    public function canAdvanceTo(CustomOrderStage $next): bool
    {
        return match ($this) {
            self::TEST_36_BOTTLES => in_array($next, [self::TEST_IN_PRODUCTION], true),
            self::TEST_IN_PRODUCTION => in_array($next, [self::TEST_DELIVERED], true),
            self::TEST_DELIVERED => in_array($next, [self::FEEDBACK_RECEIVED, self::FULL_PROD_ORDERED], true),
            self::FEEDBACK_RECEIVED => in_array($next, [self::FULL_PROD_ORDERED, self::COMPLETED], true),
            self::FULL_PROD_ORDERED => in_array($next, [self::COMPLETED], true),
            self::COMPLETED => false,
        };
    }
}
