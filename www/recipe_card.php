<?php
/**
 * BrewMo 2.0 - Recipe Card (Integrated with Dolibarr BOM llx_bom & Raw Material Products llx_product)
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

llxHeader('', 'BrewMo Opskrift / Dolibarr BOM');

print load_fiche_titre('BrewMo Opskrift (Tilknyttet Dolibarr Stykliste / BOM & Råvarer)', '', 'object_generic');

// Fetch Raw Material Products from Dolibarr (llx_product) where finished=0 (Raw material)
$rawMaterials = array();
$sql = "SELECT rowid, ref, label, price FROM " . MAIN_DB_PREFIX . "product WHERE tobuy = 1 AND entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $rawMaterials[$obj->rowid] = $obj->ref . ' - ' . $obj->label;
    }
}

// Fetch Finished Beer Products from Dolibarr (llx_product) where tosell=1
$finishedProducts = array();
$sql = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE tosell = 1 AND entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $finishedProducts[$obj->rowid] = $obj->ref . ' - ' . $obj->label;
    }
}

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<table class="border centpercent">';
print '<tr><td class="titlefield required">Opskrift Kode / Ref</td><td><input type="text" name="ref" value="REC-IPA-01" required></td></tr>';
print '<tr><td class="required">Opskrift Navn / Label</td><td><input type="text" name="title" value="India Pale Ale (IPA) 1000L" required></td></tr>';

// Link to Dolibarr Finished Product (Slutprodukt / Øl)
print '<tr><td class="required">Slutprodukt i Dolibarr (Færdigvare / Øl)</td><td>';
if (!empty($finishedProducts)) {
    print '<select name="fk_product" class="flat" required>';
    print '<option value="">-- Vælg Dolibarr Færdigvare-Øl --</option>';
    foreach ($finishedProducts as $prodId => $prodLabel) {
        print '<option value="' . $prodId . '">' . dol_escape_htmltag($prodLabel) . '</option>';
    }
    print '</select>';
} else {
    print '<span class="opacitymedium">Ingen salgsaktive produkter oprettet i Dolibarr Varekatalog endnu.</span>';
}
print '</td></tr>';

// Technical Specs
print '<tr><td>Target Batch Volume</td><td><input type="number" step="0.1" name="target_volume" value="1000"> Liter</td></tr>';
print '<tr><td>Original Gravity (OG)</td><td><input type="number" step="0.001" name="og" value="1.058"></td></tr>';
print '<tr><td>Final Gravity (FG)</td><td><input type="number" step="0.001" name="fg" value="1.012"></td></tr>';
print '<tr><td>IBU (Bitterhed)</td><td><input type="number" step="0.1" name="ibu" value="55.0"></td></tr>';
print '<tr><td>EBC (Farve)</td><td><input type="number" step="0.1" name="ebc" value="14.0"></td></tr>';
print '</table>';

print '<br><h3>Råvarer & Ingredienser (Hentet direkte fra Dolibarr Råvarekatalog)</h3>';
print '<table class="border centpercent">';
print '<tr class="liste_titre"><th>Dolibarr Råvare (Malt, Humle, Gær)</th><th>Type</th><th>Mængde pr. Batch</th><th>Enhed</th></tr>';

// 3 Sample Raw Material Rows
for ($i = 1; $i <= 3; $i++) {
    print '<tr>';
    print '<td>';
    if (!empty($rawMaterials)) {
        print '<select name="ingredient_product_id[]" class="flat">';
        print '<option value="0">-- Vælg Dolibarr Råvare --</option>';
        foreach ($rawMaterials as $rmId => $rmLabel) {
            print '<option value="' . $rmId . '">' . dol_escape_htmltag($rmLabel) . '</option>';
        }
        print '</select>';
    } else {
        print '<span class="opacitymedium">Ingen råvarer oprettet i Dolibarr endnu.</span>';
    }
    print '</td>';
    print '<td>';
    print '<select name="ingredient_type[]" class="flat">';
    print '<option value="MALT">Malt</option>';
    print '<option value="HOPS">Humle</option>';
    print '<option value="YEAST">Gær</option>';
    print '<option value="WATER">Vandbehandling</option>';
    print '</select>';
    print '</td>';
    print '<td><input type="number" step="0.01" name="ingredient_qty[]" value="0.00"></td>';
    print '<td><select name="ingredient_unit[]" class="flat"><option value="kg">kg</option><option value="g">g</option><option value="l">L</option></select></td>';
    print '</tr>';
}

print '</table>';

print '<div class="center" style="margin-top: 15px;">';
print '<input type="submit" class="button" value="Gem Opskrift som Dolibarr Stykliste (BOM)">';
print '</div>';
print '</form>';

llxFooter();
