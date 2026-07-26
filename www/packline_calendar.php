<?php
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
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();
$action=GETPOST('action','alpha'); $token=newToken();

// Quick create booking
if (!empty($user->rights->brewmo->write) && $action=='create' && GETPOST('token','alpha')==$_SESSION['newtoken']){
    $line=(int)GETPOST('fk_packaging_line','int');
    $title=GETPOST('title','alpha');
    $start=GETPOST('start_datetime','alpha');
    $minutes=(int)GETPOST('minutes','int');
    $note=GETPOST('note','alpha');
    $db->query("INSERT INTO ".MAIN_DB_PREFIX."brew_packline_bookings(entity,fk_packaging_line,title,start_datetime,end_datetime,note,status) VALUES (".(int)$conf->entity.",".$line.",'".$db->escape($title)."','".$db->escape($start)."',DATE_ADD('".$db->escape($start)."', INTERVAL ".$minutes." MINUTE),'".$db->escape($note)."','planned')");
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', $langs->trans("FlaskelinjeKalender"));
print load_fiche_titre($langs->trans("FlaskelinjeKalender"));

// Quick form
if (!empty($user->rights->brewmo->write')){
    print '<form method="post" class="border" style="padding:8px;margin-bottom:12px">';
    print '<input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="create">';
    print '<strong>'.$langs->trans("NyBooking").'</strong> ';
    print '<select name="fk_packaging_line">';
    $res=$db->query("SELECT rowid,ref,label FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE entity=".(int)$conf->entity." AND enabled=1 ORDER BY rowid DESC");
    if ($res) while($o=$db->fetch_object($res)){ print '<option value="'.$o->rowid.'">'.dol_escape_htmltag($o->ref.' - '.$o->label).'</option>'; }
    print '</select> ';
    print '<input type="text" name="title" placeholder="'.$langs->trans("Titel").'" style="width:180px"> ';
    print '<input type="datetime-local" name="start_datetime"> ';
    print '<input type="number" name="minutes" min="1" value="60" style="width:80px"> '.$langs->trans("min").' ';
    print '<input type="text" name="note" placeholder="'.$langs->trans("Note").'" style="width:200px"> ';
    print '<input class="button" type="submit" value="'.$langs->trans("Opret").'">';
    print '</form>';
}

// List upcoming bookings
$from=date('Y-m-d 00:00:00'); $to=date('Y-m-d 23:59:59', strtotime('+14 days'));
$sql="SELECT b.rowid,b.title,b.start_datetime,b.end_datetime,b.status,b.fk_packaging_line,l.ref as lineref,l.label as linelabel
      FROM ".MAIN_DB_PREFIX."brew_packline_bookings b
      LEFT JOIN ".MAIN_DB_PREFIX."brew_packaging_lines l ON l.rowid=b.fk_packaging_line
      WHERE b.entity=".(int)$conf->entity." AND b.start_datetime >= '".$db->escape($from)."' AND b.start_datetime <= '".$db->escape($to)."'
      ORDER BY b.start_datetime ASC";
$res=$db->query($sql);

print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans("Dato").'</td><td>'.$langs->trans("Start").'</td><td>'.$langs->trans("Slut").'</td><td>'.$langs->trans("Linje").'</td><td>'.$langs->trans("Titel").'</td><td>'.$langs->trans("Status").'</td></tr>';
if ($res) while($o=$db->fetch_object($res)){
    $d=date('Y-m-d', strtotime($o->start_datetime));
    $s=date('H:i', strtotime($o->start_datetime));
    $e=date('H:i', strtotime($o->end_datetime));
    print '<tr><td>'.$d.'</td><td>'.$s.'</td><td>'.$e.'</td><td>'.dol_escape_htmltag($o->lineref.' - '.$o->linelabel).'</td><td>'.dol_escape_htmltag($o->title).'</td><td>'.dol_escape_htmltag($o->status).'</td></tr>';
}
print '</table>';

llxFooter();
