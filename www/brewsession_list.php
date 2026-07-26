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

// Standard Dolibarr module file includes supporting both custom and core root
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

$sql = "SELECT b.rowid, b.ref, b.title, b.status, b.batch_size, b.created_at, r.title as recipe_title ";
$sql .= "FROM " . MAIN_DB_PREFIX . "brew_session b ";
$sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "brew_recipe r ON b.fk_recipe = r.rowid ";
if ($filter_status !== '') {
    $sql .= "WHERE b.status = '" . $db->escape($filter_status) . "' ";
}
$sql .= "ORDER BY b.rowid DESC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Ref</td>';
    print '<td>Title</td>';
    print '<td>Recipe</td>';
    print '<td>Volume (L)</td>';
    print '<td>Status</td>';
    print '<td>Created</td>';
    print '</tr>';

    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td><a href="brewsession_card.php?id=' . $obj->rowid . '">' . htmlspecialchars($obj->ref) . '</a></td>';
            print '<td>' . htmlspecialchars($obj->title) . '</td>';
            print '<td>' . htmlspecialchars($obj->recipe_title ?? '-') . '</td>';
            print '<td>' . htmlspecialchars($obj->batch_size) . '</td>';
            print '<td><span class="badge">' . htmlspecialchars($obj->status) . '</span></td>';
            print '<td>' . htmlspecialchars($obj->created_at) . '</td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="6" class="opacitymedium">No brew sessions found.</td></tr>';
    }
    print '</table>';
} else {
    print '<div class="error">Database Query Error: ' . htmlspecialchars($db->lasterror()) . '</div>';
}

llxFooter();
