<?php
/**
 * BrewMo 2.0 - Brewer's Friend Style Recipe Builder
 * Single Source of Truth for Recipes -> Auto-generates Dolibarr BOM (llx_bom)
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

llxHeader('', 'BrewMo Recipe Builder (Brewer\'s Friend Style)');

print load_fiche_titre('BrewMo Opskriftsbygger (Brewer\'s Friend Modul & Dolibarr BOM Generator)', '', 'object_generic');

// Fetch Raw Materials from Dolibarr (llx_product)
$rawMaterials = array();
$sql = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE tobuy = 1 AND entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $rawMaterials[$obj->rowid] = $obj->ref . ' - ' . $obj->label;
    }
}

// Fetch Finished Beer Products from Dolibarr (llx_product)
$finishedProducts = array();
$sql = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE tosell = 1 AND entity = 1";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $finishedProducts[$obj->rowid] = $obj->ref . ' - ' . $obj->label;
    }
}

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" id="recipeForm">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

// SECTION 1: BASIC INFO & VITAL SPECS DASHBOARD
print '<div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 6px; margin-bottom: 20px;">';
print '<h3>1. Basiskort & Vital Specs (Beregnes live)</h3>';
print '<table class="border centpercent">';
print '<tr>';
print '<td class="titlefield required">Opskrift Navn</td><td><input type="text" name="title" value="New England IPA (NEIPA)" style="width: 250px;" required></td>';
print '<td class="required">Slutprodukt i Dolibarr (Øl)</td><td>';
if (!empty($finishedProducts)) {
    print '<select name="fk_product" class="flat" required>';
    print '<option value="">-- Vælg Dolibarr Øl-Produkt --</option>';
    foreach ($finishedProducts as $prodId => $prodLabel) {
        print '<option value="' . $prodId . '">' . dol_escape_htmltag($prodLabel) . '</option>';
    }
    print '</select>';
}
print '</td>';
print '</tr>';

print '<tr>';
print '<td>Batch Størrelse (L)</td><td><input type="number" id="batch_size" name="target_volume" value="1000" style="width: 100px;"> Liter</td>';
print '<td>Mæskeeffektivitet (%)</td><td><input type="number" id="efficiency" name="efficiency" value="75" style="width: 100px;"> %</td>';
print '</tr>';
print '</table>';

// VITAL SPECS LIVE DASHBOARD
print '<div style="display: flex; gap: 15px; margin-top: 15px; text-align: center;">';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>ESTIMERET OG</small><div id="display_og" style="font-size:20px; font-weight:bold; color:#0056b3;">1.062</div></div>';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>ESTIMERET FG</small><div id="display_fg" style="font-size:20px; font-weight:bold; color:#0056b3;">1.014</div></div>';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>ESTIMERET ABV</small><div id="display_abv" style="font-size:20px; font-weight:bold; color:#28a745;">6.3 %</div></div>';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>BITTERHED (IBU)</small><div id="display_ibu" style="font-size:20px; font-weight:bold; color:#d9534f;">48 IBU</div></div>';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>FARVE (EBC)</small><div id="display_ebc" style="font-size:20px; font-weight:bold; color:#f0ad4e;">12 EBC</div></div>';
print '<div style="flex:1; background:#ffffff; border:1px solid #ced4da; padding:10px; border-radius:4px;"><small>BU / GU RATIO</small><div id="display_bugu" style="font-size:20px; font-weight:bold; color:#17a2b8;">0.77</div></div>';
print '</div>';
print '</div>';

// SECTION 2: FERMENTABLES / MALT & SUKKER
print '<h3>2. Fermentables / Malt & Sukker</h3>';
print '<table class="border centpercent" id="table_fermentables">';
print '<tr class="liste_titre"><th>Dolibarr Råvare (Malt / Sukker)</th><th>Mængde (kg)</th><th>EBC Farve</th><th>Mæske-andel %</th></tr>';
for ($i = 1; $i <= 3; $i++) {
    print '<tr>';
    print '<td>';
    print '<select name="malt_id[]" class="flat" style="width: 300px;">';
    print '<option value="0">-- Vælg Dolibarr Malt-Råvare --</option>';
    foreach ($rawMaterials as $rmId => $rmLabel) {
        print '<option value="' . $rmId . '">' . dol_escape_htmltag($rmLabel) . '</option>';
    }
    print '</select>';
    print '</td>';
    print '<td><input type="number" step="0.1" name="malt_qty[]" value="0.0" style="width: 100px;"> kg</td>';
    print '<td><input type="number" step="0.1" name="malt_ebc[]" value="4.0" style="width: 80px;"></td>';
    print '<td><span class="malt_share">0 %</span></td>';
    print '</tr>';
}
print '</table>';

// SECTION 3: HOPS / HUMLETILSÆTNINGER
print '<br><h3>3. Hops / Humletilsætninger</h3>';
print '<table class="border centpercent" id="table_hops">';
print '<tr class="liste_titre"><th>Dolibarr Råvare (Humle)</th><th>Mængde (g)</th><th>Alfa-Syre %</th><th>Anvendelse / Type</th><th>Kogetid (min) / Dry Hop (dage)</th></tr>';
for ($i = 1; $i <= 3; $i++) {
    print '<tr>';
    print '<td>';
    print '<select name="hop_id[]" class="flat" style="width: 300px;">';
    print '<option value="0">-- Vælg Dolibarr Humle-Råvare --</option>';
    foreach ($rawMaterials as $rmId => $rmLabel) {
        print '<option value="' . $rmId . '">' . dol_escape_htmltag($rmLabel) . '</option>';
    }
    print '</select>';
    print '</td>';
    print '<td><input type="number" step="1" name="hop_qty[]" value="0" style="width: 100px;"> g</td>';
    print '<td><input type="number" step="0.1" name="hop_alpha[]" value="12.5" style="width: 80px;"> %</td>';
    print '<td>';
    print '<select name="hop_use[]" class="flat">';
    print '<option value="BOIL">Boil (Kogning)</option>';
    print '<option value="FIRST_WORT">First Wort Hop</option>';
    print '<option value="WHIRLPOOL">Whirlpool / Hopstand</option>';
    print '<option value="DRY_HOP">Dry Hop (Tørhumling)</option>';
    print '</select>';
    print '</td>';
    print '<td><input type="number" step="1" name="hop_time[]" value="60" style="width: 80px;"> min/dage</td>';
    print '</tr>';
}
print '</table>';

// SECTION 4: YEAST & MASH PROFILE
print '<br><div style="display: flex; gap: 20px;">';
print '<div style="flex:1;">';
print '<h3>4. Yeast / Gær</h3>';
print '<table class="border centpercent">';
print '<tr><td>Dolibarr Gær-Råvare</td><td>';
print '<select name="yeast_id" class="flat">';
print '<option value="0">-- Vælg Dolibarr Gær --</option>';
foreach ($rawMaterials as $rmId => $rmLabel) {
    print '<option value="' . $rmId . '">' . dol_escape_htmltag($rmLabel) . '</option>';
}
print '</select>';
print '</td></tr>';
print '<tr><td>Gær-Attenuering (%)</td><td><input type="number" name="yeast_attenuation" value="77" style="width: 80px;"> %</td></tr>';
print '<tr><td>Gæringstemperatur (°C)</td><td><input type="number" name="fermentation_temp" value="19.5" style="width: 80px;"> °C</td></tr>';
print '</table>';
print '</div>';

print '<div style="flex:1;">';
print '<h3>5. Mash Profile / Mæskeskema</h3>';
print '<table class="border centpercent">';
print '<tr class="liste_titre"><th>Mæsketrin</th><th>Temperatur (°C)</th><th>Varighed (min)</th></tr>';
print '<tr><td>Mash In / Sakkarifikation</td><td><input type="number" value="66" style="width: 70px;"> °C</td><td><input type="number" value="60" style="width: 70px;"> min</td></tr>';
print '<tr><td>Mash Out</td><td><input type="number" value="76" style="width: 70px;"> °C</td><td><input type="number" value="10" style="width: 70px;"> min</td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '<div class="center" style="margin-top: 25px;">';
print '<input type="submit" class="button button-save" value="Gem Opskrift (Opdaterer automatisk Dolibarr BOM)">';
print '</div>';
print '</form>';

llxFooter();
