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
$_GET['leftmenu']  = 'brewmo_batches';

if (file_exists(DOL_DOCUMENT_ROOT . '/custom/brewmo/class/brewsession.class.php')) {
    dol_include_once('/custom/brewmo/class/brewsession.class.php');
    dol_include_once('/custom/brewmo/class/recipe.class.php');
    dol_include_once('/custom/brewmo/class/brewtank.class.php');
} else {
    dol_include_once('/brewmo/class/brewsession.class.php');
    dol_include_once('/brewmo/class/recipe.class.php');
    dol_include_once('/brewmo/class/brewtank.class.php');
}

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read) && empty($user->admin)) accessforbidden();

llxHeader('', $langs->trans("BrewSessions"));

print load_fiche_titre($langs->trans("BrewSessions"));

print '<div class="tabsAction">';
if (!empty($user->rights->brewmo->write) || !empty($user->admin)) {
    print '<a class="butAction" href="brewsession_plan.php">' . $langs->trans("PlanNewBrew") . '</a>';
}
print '</div>';

$object = new BrewmoBrewSession($db);
$filter_status = GETPOST('status', 'alpha');

// Check if llx_brew_session_v2 exists, fallback to llx_brew_session
$table = MAIN_DB_PREFIX . "brew_session_v2";
$check_sql = "SHOW TABLES LIKE '" . $table . "'";
$res_check = $db->query($check_sql);
if (!$res_check || $db->num_rows($res_check) === 0) {
    $table = MAIN_DB_PREFIX . "brew_session";
}

$sql = "SELECT b.rowid, b.ref, b.title, b.state, b.planned_volume_liters, b.lot_number ";
$sql .= "FROM " . $table . " b ";
if ($filter_status !== '') {
    $sql .= "WHERE b.state = '" . $db->escape($filter_status) . "' ";
}
$sql .= "ORDER BY b.rowid DESC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Ref</td>';
    print '<td>Titel</td>';
    print '<td>Lot/Batch nr.</td>';
    print '<td>Planlagt Volumen (L)</td>';
    print '<td>Status / State</td>';
    print '</tr>';

    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td><a href="brewsession_card.php?id=' . $obj->rowid . '">' . htmlspecialchars($obj->ref) . '</a></td>';
            print '<td>' . htmlspecialchars($obj->title) . '</td>';
            print '<td>' . htmlspecialchars($obj->lot_number ?? '-') . '</td>';
            print '<td>' . htmlspecialchars($obj->planned_volume_liters ?? $obj->batch_size ?? '0') . '</td>';
            print '<td><span class="badge badge-status">' . htmlspecialchars($obj->state ?? $obj->status ?? 'DRAFT') . '</span></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="5" class="opacitymedium">Ingen brygsessioner fundet. Klik på "Planlæg nyt bryg" for at oprette en batch.</td></tr>';
    }
    print '</table>';
} else {
    print '<div class="error">Database Query Fejl: ' . htmlspecialchars($db->lasterror()) . '</div>';
}

llxFooter();
