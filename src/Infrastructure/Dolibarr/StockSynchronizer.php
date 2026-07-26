<?php

namespace BrewMo\Infrastructure\Dolibarr;

/**
 * Synchronizes BrewMo domain events with Dolibarr stock management (llx_stock & llx_stock_mvt).
 */
class StockSynchronizer
{
    private object $db; // Dolibarr DoliDB object

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    /**
     * Deduct raw materials from warehouse stock when a brew session enters MASHING state.
     */
    public function consumeIngredientsForBrew(int $warehouseId, array $materialRequirements, string $brewRef): bool
    {
        foreach ($materialRequirements as $item) {
            $productId = $item['productId'];
            $qtyToDeduct = $item['requiredAmount'];

            // In Dolibarr, stock decrement uses MouvementStock class or direct SQL / API hooks
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "stock_mvt (fk_product, fk_entrepot, qty, datem, label) ";
            $sql .= "VALUES (" . (int)$productId . ", " . (int)$warehouseId . ", -" . (float)$qtyToDeduct . ", ";
            $sql .= "'" . $this->db->escape(date('Y-m-d H:i:s')) . "', ";
            $sql .= "'" . $this->db->escape("BrewMo Consumption - Batch " . $brewRef) . "')";

            $this->db->query($sql);
        }

        return true;
    }
}
