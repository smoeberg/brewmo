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
dol_include_once('/brewmo/class/packline.class.php');
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->admin)) accessforbidden();
$action=GETPOST('action','alpha'); $token=newToken(); $id=(int)GETPOST('id','int');
$p=new BrewmoPackLine($db); if ($id>0) $p->fetch($id);

if (GETPOST('token','alpha')==$_SESSION['newtoken'] && $action=='save'){
    foreach(array('ref','label','line_type','note') as $k){ $p->$k=GETPOST($k,'alpha'); }
    $p->units_per_hour=GETPOST('units_per_hour','alpha'); $p->enabled=GETPOST('enabled','int')?1:0;
    if ($p->id) $p->update($user); else $id=$p->create($user);
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.($p->id?:$id)); exit;
}
if (GETPOST('token','alpha')==$_SESSION['newtoken'] && $action=='delete' && $p->id>0){
    $p->delete($user); header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', $langs->trans('Flaskelinjer'));
print load_fiche_titre($langs->trans('Flaskelinjer'));

print '<form method="post"><input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.($p->id?:'').'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans('Felt').'</td><td>'.$langs->trans('Værdi').'</td></tr>';
print '<tr><td>'.$langs->trans('Ref').'</td><td><input type="text" name="ref" value="'.dol_escape_htmltag($p->ref).'"></td></tr>';
print '<tr><td>'.$langs->trans('Etiket').'</td><td><input type="text" name="label" value="'.dol_escape_htmltag($p->label).'"></td></tr>';
print '<tr><td>'.$langs->trans('Linjetype').'</td><td><select name="line_type"><option value="bottle"'.($p->line_type=='bottle'?' selected':'').'>'.$langs->trans('Flaskning').'</option><option value="can"'.($p->line_type=='can'?' selected':'').'>'.$langs->trans('Dåselinje').'</option><option value="keg"'.($p->line_type=='keg'?' selected':'').'>'.$langs->trans('Fad/Keg').'</option></select></td></tr>';
print '<tr><td>'.$langs->trans('KapacitetEnhederTime').'</td><td><input type="number" step="1" name="units_per_hour" value="'.($p->units_per_hour!==null?$p->units_per_hour:'').'"> '.$langs->trans('enheder/time').'</td></tr>';
print '<tr><td>'.$langs->trans('Aktiv').'</td><td><input type="checkbox" name="enabled" value="1"'.($p->enabled?' checked':'').'></td></tr>';
print '<tr><td>'.$langs->trans('Note').'</td><td><input type="text" name="note" style="width:60%" value="'.dol_escape_htmltag($p->note).'"></td></tr>';
print '</table>';
print '<div class="center"><input class="button" type="submit" value="'.$langs->trans($p->id?'Gem':'Opret').'">';
if ($p->id){ $del=$_SERVER['PHP_SELF'].'?id='.$p->id.'&action=delete&token='.$token; print ' <a class="butActionDelete" href="'.$del.'">× '.$langs->trans('Slet').'</a>'; }
print '</div></form>';

$res=$db->query("SELECT rowid,ref,label,line_type,units_per_hour,enabled FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE entity=".(int)$conf->entity." ORDER BY rowid DESC");
print '<h3>'.$langs->trans('Liste').'</h3>';
print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Etiket').'</td><td>'.$langs->trans('Linjetype').'</td><td>'.$langs->trans('KapacitetEnhederTime').'</td><td>'.$langs->trans('Aktiv').'</td><td class="right">'.$langs->trans('Handling').'</td></tr>';
if ($res) while($o=$db->fetch_object($res)){ $u=$_SERVER['PHP_SELF'].'?id='.$o->rowid; $lt=$o->line_type; $ltname=($lt=='bottle'?$langs->trans('Flaskning'):($lt=='can'?$langs->trans('Dåselinje'):$langs->trans('Fad/Keg'))); print '<tr><td><a href="'.$u.'">'.dol_escape_htmltag($o->ref).'</a></td><td>'.dol_escape_htmltag($o->label).'</td><td>'.$ltname.'</td><td>'.($o->units_per_hour!==null?$o->units_per_hour:'-').'</td><td>'.($o->enabled?$langs->trans('Ja'):$langs->trans('Nej')).'</td><td class="right"><a class="butAction" href="'.$u.'">'.$langs->trans('Åbn').'</a></td></tr>'; }
llxFooter();
