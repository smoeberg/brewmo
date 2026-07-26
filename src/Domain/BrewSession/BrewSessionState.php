<?php

namespace BrewMo\Domain\BrewSession;

enum BrewSessionState: string
{
    case DRAFT        = 'DRAFT';
    case PLANNED      = 'PLANNED';
    case MASHING      = 'MASHING';
    case BOILING      = 'BOILING';
    case FERMENTING   = 'FERMENTING';
    case CONDITIONING = 'CONDITIONING';
    case PACKAGING   = 'PACKAGING';
    case COMPLETED   = 'COMPLETED';
    case CANCELLED   = 'CANCELLED';

    /**
     * Allowed state transitions (Rigid MES Workflow Engine)
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        // CANCELLED is a terminal state, or can be reached from early phases
        if ($target === self::CANCELLED) {
            return in_array($this, [self::DRAFT, self::PLANNED, self::MASHING, self::BOILING], true);
        }

        return match ($this) {
            self::DRAFT        => $target === self::PLANNED,
            self::PLANNED      => $target === self::MASHING,
            self::MASHING      => $target === self::BOILING,
            self::BOILING      => $target === self::FERMENTING,
            self::FERMENTING   => $target === self::CONDITIONING,
            self::CONDITIONING => $target === self::PACKAGING,
            self::PACKAGING   => $target === self::COMPLETED,
            self::COMPLETED   => false, // Terminal state
            self::CANCELLED   => false, // Terminal state
        };
    }
}
