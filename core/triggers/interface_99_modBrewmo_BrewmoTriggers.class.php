<?php

/**
 * BrewMo Dolibarr Trigger Hook
 * Captures core Dolibarr events (e.g. Stock movements, Order validations) to trigger BrewMo business logic.
 */
class InterfaceBrewmoTriggers
{
    private DoliDB $db;

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Trigger execution hook called by Dolibarr core when events occur.
     */
    public function run_trigger(string $action, object $object, User $user, Translate $langs, conf $conf): int
    {
        if ($action === 'LINEBILL_INSERT' || $action === 'ORDER_VALIDATE') {
            // Trigger BrewMo container allocation check or batch reservation
            return 1;
        }

        if ($action === 'STOCK_MOVEMENT') {
            // Log stock audit for raw material consumption
            return 1;
        }

        return 0;
    }
}
