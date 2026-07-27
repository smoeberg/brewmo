<?php
/**
 * BrewMo 2.0 - BrewSession Card (Integrated with Dolibarr MRP MO llx_mrp_mo & Agenda llx_actioncomm)
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

// Handle Save Action (Creates BrewSession, Dolibarr MO and Agenda Event)
if ($action === 'save') {
    $ref          = GETPOST('ref', 'alpha');
    $title        = GETPOST('title', 'restreint');
    $fk_recipe    = GETPOST('fk_recipe', 'int');
    $fk_resource  = GETPOST('fk_resource', 'int');
    $volume       = GETPOST('planned_volume', 'int');
    $date_start   = GETPOST('date_start', 'alpha');

    if (!empty($ref) && !empty($title)) {
        // 1. Insert into BrewMo v2 Session Table
        $sqlSession = "INSERT INTO " . MAIN_DB_PREFIX . "brew_session_v2 (entity, ref, title, fk_recipe, fk_vessel, state, planned_volume_liters, lot_number, datec) ";
        $sqlSession .= "VALUES (1, '" . $db->escape($ref) . "', '" . $db->escape($title) . "', " . (int)$fk_recipe . ", " . (int)$fk_resource . ", 'PLANNED', " . (float)$volume . ", 'LOT-" . date('Ymd-His') . "', NOW())";
        
        $resql = $db->query($sqlSession);
        if ($resql) {
            $sessionId = $db->last_insert_id(MAIN_DB_PREFIX . "brew_session_v2");

            // 2. Create corresponding Dolibarr Manufacturing Order (llx_mrp_mo)
            $sqlMo = "INSERT INTO " . MAIN_DB_PREFIX . "mrp_mo (entity, ref, label, status, qty, datec) ";
            $sqlMo .= "VALUES (1, 'MO-" . $db->escape($ref) . "', '" . $db->escape("Bryg: " . $title) . "', 0, " . (float)$volume . ", NOW())";
            $db->query($sqlMo);

            // 3. Book Event in Dolibarr Agenda / Kalender (llx_actioncomm)
            $sqlAgenda = "INSERT INTO " . MAIN_DB_PREFIX . "actioncomm (entity, ref_ext, label, datep, fk_element, elementtype, datec) ";
            $sqlAgenda .= "VALUES (1, '" . $db->escape($ref) . "', '" . $db->escape("Brygning: " . $title . " (" . $volume . "L)") . "', NOW(), " . $sessionId . ", 'brewsession', NOW())";
            $db->query($sqlAgenda);

            setEventMessages("Brygsession oprettet! Der er automatisk genereret en Dolibarr Manufacturing Order (MO) og booket tid i Dolibarr Agenda/Kalenderen.", null, 'mesgs');
            header("Location: brewsession_list.php");
            exit;
        } else {
            setEventMessages("Fejl ved oprettelse af brygsession: " . $db->lasterror(), null, 'errors');
        }
    }
}

llxHeader('', 'BrewMo Brygsession Card');

print load_fiche_titre('BrewMo Brygsession (Opretter Dolibarr MO & Kalender-Booking)', '', 'object_generic');

// Fetch BrewMo Recipes
$recipes = array();
$sql = "SELECT rowid, ref, title FROM " . MAIN_DB_PREFIX . "brew_recipe WHERE entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $recipes[$obj->rowid] = $obj->ref . ' - ' . $obj->title;
    }
}

// Fetch Dolibarr Resources (Tanke / Udskænkningstanke) from llx_resource
$resources = array();
$sql = "SELECT rowid, ref, description FROM " . MAIN_DB_PREFIX . "resource WHERE entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $resources[$obj->rowid] = $obj->ref . ($obj->description ? ' (' . $obj->description . ')' : '');
    }
}

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<table class="border centpercent">';
print '<tr><td class="titlefield required">Bryg Batch-Kode / Ref</td><td><input type="text" name="ref" value="BATCH-' . date('Ymd-01') . '" required></td></tr>';
print '<tr><td class="required">Titel / Brygnavn</td><td><input type="text" name="title" value="Midsommer IPA - Batch #1" required></td></tr>';

// Recipe Select
print '<tr><td class="required">Bryg-Opskrift (Recipe)</td><td>';
if (!empty($recipes)) {
    print '<select name="fk_recipe" class="flat" required>';
    print '<option value="">-- Vælg Opskrift --</option>';
    foreach ($recipes as $recId => $recLabel) {
        print '<option value="' . $recId . '">' . dol_escape_htmltag($recLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">Ingen opskrifter oprettet endnu. <a href="recipe_card.php">Opret en opskrift her</a>.</span>';
}
print '</td></tr>';

// Tank / Resource Select
print '<tr><td class="required">Tildelt Tank / Ressource (Dolibarr Resource)</td><td>';
if (!empty($resources)) {
    print '<select name="fk_resource" class="flat" required>';
    print '<option value="">-- Vælg Tank / Gæringstank --</option>';
    foreach ($resources as $resId => $resLabel) {
        print '<option value="' . $resId . '">' . dol_escape_htmltag($resLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">Ingen tanke oprettet i Dolibarr ressourcer endnu. <a href="tank_card.php">Opret tank her</a>.</span>';
}
print '</td></tr>';

print '<tr><td class="required">Planlagt Volumen (Liter)</td><td><input type="number" step="1" name="planned_volume" value="1000" required> Liter</td></tr>';
print '<tr><td>Planlagt Brygdato</td><td><input type="date" name="date_start" value="' . date('Y-m-d') . '"></td></tr>';
print '</table>';

print '<div class="center" style="margin-top: 20px;">';
print '<input type="submit" class="button button-save" value="Igangsæt Brygsession (Opretter MO & Kalender-aftale i Dolibarr)">';
print '</div>';
print '</form>';

llxFooter();
