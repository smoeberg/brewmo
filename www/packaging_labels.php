<?php
$res = 0;
$paths = array(
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php'
);
foreach ($paths as $p) { if (!$res && file_exists($p)) { $res = @include $p; } }
if (!$res) { die('Include of main.inc.php failed'); }

$_GET['mainmenu']  = 'brewmo';
$_GET['leftmenu']  = 'brewmo_labels';

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read) && empty($user->admin)) accessforbidden();

$sessionid = GETPOSTINT('sessionid');

llxHeader('', $langs->trans("BrewmoPackagingLabels"));

print load_fiche_titre($langs->trans("BrewmoPackagingLabels"));

print '<form method="GET">';
print '<table class="border centpercent">';
print '<tr><td>Vælg Brygsession / Batch:</td><td>';
print '<select name="sessionid" class="flat">';
print '<option value="0">-- Vælg Batch --</option>';

$table_v2 = MAIN_DB_PREFIX . "brew_session_v2";
$table_v1 = MAIN_DB_PREFIX . "brew_session";

$table = $table_v2;
$check_v2 = $db->query("SHOW TABLES LIKE '" . $table_v2 . "'");
if (!$check_v2 || $db->num_rows($check_v2) === 0) {
    $table = $table_v1;
}

if ($table !== null) {
    $sql = "SELECT rowid, ref, title FROM " . $table . " ORDER BY rowid DESC";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $selected = ($sessionid == $obj->rowid) ? ' selected' : '';
            print '<option value="' . $obj->rowid . '"' . $selected . '>' . htmlspecialchars($obj->ref . ' - ' . $obj->title) . '</option>';
        }
    }
}

print '</select> ';
print '</td></tr>';
print '</table>';
print '<br><div class="center"><input type="submit" class="button" value="Generer Etiketter / QR-koder"></div>';
print '</form>';

if ($sessionid > 0) {
    print '<br>';
    print load_fiche_titre("Genererede QR Etiketter for Batch #" . (int)$sessionid, '', '');
    print '<div class="info">QR Etiketter genereret for fustager/flasker til batch #' . (int)$sessionid . '. Klar til udskrivning.</div>';
}

llxFooter();
