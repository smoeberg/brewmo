<?php
// ======================================================================
// BREWMO • Udstyr (create/update + liste)
// Filtype: Standalone Dolibarr page
// ======================================================================
$res=0;
$paths=array(
    __DIR__.'/../../main.inc.php',
    __DIR__.'/../../../main.inc.php',
    __DIR__.'/../../../../main.inc.php',
    __DIR__.'/../main.inc.php',
    __DIR__.'/main.inc.php'
);
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }

global $db,$user,$conf,$langs;

dol_include_once('/brewmo/class/equipment.class.php');

$langs->load('brewmo@brewmo');

if (empty($user->rights->brewmo->admin)) accessforbidden();

// ---------------------------------------------------------------------
// Init / inputs
// ---------------------------------------------------------------------
$action = GETPOST('action','alpha');
$token  = newToken();
$id     = (int) GETPOST('id','int');

$eq = new BrewmoEquipment($db);
if ($id > 0) $eq->fetch($id);

// Hjælper: læs tal som “price/float” og rens dem
function _num($key){
    $raw = GETPOST($key, 'alphanohtml');
    return price2num($raw, 'MU'); // MU = keep decimal marker according to conf
}

// ---------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------
if (GETPOST('token','alpha') == $_SESSION['newtoken'] && $action === 'save') {
    // Tekstfelter
    foreach (array('ref','label','note') as $k) { $eq->$k = GETPOST($k, 'alphanohtml'); }

    // Numeriske felter – normaliser med price2num
    $eq->efficiency_pct     = _num('efficiency_pct');
    $eq->boiloff_l_h        = _num('boiloff_l_h');
    $eq->trub_loss_l        = _num('trub_loss_l');
    $eq->lauter_deadspace_l = _num('lauter_deadspace_l');
    $eq->mash_tun_l         = _num('mash_tun_l');
    $eq->kettle_l           = _num('kettle_l');

    // Kritisk: sørg for korrekt entity, ellers ses posten ikke i listen
    $eq->entity  = (int) $conf->entity;
    $eq->enabled = GETPOST('enabled','int') ? 1 : 0;

    if (!empty($eq->id)) {
        $resu = $eq->update($user);
        if ($resu > 0) {
            setEventMessages($langs->trans('RecordModified'), null, 'mesgs');
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.$eq->id); exit;
        } else {
            setEventMessages($langs->trans('Error').' '.$eq->error, $eq->errors, 'errors');
        }
    } else {
        $idnew = $eq->create($user);
        if ($idnew > 0) {
            $eq->id = $idnew; // sørg for id på objektet til redirect
            setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.$eq->id); exit;
        } else {
            setEventMessages($langs->trans('Error').' '.$eq->error, $eq->errors, 'errors');
        }
    }
}

if (GETPOST('token','alpha') == $_SESSION['newtoken'] && $action === 'delete' && $eq->id > 0) {
    $resu = $eq->delete($user);
    if ($resu > 0) {
        setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    } else {
        setEventMessages($langs->trans('Error').' '.$eq->error, $eq->errors, 'errors');
    }
}

// ---------------------------------------------------------------------
// View
// ---------------------------------------------------------------------
llxHeader('', $langs->trans('Produktionsanlæg'));
print load_fiche_titre($langs->trans('Produktionsanlæg'));

// Evt. advarsel hvis der findes poster i andre entity'er (hjælp til fejlfinding)
$sqlChk = "SELECT COUNT(*) as n FROM ".MAIN_DB_PREFIX."brew_equipment WHERE entity <> ".((int)$conf->entity)." OR entity IS NULL";
$rChk = $db->query($sqlChk);
if ($rChk) {
    $o = $db->fetch_object($rChk);
    if (!empty($o->n)) {
        print '<div class="warning">'.$langs->trans('Warning').': '.$o->n.' '.$langs->trans('recordsOutsideCurrentEntity').'</div>';
    }
}

// Formular
print '<form method="post" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="token" value="'.$token.'">';
print '<input type="hidden" name="action" value="save">';
print '<input type="hidden" name="id" value="'.(!empty($eq->id)?(int)$eq->id:'').'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans('Felt').'</td><td>'.$langs->trans('Værdi').'</td></tr>';

print '<tr><td>'.$langs->trans('Ref').'</td><td><input type="text" name="ref" value="'.dol_escape_htmltag($eq->ref).'"></td></tr>';
print '<tr><td>'.$langs->trans('Etiket').'</td><td><input type="text" name="label" value="'.dol_escape_htmltag($eq->label).'"></td></tr>';

print '<tr><td>'.$langs->trans('EffektivitetPct').'</td><td><input type="number" step="0.1" name="efficiency_pct" value="'.(($eq->efficiency_pct!==null && $eq->efficiency_pct!=='')?dol_escape_htmltag(price($eq->efficiency_pct)):'').'"> %</td></tr>';
print '<tr><td>'.$langs->trans('KogeFordampLPrTime').'</td><td><input type="number" step="0.1" name="boiloff_l_h" value="'.(($eq->boiloff_l_h!==null && $eq->boiloff_l_h!=='')?dol_escape_htmltag(price($eq->boiloff_l_h)):'').'"> L/h</td></tr>';
print '<tr><td>'.$langs->trans('TrubTabL').'</td><td><input type="number" step="0.1" name="trub_loss_l" value="'.(($eq->trub_loss_l!==null && $eq->trub_loss_l!=='')?dol_escape_htmltag(price($eq->trub_loss_l)):'').'"> L</td></tr>';
print '<tr><td>'.$langs->trans('LauterDødzoneL').'</td><td><input type="number" step="0.1" name="lauter_deadspace_l" value="'.(($eq->lauter_deadspace_l!==null && $eq->lauter_deadspace_l!=='')?dol_escape_htmltag(price($eq->lauter_deadspace_l)):'').'"> L</td></tr>';
print '<tr><td>'.$langs->trans('MæskekarVolumenL').'</td><td><input type="number" step="0.1" name="mash_tun_l" value="'.(($eq->mash_tun_l!==null && $eq->mash_tun_l!=='')?dol_escape_htmltag(price($eq->mash_tun_l)):'').'"> L</td></tr>';
print '<tr><td>'.$langs->trans('KedelVolumenL').'</td><td><input type="number" step="0.1" name="kettle_l" value="'.(($eq->kettle_l!==null && $eq->kettle_l!=='')?dol_escape_htmltag(price($eq->kettle_l)):'').'"> L</td></tr>';

print '<tr><td>'.$langs->trans('Aktiv').'</td><td><input type="checkbox" name="enabled" value="1"'.(!empty($eq->enabled)?' checked':'').'></td></tr>';
print '<tr><td>'.$langs->trans('Note').'</td><td><input type="text" name="note" style="width:60%" value="'.dol_escape_htmltag($eq->note).'"></td></tr>';

print '</table>';

print '<div class="center">';
print '<input class="button" type="submit" value="'.$langs->trans(!empty($eq->id)?'Gem':'Opret').'">';
if (!empty($eq->id)) {
    $del = $_SERVER['PHP_SELF'].'?id='.(int)$eq->id.'&action=delete&token='.$token;
    print ' <a class="butActionDelete" href="'.dol_escape_htmltag($del).'">× '.$langs->trans('Slet').'</a>';
}
print '</div>';

print '</form>';

// ---------------------------------------------------------------------
// Liste
// ---------------------------------------------------------------------
print '<h3>'.$langs->trans('Liste').'</h3>';
$sql = "SELECT rowid, ref, label, efficiency_pct, enabled 
        FROM ".MAIN_DB_PREFIX."brew_equipment 
        WHERE entity = ".((int)$conf->entity)."
        ORDER BY rowid DESC";
$resql = $db->query($sql);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Ref').'</td>';
print '<td>'.$langs->trans('Etiket').'</td>';
print '<td>'.$langs->trans('EffektivitetPct').'</td>';
print '<td>'.$langs->trans('Aktiv').'</td>';
print '<td class="right">'.$langs->trans('Handling').'</td>';
print '</tr>';

if ($resql) {
    while ($o = $db->fetch_object($resql)) {
        $u = $_SERVER['PHP_SELF'].'?id='.(int)$o->rowid;
        print '<tr>';
        print '<td><a href="'.dol_escape_htmltag($u).'">'.dol_escape_htmltag($o->ref).'</a></td>';
        print '<td>'.dol_escape_htmltag($o->label).'</td>';
        print '<td>'.(($o->efficiency_pct!==null && $o->efficiency_pct!=='')?dol_escape_htmltag(price($o->efficiency_pct)).' %':'-').'</td>';
        print '<td>'.($o->enabled ? $langs->trans('Ja') : $langs->trans('Nej')).'</td>';
        print '<td class="right"><a class="butAction" href="'.dol_escape_htmltag($u).'">'.$langs->trans('Åbn').'</a></td>';
        print '</tr>';
    }
} else {
    setEventMessages($db->lasterror(), null, 'errors');
}
print '</table>';

llxFooter();
