<?php

namespace BrewMo\Application\Service;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\Vessel\Vessel;
use RuntimeException;

/**
 * Application Service responsible for assigning brew sessions to brewery vessels/tanks.
 * Ensures strict validation: Vessel must be clean and unassigned/unoccupied.
 */
class VesselSchedulerService
{
    /**
     * Assign a vessel to a brew session with validation.
     */
    public function assignVesselToSession(BrewSession $session, Vessel $vessel): void
    {
        if (!$vessel->isClean()) {
            throw new RuntimeException(
                sprintf("Cannot assign Vessel '%s' (%s) to Batch '%s': Tank requires CIP cleaning first.", $vessel->getName(), $vessel->getRef(), $session->getRef())
            );
        }

        if ($vessel->isOccupied()) {
            throw new RuntimeException(
                sprintf("Cannot assign Vessel '%s' (%s) to Batch '%s': Tank is currently occupied by another batch.", $vessel->getName(), $vessel->getRef(), $session->getRef())
            );
        }

        // Occupy vessel and link to session
        $vessel->occupy();
        $session->assignVessel($vessel->getId());
    }

    /**
     * Release a vessel when a brew session is transferred or finished.
     */
    public function releaseVesselFromSession(BrewSession $session, Vessel $vessel): void
    {
        $vessel->release(); // Automatically marks vessel as dirty
    }
}
