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
if (empty($user->rights->brewmo->read)) accessforbidden();

$sessionid = GETPOSTINT('sessionid');

llxHeader('', $langs->trans("BrewmoPackagingLabels"));

print load_fiche_titre($langs->trans("BrewmoPackagingLabels"));

if ($sessionid <= 0) {
    print '<p>'.$langs->trans("SelectBrewSessionForLabels").'</p>';
    // Simple selector
    print '<form method="GET">';
    print '<input type="hidden" name="sessionid" value="">';
    print '<select name="sessionid">';
    $sql = "SELECT rowid, ref FROM ".$db->prefix()."brew_brewsession WHERE entity = ".((int)$conf->entity)." ORDER BY rowid DESC";
    $resql = $db->query($sql);
    while ($obj = $db->fetch_object($resql)) {
        print '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->ref).'</option>';
    }
    print '</select> ';
    print '<input type="submit" class="button" value="'.$langs->trans("Generate").'">';
    print '</form>';
    llxFooter();
    exit;
}

// Ensure modulepart directory
$diroutput = $conf->brewmo->dir_output.'/qrcodes';
dol_mkdir($diroutput);

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Basic QR: we skip external libs and just print URL text (you can later integrate phpqrcode)
$sql = "SELECT s.rowid as sessionid, s.ref as sessionref, r.ref as reciperef, p.rowid as packid, p.qty, p.lot";
$sql .= " FROM ".$db->prefix()."brew_brewsession as s";
$sql .= " JOIN ".$db->prefix()."brew_recipes as r ON r.rowid = s.fk_recipe";
$sql .= " JOIN ".$db->prefix()."brew_packaging as p ON p.fk_session = s.rowid";
$sql .= " WHERE s.rowid = ".(int)$sessionid;

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);

print '<div class="brewo-label-sheet">';

while ($obj = $db->fetch_object($resql)) {
    $targeturl = dol_buildpath('/brewmo/www/brewsession_card.php', 2).'?id='.$obj->sessionid;

    print '<div class="brewo-label" style="display:inline-block;border:1px solid #ccc;padding:4mm;margin:2mm;width:60mm;height:40mm;">';
    print '<strong>'.dol_escape_htmltag($obj->reciperef).'</strong><br>';
    print dol_escape_htmltag($obj->sessionref).'<br>';
    if (!empty($obj->lot)) {
        print 'Lot: '.dol_escape_htmltag($obj->lot).'<br>';
    }
    print 'Qty: '.price($obj->qty, 0).' stk<br>';
    print '<small>'.$langs->trans("ScanUrl").': '.dol_escape_htmltag($targeturl).'</small>';
    print '</div>';
}

print '</div>';

llxFooter();
