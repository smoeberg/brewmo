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
dol_include_once('/brewmo/class/vessel.class.php');
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->admin)) accessforbidden();
$action=GETPOST('action','alpha'); $token=newToken(); $id=(int)GETPOST('id','int');
$v=new BrewmoVessel($db); if ($id>0) $v->fetch($id);

if (GETPOST('token','alpha')==$_SESSION['newtoken'] && $action=='save'){
    foreach(array('ref','label','type','location','note') as $k){ $v->$k=GETPOST($k,'alpha'); }
    $v->capacity_l=GETPOST('capacity_l','alpha'); $v->enabled=GETPOST('enabled','int')?1:0; $v->status=GETPOST('status','alpha')?:$v->status;
    if ($v->id) $v->update($user); else $id=$v->create($user);
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.($v->id?:$id)); exit;
}
if (GETPOST('token','alpha')==$_SESSION['newtoken'] && $action=='setstatus' && $v->id>0){
    $new=GETPOST('newstatus','alpha'); $note=GETPOST('stnote','alpha');
    $db->begin();
    $ok1=$db->query("UPDATE ".MAIN_DB_PREFIX."brew_vessels SET status='".$db->escape($new)."' WHERE rowid=".(int)$v->id);
    $ok2=$db->query("INSERT INTO ".MAIN_DB_PREFIX."brew_vessel_status_log(entity,fk_vessel,status,note,changed_at) VALUES(".(int)$conf->entity.",".(int)$v->id.",'".$db->escape($new)."','".$db->escape($note)."',NOW())");
    if($ok1 && $ok2){ $db->commit(); $v->status=$new; setEventMessages($langs->trans("StatusOpdateret"),null,'mesgs'); } else { $db->rollback(); setEventMessages($langs->trans("FejlStatusOpdatering"),null,'errors'); }
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$v->id); exit;
}
if (GETPOST('token','alpha')==$_SESSION['newtoken'] && $action=='delete' && $v->id>0){
    $v->delete($user); header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', $langs->trans('Gærtanke'));
print load_fiche_titre($langs->trans('Gærtanke'));

print '<form method="post"><input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.($v->id?:'').'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans('Felt').'</td><td>'.$langs->trans('Værdi').'</td></tr>';
print '<tr><td>'.$langs->trans('Ref').'</td><td><input type="text" name="ref" value="'.dol_escape_htmltag($v->ref).'"></td></tr>';
print '<tr><td>'.$langs->trans('Etiket').'</td><td><input type="text" name="label" value="'.dol_escape_htmltag($v->label).'"></td></tr>';
print '<tr><td>'.$langs->trans('KapacitetL').'</td><td><input type="number" step="0.1" name="capacity_l" value="'.($v->capacity_l!==null?$v->capacity_l:'').'"> L</td></tr>';
print '<tr><td>'.$langs->trans('Type').'</td><td><select name="type"><option value="fermenter"'.($v->type=='fermenter'?' selected':'').'>'.$langs->trans('Fermenter').'</option><option value="brite"'.($v->type=='brite'?' selected':'').'>'.$langs->trans('Brite').'</option><option value="lagering"'.($v->type=='lagering'?' selected':'').'>'.$langs->trans('Lagering').'</option></select></td></tr>';
print '<tr><td>'.$langs->trans('Placering').'</td><td><input type="text" name="location" value="'.dol_escape_htmltag($v->location).'"></td></tr>';
print '<tr><td>'.$langs->trans('Status').'</td><td><select name="status"><option value="available"'.($v->status=='available'?' selected':'').'>'.$langs->trans('Ledig').'</option><option value="in_use"'.($v->status=='in_use'?' selected':'').'>'.$langs->trans('Optaget').'</option><option value="cleaning"'.($v->status=='cleaning'?' selected':'').'>'.$langs->trans('Rengøring').'</option><option value="service"'.($v->status=='service'?' selected':'').'>'.$langs->trans('Service').'</option></select></td></tr>';
print '<tr><td>'.$langs->trans('Aktiv').'</td><td><input type="checkbox" name="enabled" value="1"'.($v->enabled?' checked':'').'></td></tr>';
print '<tr><td>'.$langs->trans('Note').'</td><td><input type="text" name="note" style="width:60%" value="'.dol_escape_htmltag($v->note).'"></td></tr>';
print '</table>';
print '<div class="center"><input class="button" type="submit" value="'.$langs->trans($v->id?'Gem':'Opret').'">';
if ($v->id){ $del=$_SERVER['PHP_SELF'].'?id='.$v->id.'&action=delete&token='.$token; print ' <a class="butActionDelete" href="'.$del.'">× '.$langs->trans('Slet').'</a>'; }
print '</div></form>';

if ($v->id){
    print '<h3>'.$langs->trans('OpdaterStatus').'</h3>';
    print '<form method="post" style="margin-bottom:12px"><input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="setstatus"><input type="hidden" name="id" value="'.$v->id.'">';
    print '<select name="newstatus"><option value="available">'.$langs->trans('Ledig').'</option><option value="in_use">'.$langs->trans('Optaget').'</option><option value="cleaning">'.$langs->trans('Rengøring').'</option><option value="service">'.$langs->trans('Service').'</option></select> ';
    print '<input type="text" name="stnote" placeholder="'.$langs->trans('Note').'" style="width:240px"> ';
    print '<input class="button" type="submit" value="'.$langs->trans('Gem').'">';
    print '</form>';

    // status log
    $res=$db->query("SELECT status,note,changed_at FROM ".MAIN_DB_PREFIX."brew_vessel_status_log WHERE fk_vessel=".(int)$v->id." ORDER BY changed_at DESC");
    print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans('Status').'</td><td>'.$langs->trans('Note').'</td><td>'.$langs->trans('Dato').'</td></tr>';
    if ($res) while($o=$db->fetch_object($res)){ print '<tr><td>'.dol_escape_htmltag($o->status).'</td><td>'.dol_escape_htmltag($o->note).'</td><td>'.$o->changed_at.'</td></tr>'; }
    print '</table>';
}

$res=$db->query("SELECT rowid,ref,label,capacity_l,type,enabled,status FROM ".MAIN_DB_PREFIX."brew_vessels WHERE entity=".(int)$conf->entity." ORDER BY rowid DESC");
print '<h3>'.$langs->trans('Liste').'</h3>';
print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Etiket').'</td><td>'.$langs->trans('KapacitetL').'</td><td>'.$langs->trans('Type').'</td><td>'.$langs->trans('Status').'</td><td>'.$langs->trans('Aktiv').'</td><td class="right">'.$langs->trans('Handling').'</td></tr>';
if ($res) while($o=$db->fetch_object($res)){ $u=$_SERVER['PHP_SELF'].'?id='.$o->rowid; print '<tr><td><a href="'.$u.'">'.dol_escape_htmltag($o->ref).'</a></td><td>'.dol_escape_htmltag($o->label).'</td><td>'.($o->capacity_l!==null?$o->capacity_l:'-').'</td><td>'.$langs->trans(ucfirst($o->type)).'</td><td>'.dol_escape_htmltag($o->status?:'-').'</td><td>'.($o->enabled?$langs->trans('Ja'):$langs->trans('Nej')).'</td><td class="right"><a class="butAction" href="'.$u.'">'.$langs->trans('Åbn').'</a></td></tr>'; }
llxFooter();
