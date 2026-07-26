<?php

namespace BrewMo\Infrastructure\Adapter;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;

/**
 * Adapter to convert between Legacy BrewmoBrewSession and new BrewSession domain entity
 * This allows gradual migration from old code to new DDD architecture
 */
class LegacyBrewSessionAdapter
{
    /**
     * Convert Legacy BrewmoBrewSession object to new BrewSession entity
     */
    public static function toDomain(object $legacySession): BrewSession
    {
        return new BrewSession(
            $legacySession->id ?? null,
            $legacySession->ref ?? '',
            $legacySession->label ?? $legacySession->title ?? '',
            $legacySession->fk_recipe ?? 0,
            $legacySession->volume_l ?? 0.0,
            self::mapLegacyStatusToState($legacySession->status ?? 0),
            $legacySession->fk_tank ?? $legacySession->fk_vessel ?? null,
            null, // lot_number not in legacy
            $legacySession->date_start ?? null,
            $legacySession->date_end ?? null
        );
    }

    /**
     * Convert new BrewSession entity to array for Legacy BrewmoBrewSession
     */
    public static function toLegacyArray(BrewSession $session): array
    {
        return [
            'id' => $session->getId(),
            'ref' => $session->getRef(),
            'title' => $session->getTitle(),
            'fk_recipe' => $session->getRecipeId(),
            'volume_l' => $session->getPlannedVolumeLiters(),
            'status' => self::mapStateToLegacyStatus($session->getState()),
            'fk_tank' => $session->getVesselId(),
            'date_start' => $session->getStartDate(),
            'date_end' => $session->getEndDate(),
            'note_public' => null,
            'note_private' => null
        ];
    }

    /**
     * Map legacy status integer to BrewSessionState enum
     */
    private static function mapLegacyStatusToState(int $legacyStatus): BrewSessionState
    {
        // Map common legacy status values to new states
        // These mappings may need to be adjusted based on actual legacy values
        return match ($legacyStatus) {
            0 => BrewSessionState::DRAFT,
            1 => BrewSessionState::PLANNED,
            2 => BrewSessionState::MASHING,
            3 => BrewSessionState::BOILING,
            4 => BrewSessionState::FERMENTING,
            5 => BrewSessionState::PACKAGING,
            6 => BrewSessionState::COMPLETED,
            9, 99 => BrewSessionState::CANCELLED,
            default => BrewSessionState::DRAFT,
        };
    }

    /**
     * Map BrewSessionState enum to legacy status integer
     */
    private static function mapStateToLegacyStatus(BrewSessionState $state): int
    {
        return match ($state) {
            BrewSessionState::DRAFT => 0,
            BrewSessionState::PLANNED => 1,
            BrewSessionState::MASHING => 2,
            BrewSessionState::BOILING => 3,
            BrewSessionState::FERMENTING => 4,
            BrewSessionState::PACKAGING => 5,
            BrewSessionState::COMPLETED => 6,
            BrewSessionState::CANCELLED => 99,
        };
    }
}
