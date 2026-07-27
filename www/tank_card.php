<?php
/**
 * BrewMo 2.0 - Tank / Vessel Card (Creates directly in Dolibarr Core Resources llx_resource)
 */

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

$res = 0;
if (file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";

if (!$res) {
    die("Error: Dolibarr environment initialization failed.");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$action = GETPOST('action', 'aZ09');
$id     = GETPOST('id', 'int');

// Process Save Action
if ($action === 'save') {
    $ref          = GETPOST('ref', 'alpha');
    $name         = GETPOST('name', 'restreint');
    $fk_code_type = GETPOST('fk_code_type', 'alpha');
    $capacity     = GETPOST('capacity_liters', 'int');
    $fk_ws        = GETPOST('fk_workstation', 'int');

    if (!empty($ref) && !empty($name)) {
        // Insert into Dolibarr Core Resources table (llx_resource)
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "resource (ref, description, fk_code_type, entity, datec) ";
        $sql .= "VALUES (";
        $sql .= "'" . $db->escape($ref) . "', ";
        $sql .= "'" . $db->escape($name . ' (' . $capacity . 'L)') . "', ";
        $sql .= "'" . $db->escape($fk_code_type) . "', ";
        $sql .= "1, ";
        $sql .= "NOW()";
        $sql .= ")";

        $resql = $db->query($sql);
        if ($resql) {
            $resourceId = $db->last_insert_id(MAIN_DB_PREFIX . "resource");

            // Also mirror in BrewMo Vessel table for MES state tracking
            $vesselSql = "INSERT INTO " . MAIN_DB_PREFIX . "brew_vessel (entity, ref, name, type, capacity_liters, is_clean, is_occupied, datec) VALUES (1, '" . $db->escape($ref) . "', '" . $db->escape($name) . "', '" . $db->escape($fk_code_type) . "', " . (float)$capacity . ", 1, 0, NOW())";
            $db->query($vesselSql);

            setEventMessages("Tanken blev oprettet i Dolibarrs Ressource-register og tilknyttet din Workstation!", null, 'mesgs');
            header("Location: tank_list.php");
            exit;
        } else {
            setEventMessages("Fejl ved oprettelse af ressource: " . $db->lasterror(), null, 'errors');
        }
    }
}

llxHeader('', 'BrewMo Tank / Vessel Creation');

print load_fiche_titre('BrewMo - Tank / Vessel (Opret direkte som Dolibarr Ressource)', '', 'object_generic');

// Fetch Resource Types from Dolibarr Dictionary (llx_c_type_resource)
$resourceTypes = array();
$sql = "SELECT code, label FROM " . MAIN_DB_PREFIX . "c_type_resource WHERE active = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $resourceTypes[$obj->code] = $obj->label;
    }
}

// Fetch Dolibarr Workstations (llx_workstation)
$workstations = array();
$sql = "SELECT rowid, ref, name FROM " . MAIN_DB_PREFIX . "workstation WHERE status = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $workstations[$obj->rowid] = $obj->ref . ' - ' . $obj->name;
    }
}

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<table class="border centpercent">';
print '<tr><td class="titlefield required">Ref / Tank-Kode</td><td><input type="text" name="ref" value="TANK-001" required></td></tr>';
print '<tr><td class="required">Navn / Beskrivelse</td><td><input type="text" name="name" value="Gæringstank #01" required></td></tr>';

// Ressource Type fra Ordbogen (llx_c_type_resource)
print '<tr><td class="required">Ressourcetype (Fra Dolibarr Ordbog)</td><td>';
print '<select name="fk_code_type" class="flat">';
foreach ($resourceTypes as $code => $label) {
    $selected = (strpos($code, 'FERMENTER') !== false) ? ' selected' : '';
    print '<option value="' . $code . '"' . $selected . '>' . dol_escape_htmltag($label) . '</option>';
}
print '</select>';
print '</td></tr>';

// Capacity
print '<tr><td class="required">Kapacitet</td><td><input type="number" step="0.01" name="capacity_liters" value="1000.00"> Liter</td></tr>';

// Dolibarr Workstation Link
print '<tr><td>Dolibarr Workstation (Arbejdscenter)</td><td>';
if (!empty($workstations)) {
    print '<select name="fk_workstation" class="flat">';
    print '<option value="0">-- Vælg Dolibarr Workstation --</option>';
    foreach ($workstations as $wsId => $wsLabel) {
        print '<option value="' . $wsId . '">' . dol_escape_htmltag($wsLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">Ingen arbejdsstationer oprettet i Dolibarr MRP endnu.</span>';
}
print '</td></tr>';

print '</table>';

print '<div class="center" style="margin-top: 15px;">';
print '<input type="submit" class="button" value="Opret Tank i Dolibarr Ressourcer & Workstation">';
print '</div>';
print '</form>';

llxFooter();
