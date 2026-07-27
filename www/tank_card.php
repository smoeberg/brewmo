<?php
/**
 * BrewMo 2.0 - Tank / Vessel Card (Integrated with Dolibarr Workstations & Warehouses)
 */

// Load Dolibarr environment
$res = 0;
$paths = array(
    __DIR__ . '/../../../../main.inc.php',
    __DIR__ . '/../../../../../main.inc.php',
    __DIR__ . '/../../main.inc.php'
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) {
        $res = @include $p;
    }
}

if (!$res) {
    die("Error: Dolibarr environment initialization failed.");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$action = GETPOST('action', 'aZ09');
$id     = GETPOST('id', 'int');

llxHeader('', 'BrewMo Tank / Vessel Management');

print load_fiche_titre('BrewMo - Tank / Vessel Management', '', 'object_generic');

// Tank Types Mapping
$vesselTypes = array(
    'MASH_TUN'     => 'Mash Tun (Mæskekar)',
    'BREW_KETTLE'  => 'Brew Kettle (Brygkedel)',
    'FERMENTER'    => 'Fermenter / CCT (Gæringstank)',
    'BRITE_TANK'   => 'Brite Tank / BBT (Lagertank)',
    'SERVING_TANK' => 'Serving Tank (Udskænkningstank)'
);

// Fetch Dolibarr Workstations (llx_workstation) for integration
$workstations = array();
$sql = "SELECT rowid, ref, name FROM " . MAIN_DB_PREFIX . "workstation WHERE status = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $workstations[$obj->rowid] = $obj->ref . ' - ' . $obj->name;
    }
}

// Fetch Dolibarr Warehouses / Locations (llx_entrepot)
$warehouses = array();
$sql = "SELECT rowid, ref, lieu FROM " . MAIN_DB_PREFIX . "entrepot WHERE statut = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $warehouses[$obj->rowid] = $obj->ref . ($obj->lieu ? ' (' . $obj->lieu . ')' : '');
    }
}

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<table class="border centpercent">';
print '<tr><td class="titlefield required">Ref / Code</td><td><input type="text" name="ref" value="TANK-001" required></td></tr>';
print '<tr><td class="required">Name / Label</td><td><input type="text" name="name" value="Fermenter #1" required></td></tr>';

// Vessel Type Dropdown
print '<tr><td class="required">Vessel Type (Tanktype)</td><td>';
print '<select name="type" class="flat">';
foreach ($vesselTypes as $key => $label) {
    print '<option value="' . $key . '">' . $label . '</option>';
}
print '</select>';
print '</td></tr>';

// Capacity
print '<tr><td class="required">Capacity (Kapacitet)</td><td><input type="number" step="0.01" name="capacity_liters" value="1000.00"> Liters</td></tr>';

// Dolibarr Workstation Link
print '<tr><td>Dolibarr Workstation (Arbejdsstation)</td><td>';
if (!empty($workstations)) {
    print '<select name="fk_workstation" class="flat">';
    print '<option value="0">-- Select Dolibarr Workstation --</option>';
    foreach ($workstations as $wsId => $wsLabel) {
        print '<option value="' . $wsId . '">' . dol_escape_htmltag($wsLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">No Workstations found in Dolibarr MRP module.</span>';
}
print '</td></tr>';

// Dolibarr Warehouse / Location Link
print '<tr><td>Dolibarr Warehouse / Location (Lager)</td><td>';
if (!empty($warehouses)) {
    print '<select name="fk_entrepot" class="flat">';
    print '<option value="0">-- Select Dolibarr Warehouse --</option>';
    foreach ($warehouses as $whId => $whLabel) {
        print '<option value="' . $whId . '">' . dol_escape_htmltag($whLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">No Warehouses found in Dolibarr.</span>';
}
print '</td></tr>';

print '<tr><td>Status</td><td><label><input type="checkbox" name="is_clean" value="1" checked> CIP Clean & Ready</label></td></tr>';
print '</table>';

print '<div class="center" style="margin-top: 15px;">';
print '<input type="submit" class="button" value="Save Tank / Vessel">';
print '</div>';
print '</form>';

llxFooter();
