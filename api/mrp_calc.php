<?php
$res = 0;
$paths = array(
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php'
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) { $res = @include $p; }
}
if (!$res) { die('Include of main.inc.php failed'); }

dol_syslog("brewmo/api/mrp_calc called");

header('Content-Type: application/json');

$recipe_id   = (int) GETPOST('recipe_id', 'int');
$batch_count = (float) GETPOST('batches', 'alpha');

if ($recipe_id <= 0 || $batch_count <= 0) {
    echo json_encode(array('error' => 'Missing recipe_id or batches'));
    exit;
}

$db = $GLOBALS['db'];
$result = array(
    'malts'  => array(),
    'hops'   => array(),
    'yeasts' => array(),
    'extras' => array()
);

function brewmo_get_stock($db, $product_id) {
    $sql = "SELECT SUM(real) as stock FROM ".$db->prefix()."product_stock";
    $sql .= " WHERE fk_product = ".((int) $product_id);
    $resql = $db->query($sql);
    if (!$resql) return 0;
    $obj = $db->fetch_object($resql);
    return (double) $obj->stock;
}

// Malt
$sql = "SELECT rm.fk_product, rm.qty_kg, p.ref, p.label";
$sql .= " FROM ".$db->prefix()."brew_recipe_malt as rm";
$sql .= " JOIN ".$db->prefix()."product as p ON p.rowid = rm.fk_product";
$sql .= " WHERE rm.fk_recipe = ".$recipe_id;
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $needed = $obj->qty_kg * $batch_count;
        $stock  = brewmo_get_stock($db, $obj->fk_product);
        $toorder = max(0, $needed - $stock);

        $result['malts'][] = array(
            'product_id'  => $obj->fk_product,
            'ref'         => $obj->ref,
            'label'       => $obj->label,
            'needed_kg'   => $needed,
            'stock_kg'    => $stock,
            'to_order_kg' => $toorder
        );
    }
}

// TODO: same pattern for hops, yeasts, extras when recipe lines are populated.

echo json_encode($result);
