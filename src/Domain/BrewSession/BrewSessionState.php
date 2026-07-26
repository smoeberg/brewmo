<?php

namespace BrewMo\Domain\BrewSession;

/**
 * Enumeration of BrewSession states for the BrewMo 2.0 State Machine.
 */
enum BrewSessionState: string
{
    case DRAFT = 'DRAFT';
    case PLANNED = 'PLANNED';
    case MASHING = 'MASHING';
    case BOILING = 'BOILING';
    case FERMENTING = 'FERMENTING';
    case PACKAGING = 'PACKAGING';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    /**
     * Determine if a state transition to $newState is allowed.
     */
    public function canTransitionTo(BrewSessionState $newState): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newState, [self::PLANNED, self::CANCELLED]),
            self::PLANNED => in_array($newState, [self::MASHING, self::CANCELLED]),
            self::MASHING => in_array($newState, [self::BOILING, self::CANCELLED]),
            self::BOILING => in_array($newState, [self::FERMENTING, self::CANCELLED]),
            self::FERMENTING => in_array($newState, [self::PACKAGING, self::CANCELLED]),
            self::PACKAGING => in_array($newState, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED, self::CANCELLED => false,
        };
    }
}
