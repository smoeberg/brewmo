<?php
$res=0;
$paths=array(__DIR__.'/../../main.inc.php',__DIR__.'/../../../main.inc.php',__DIR__.'/../../../../main.inc.php');
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();
global $db, $conf;

$rid = (int) GETPOST('recipe_id','int');
if ($rid<=0) accessforbidden();

$wh_id = !empty($conf->global->BREWMO_DEFAULT_WAREHOUSE) ? (int)$conf->global->BREWMO_DEFAULT_WAREHOUSE : 0;

function q($sql){ global $db; $res=$db->query($sql); if(!$res) { print '<div class="error">'.$db->lasterror().'</div>'; return false; } return $res; }

llxHeader('', $langs->trans("PlanlægBryg"));

print load_fiche_titre($langs->trans("PlanlægBryg"));

$res = q("SELECT label, batch_volume_l FROM llx_brew_recipes WHERE rowid=".$rid);
$rec = $res && $db->num_rows($res)>0 ? $db->fetch_object($res) : null;

if(!$rec){ print '<div class="error">Opskrift ikke fundet.</div>'; llxFooter(); exit; }

print '<div class="fichecenter">';
print '<p><strong>'.$langs->trans("Opskrift").':</strong> '.dol_escape_htmltag($rec->label).' &nbsp; ';
print '<strong>'.$langs->trans("Batchvolumen (L)").':</strong> '.dol_escape_htmltag($rec->batch_volume_l).'</p>';
if(!$wh_id) print '<div class="warning">BREWMO_DEFAULT_WAREHOUSE er ikke sat. Lagerkontrol sker på tværs af alle lagre.</div>';

$need = array();

// Malt (kg)
$r = q("SELECT name, amount_kg as qty, fk_product, product_ref FROM llx_brew_recipe_malts WHERE fk_recipe=".$rid);
while($r && ($o=$db->fetch_object($r))){
    $need[] = array('type'=>'malt','name'=>$o->name,'u'=>'kg','qty'=>(float)$o->qty,'fk_product'=>(int)$o->fk_product,'ref'=>$o->product_ref);
}
// Hops (g)
$r = q("SELECT name, grams as qty, fk_product, product_ref FROM llx_brew_recipe_hops WHERE fk_recipe=".$rid);
while($r && ($o=$db->fetch_object($r))){
    $need[] = array('type'=>'hop','name'=>$o->name,'u'=>'g','qty'=>(float)$o->qty,'fk_product'=>(int)$o->fk_product,'ref'=>$o->product_ref);
}
// Yeast (g)
$r = q("SELECT name, grams as qty, fk_product, product_ref FROM llx_brew_recipe_yeasts WHERE fk_recipe=".$rid);
while($r && ($o=$db->fetch_object($r))){
    $need[] = array('type'=>'yeast','name'=>$o->name,'u'=>'g','qty'=>(float)$o->qty,'fk_product'=>(int)$o->fk_product,'ref'=>$o->product_ref);
}
// Extras (g)
$r = q("SELECT name, grams as qty, fk_product, product_ref FROM llx_brew_recipe_extras WHERE fk_recipe=".$rid);
while($r && ($o=$db->fetch_object($r))){
    $need[] = array('type'=>'extra','name'=>$o->name,'u'=>'g','qty'=>(float)$o->qty,'fk_product'=>(int)$o->fk_product,'ref'=>$o->product_ref);
}

print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Navn").'</td><td>'.$langs->trans("Produkt ref.").'</td><td>'.$langs->trans("Behov").'</td><td>'.$langs->trans("Lager").'</td><td>'.$langs->trans("Mangler").'</td></tr>';

$short = array();

foreach($need as $n){
    $stock = 0.0;
    if ($n['fk_product']>0){
        if ($wh_id>0){
            $sql = "SELECT IFNULL(reel,0) AS s FROM llx_product_stock WHERE fk_product=".$n['fk_product']." AND fk_entrepot=".$wh_id;
        } else {
            $sql = "SELECT SUM(IFNULL(reel,0)) AS s FROM llx_product_stock WHERE fk_product=".$n['fk_product'];
        }
        $rs=q($sql);
        if($rs && $db->num_rows($rs)>0){ $o=$db->fetch_object($rs); $stock = (float)$o->s; }
    }
    // Enhedsforskel: malt i kg, humle/gær/ekstra i g -> antag produkt lagerenhed matcher (forenkling)
    $missing = max(0.0, $n['qty'] - $stock);
    if ($missing>0){
        $short[] = array('type'=>$n['type'],'name'=>$n['name'],'ref'=>$n['ref'],'fk_product'=>$n['fk_product'],'need'=>$n['qty'],'stock'=>$stock,'missing'=>$missing,'u'=>$n['u']);
    }
    print '<tr><td>'.$n['type'].'</td><td>'.dol_escape_htmltag($n['name']).'</td><td>'.dol_escape_htmltag($n['ref']).'</td><td align="right">'.price($n['qty'],'',',',' ').' '.$n['u'].'</td><td align="right">'.price($stock,'',',',' ').'</td><td align="right">'.price($missing,'',',',' ').'</td></tr>';
}
print '</table></div>';

if (count($short)==0){
    print '<div class="ok">Alle råvarer er på lager for dette bryg.</div>';
} else {
    print '<br><div class="warning">Der mangler varer til dette bryg. Se liste nedenfor.</div>';
    print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Produkt ref.").'</td><td>'.$langs->trans("Navn").'</td><td>'.$langs->trans("Behov").'</td><td>'.$langs->trans("Lager").'</td><td>'.$langs->trans("Mangler").'</td></tr>';
    foreach($short as $s){
        print '<tr><td>'.dol_escape_htmltag($s['ref']).'</td><td>'.dol_escape_htmltag($s['name']).'</td><td align="right">'.price($s['need']).' '.$s['u'].'</td><td align="right">'.price($s['stock']).'</td><td align="right"><strong>'.price($s['missing']).' '.$s['u'].'</strong></td></tr>';
    }
    print '</table></div>';
    print '<p class="opacitymedium">'.$langs->trans("TipOpretIndkobsordre").'</p>';
}
print '</div>';

llxFooter();
