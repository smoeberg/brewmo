<?php
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

class InterfaceBrewmoPackaging extends DolibarrTriggers
{
    public $family = 'brewmo';
    public $picto = 'generic';
    public $description = 'Brewmo packaging trigger (stub)';

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // For now, do nothing. Can later hook on STOCK_MOVEMENT or similar.
        return 0;
    }
}
