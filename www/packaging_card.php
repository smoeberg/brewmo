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
dol_include_once('/brewmo/class/packaging.class.php');
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->write)) accessforbidden();

$id=(int)GETPOST('id','int'); $action=GETPOST('action','alpha'); $token=newToken();
$p=new BrewmoPackaging($db); if ($id>0) $p->fetch($id);

function brewmo_get_line_speed($db,$id){
  $res=$db->query("SELECT units_per_hour FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE rowid=".(int)$id);
  if($res && $o=$db->fetch_object($res)) return (float)$o->units_per_hour;
  return null;
}

if ($action=='create' && GETPOST('token','alpha')==$_SESSION['newtoken']){
    $p->fk_brewsession=(int)GETPOST('fk_brewsession','int');
    $p->fk_packaging_type=(int)GETPOST('fk_packaging_type','int');
    $p->units=(float)GETPOST('units','alpha');
    $p->total_volume_l=(float)GETPOST('total_volume_l','alpha');
    $p->fk_product=(int)GETPOST('fk_product','int');
    $p->note=GETPOST('note','alpha');
    $p->fk_packaging_line=(int)GETPOST('fk_packaging_line','int');
    $planstart=GETPOST('schedule_start','alpha'); // 'YYYY-MM-DDTHH:MM'

    $spd=brewmo_get_line_speed($db,$p->fk_packaging_line);
    if ($spd && $spd>0){ $p->expected_minutes = ($p->units / $spd) * 60.0; }

    $id=$p->create($user);

    // Opret booking hvis planstart sat
    if ($id>0 && !empty($planstart)){
        $start = $db->idate(strtotime($planstart));
        $minutes = ($p->expected_minutes!==null)? (float)$p->expected_minutes : 0;
        $db->query("INSERT INTO ".MAIN_DB_PREFIX."brew_packline_bookings(entity,fk_packaging_line,fk_packaging,title,start_datetime,end_datetime,status) VALUES (".(int)$conf->entity.",".(int)$p->fk_packaging_line.",".$id.",'Pakning #".$id."','".$db->escape($planstart).":00',DATE_ADD('".$db->escape($planstart).":00', INTERVAL ".(int)$minutes." MINUTE),'planned')");
    }

    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id); exit;
}

if ($action=='validate' && $p->id>0 && GETPOST('token','alpha')==$_SESSION['newtoken']){
    require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
    $p->setStatusValidated($user);
    $db->query("UPDATE ".MAIN_DB_PREFIX."brew_packline_bookings SET status='done' WHERE fk_packaging=".(int)$p->id);
    $interfaces=new Interfaces($db);
    $interfaces->run_triggers('BREWMO_PACKAGING_VALIDATE', $p, $user, $langs, $conf);
    setEventMessages($langs->trans("Valideret"), null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF'].'?id='.$p->id); exit;
}

llxHeader('', $langs->trans("Pakning"));
print load_fiche_titre($langs->trans("Pakning"));

if ($p->id>0){
    print '<div class="fichecenter"><table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Felt").'</td><td>'.$langs->trans("Værdi").'</td></tr>';
    print '<tr><td>ID</td><td>'.$p->id.'</td></tr>';
    print '<tr><td>'.$langs->trans("Brygsession").'</td><td>'.$p->fk_brewsession.'</td></tr>';
    print '<tr><td>'.$langs->trans("Pakningstype").'</td><td>'.$p->fk_packaging_type.'</td></tr>';
    print '<tr><td>'.$langs->trans("Enheder").'</td><td>'.$p->units.'</td></tr>';
    print '<tr><td>'.$langs->trans("VolumenL").'</td><td>'.$p->total_volume_l.'</td></tr>';
    print '<tr><td>'.$langs->trans("Linje").'</td><td>'.($p->fk_packaging_line?:'-').'</td></tr>';
    print '<tr><td>'.$langs->trans("ForventetTidMin").'</td><td>'.($p->expected_minutes!==null?round($p->expected_minutes,1):'-').'</td></tr>';
    print '<tr><td>'.$langs->trans("FærdigvareProduktID").'</td><td>'.($p->fk_product?:'-').'</td></tr>';
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.($p->status? $langs->trans("Valideret"):$langs->trans("Kladde")).'</td></tr>';
    print '<tr><td>'.$langs->trans("Dato").'</td><td>'.($p->date_packaged?:'-').'</td></tr>';
    print '<tr><td>'.$langs->trans("Note").'</td><td>'.dol_escape_htmltag($p->note).'</td></tr>';
    print '</table></div>';
    if (!$p->status){
        $u=$_SERVER['PHP_SELF'].'?id='.$p->id.'&action=validate&token='.$token;
        print '<div class="tabsAction"><a class="butAction" href="'.$u.'">'.$langs->trans("Validér").'</a></div>';
    }
} else {
    print '<form method="post"><input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="create">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Felt").'</td><td>'.$langs->trans("Værdi").'</td></tr>';
    print '<tr><td>'.$langs->trans("BrygsessionID").'</td><td><input type="number" name="fk_brewsession" min="1" required></td></tr>';
    print '<tr><td>'.$langs->trans("PakningstypeID").'</td><td><input type="number" name="fk_packaging_type" min="1" required></td></tr>';
    print '<tr><td>'.$langs->trans("Enheder").'</td><td><input type="number" step="1" name="units" required></td></tr>';
    print '<tr><td>'.$langs->trans("VolumenL").'</td><td><input type="number" step="0.1" name="total_volume_l"></td></tr>';
    print '<tr><td>'.$langs->trans("FærdigvareProduktID").'</td><td><input type="number" name="fk_product" min="1"></td></tr>';
    print '<tr><td>'.$langs->trans("Linje").'</td><td><select name="fk_packaging_line">';
    $res=$db->query("SELECT rowid,ref,label,line_type,units_per_hour FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE entity=".(int)$conf->entity." AND enabled=1 ORDER BY rowid DESC");
    if ($res) while($o=$db->fetch_object($res)){ print '<option value="'.$o->rowid.'">'.dol_escape_htmltag($o->ref.' - '.$o->label.' ('.$o->line_type.', '.$o->units_per_hour.' '.$langs->trans("enheder/time").')').'</option>'; }
    print '</select></td></tr>';
    print '<tr><td>'.$langs->trans("PlanlagtStart").'</td><td><input type="datetime-local" name="schedule_start"></td></tr>';
    print '<tr><td>'.$langs->trans("Note").'</td><td><input type="text" name="note" style="width:60%"></td></tr>';
    print '</table><div class="center"><input class="button" type="submit" value="'.$langs->trans("Opret").'"></div></form>';
}

llxFooter();
