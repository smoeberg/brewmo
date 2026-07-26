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
llxHeader('', $langs->trans("UdstyrStatus"));
print load_fiche_titre($langs->trans("UdstyrStatus"));

print '<h3>'.$langs->trans("Gærtanke").'</h3>';
$sql="SELECT v.rowid,v.ref,v.label,v.capacity_l,v.status,b.ref as brewsref,b.rowid as bsid
      FROM ".MAIN_DB_PREFIX."brew_vessels v
      LEFT JOIN ".MAIN_DB_PREFIX."brew_brewsession b ON b.fk_vessel=v.rowid AND b.entity=v.entity
      WHERE v.entity=".(int)$conf->entity."
      ORDER BY v.rowid DESC";
$res=$db->query($sql);
print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Etiket").'</td><td>'.$langs->trans("KapacitetL").'</td><td>'.$langs->trans("Status").'</td><td>'.$langs->trans("Brygsession").'</td></tr>';
if ($res) while($o=$db->fetch_object($res)){
    $bslink = $o->bsid? '<a href="'.dol_buildpath('/brewmo/www/brewsession_card.php',1).'?id='.$o->bsid.'">'.dol_escape_htmltag($o->brewsref).'</a>' : '-';
    print '<tr><td>'.dol_escape_htmltag($o->ref).'</td><td>'.dol_escape_htmltag($o->label).'</td><td>'.($o->capacity_l+0).'</td><td>'.dol_escape_htmltag($o->status?:'-').'</td><td>'.$bslink.'</td></tr>';
}
print '</table>';

print '<h3>'.$langs->trans("Flaskelinjer").'</h3>';
$res=$db->query("SELECT rowid,ref,label,line_type,units_per_hour,enabled FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE entity=".(int)$conf->entity." ORDER BY rowid DESC");
print '<table class="noborder" width="100%"><tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Etiket").'</td><td>'.$langs->trans("Linjetype").'</td><td>'.$langs->trans("KapacitetEnhederTime").'</td><td>'.$langs->trans("Aktiv").'</td></tr>';
if ($res) while($o=$db->fetch_object($res)){
    print '<tr><td>'.dol_escape_htmltag($o->ref).'</td><td>'.dol_escape_htmltag($o->label).'</td><td>'.dol_escape_htmltag($o->line_type).'</td><td>'.($o->units_per_hour!==null?$o->units_per_hour:'-').'</td><td>'.($o->enabled?$langs->trans("Ja"):$langs->trans("Nej")).'</td></tr>';
}
llxFooter();
