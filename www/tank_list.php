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
$_GET['leftmenu']  = 'brewmo_tanks';

if (file_exists(DOL_DOCUMENT_ROOT . '/custom/brewmo/class/brewtank.class.php')) {
    dol_include_once('/custom/brewmo/class/brewtank.class.php');
} else {
    dol_include_once('/brewmo/class/brewtank.class.php');
}

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read) && empty($user->admin)) accessforbidden();

llxHeader('', $langs->trans("BrewmoTanks"));

print load_fiche_titre($langs->trans("BrewmoTanks"));

print '<div class="tabsAction">';
if (!empty($user->rights->brewmo->write) || !empty($user->admin)) {
    print '<a class="butAction" href="tank_card.php?action=create">' . $langs->trans("NewTank") . '</a>';
}
print '</div>';

$table_v2 = MAIN_DB_PREFIX . "brew_vessel";
$table_v1 = MAIN_DB_PREFIX . "brew_tank";

$table = $table_v2;
$check_v2 = $db->query("SHOW TABLES LIKE '" . $table_v2 . "'");
if (!$check_v2 || $db->num_rows($check_v2) === 0) {
    $check_v1 = $db->query("SHOW TABLES LIKE '" . $table_v1 . "'");
    if ($check_v1 && $db->num_rows($check_v1) > 0) {
        $table = $table_v1;
    } else {
        $table = null;
    }
}

if ($table === null) {
    print '<div class="warning"><strong>BrewMo tanktabeller mangler i databasen:</strong><br>';
    print 'Gå venligst til <strong>Opsætning → Moduler</strong> i Dolibarr, slå <strong>BrewMo</strong> fra og aktiver det igen for at oprette tabellerne.</div>';
    llxFooter();
    exit;
}

if ($table === $table_v2) {
    $sql = "SELECT rowid, ref, name as label, capacity_liters as capacity_l, type as tank_type, is_clean, is_occupied ";
    $sql .= "FROM " . $table . " ";
    $sql .= "ORDER BY ref ASC";
} else {
    $sql = "SELECT rowid, ref, label, capacity_l, tank_type, location, is_active ";
    $sql .= "FROM " . $table . " ";
    $sql .= "ORDER BY ref ASC";
}

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Ref</td>';
    print '<td>Navn / Label</td>';
    print '<td>Kapacitet (L)</td>';
    print '<td>Tank Type</td>';
    print '<td>Rengørings-status</td>';
    print '<td>Optaget / Status</td>';
    print '</tr>';

    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td><a href="tank_card.php?id=' . $obj->rowid . '">' . htmlspecialchars($obj->ref) . '</a></td>';
            print '<td>' . htmlspecialchars($obj->label) . '</td>';
            print '<td>' . htmlspecialchars($obj->capacity_l ?? '0') . '</td>';
            print '<td>' . htmlspecialchars($obj->tank_type ?? 'FERMENTER') . '</td>';
            
            // Clean status badge
            if (isset($obj->is_clean)) {
                $clean_badge = $obj->is_clean ? '<span class="badge badge-status-4">Clean (CIP OK)</span>' : '<span class="badge badge-status-8">Dirty (Requires CIP)</span>';
            } else {
                $clean_badge = '-';
            }
            print '<td>' . $clean_badge . '</td>';

            // Occupied badge
            if (isset($obj->is_occupied)) {
                $occ_badge = $obj->is_occupied ? '<span class="badge badge-status-8">Occupied</span>' : '<span class="badge badge-status-4">Available</span>';
            } else {
                $occ_badge = '-';
            }
            print '<td>' . $occ_badge . '</td>';

            print '</tr>';
        }
    } else {
        print '<tr><td colspan="6" class="opacitymedium">Ingen tanke eller fermentorer fundet. Klik på "Ny tank" for at oprette en.</td></tr>';
    }
    print '</table>';
} else {
    print '<div class="error">Database Query Fejl: ' . htmlspecialchars($db->lasterror()) . '</div>';
}

llxFooter();
