<?php

namespace BrewMo\Application\Service;

use BrewMo\Domain\BrewSession\BrewSession;
use BrewMo\Domain\BrewSession\BrewSessionState;
use BrewMo\Domain\Recipe\Recipe;
use BrewMo\Infrastructure\Dolibarr\StockSynchronizer;
use RuntimeException;

/**
 * Application Service orchestrating BrewSession workflows and Dolibarr integrations.
 */
class BrewSessionService
{
    private MRPService $mrpService;
    private ?StockSynchronizer $stockSynchronizer;

    public function __construct(MRPService $mrpService, ?StockSynchronizer $stockSynchronizer = null)
    {
        $this->mrpService = $mrpService;
        $this->stockSynchronizer = $stockSynchronizer;
    }

    /**
     * Advances a brew session to a new state and executes side-effects (e.g. stock deduction).
     */
    public function transitionState(
        BrewSession $session,
        Recipe $recipe,
        BrewSessionState $newState,
        ?int $warehouseId = null
    ): void {
        $session->transitionTo($newState);

        // Side-effect: When moving into MASHING, consume raw ingredients from warehouse
        if ($newState === BrewSessionState::MASHING) {
            if ($warehouseId === null) {
                throw new RuntimeException("Warehouse ID is required for raw material consumption when mashing.");
            }

            $materialRequirements = $this->mrpService->calculateMaterialRequirements(
                $recipe,
                $session->getPlannedVolumeLiters()
            );

            if ($this->stockSynchronizer !== null) {
                $this->stockSynchronizer->consumeIngredientsForBrew(
                    $warehouseId,
                    $materialRequirements,
                    $session->getRef()
                );
            }
        }
    }
}
