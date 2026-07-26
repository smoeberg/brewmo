<?php
$res=0;
$paths=array(__DIR__.'/../../main.inc.php',__DIR__.'/../../../main.inc.php',__DIR__.'/../../../../main.inc.php');
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->write)) accessforbidden();
global $db, $conf;

$rid = (int) GETPOST('recipe_id','int');
if ($rid<=0) accessforbidden();

function columnExists($table,$col){
    global $db;
    $dbn = $db->database_name ? $db->database_name : null;
    $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='".$db->escape($table)."' AND COLUMN_NAME='".$db->escape($col)."'";
    if ($dbn) $sql .= " AND TABLE_SCHEMA='".$db->escape($dbn)."'";
    $res = $db->query($sql);
    if ($res && ($o=$db->fetch_object($res))) return ((int)$o->c)>0;
    return false;
}
function insertAuto($table, $data){
    global $db;
    $fields=array(); $values=array();
    foreach($data as $k=>$v){
        if (columnExists($table, $k)){
            $fields[]=$k;
            $values[]=$v===null ? "NULL" : $v;
        }
    }
    if (!count($fields)) return false;
    $sql = "INSERT INTO ".$table." (".implode(',', $fields).") VALUES (".implode(',', $values).")";
    $res = $db->query($sql);
    if(!$res) return array(false, $db->lasterror());
    $id = $db->last_insert_id($table);
    return array($id, null);
}

$recipe = null;
$res = $db->query("SELECT rowid, label, batch_volume_l, fk_product FROM llx_brew_recipes WHERE rowid=".$rid);
if($res && $db->num_rows($res)>0){ $recipe = $db->fetch_object($res); }
if(!$recipe){ setEventMessages('Opskrift ikke fundet', null, 'errors'); header("Location: /custom/brewmo/www/recipe_list.php"); exit; }

if (empty($conf->mrp->enabled)) {
    setEventMessages('MRP (Produktionsordrer) modulet er ikke aktiveret.', null, 'errors');
    header("Location: /custom/brewmo/www/brewsession_plan.php?recipe_id=".$rid); exit;
}
if (empty($recipe->fk_product)) {
    setEventMessages('Opskriften er ikke knyttet til en færdigvare (fk_product). Angiv færdigvare på opskriften først.', null, 'errors');
    header("Location: /custom/brewmo/www/recipe_edit.php?id=".$rid); exit;
}

$db->begin();

// 1) Opret/sync BOM
$bomid = null;

// Tjek om en BOM i forvejen er lavet for denne opskrift
$bomref = 'BREWMO-REC-'.$recipe->rowid;
$bomfields = array('rowid','ref');
$has_label_col = columnExists('llx_bom_bom','label');
$has_title_col = columnExists('llx_bom_bom','title');
$labelcol = $has_label_col ? 'label' : ($has_title_col ? 'title' : null);
$sqlFind = "SELECT rowid FROM llx_bom_bom WHERE ref='".$db->escape($bomref)."'";
$rf = $db->query($sqlFind);
if ($rf && $db->num_rows($rf)>0) {
    $o = $db->fetch_object($rf);
    $bomid = (int)$o->rowid;
} else {
    // Build BOM header
    $data = array();
    $data['entity'] = (int)$conf->entity;
    if (columnExists('llx_bom_bom','ref')) $data['ref'] = "'".$db->escape($bomref)."'";
    if ($labelcol) $data[$labelcol] = "'".$db->escape('BOM: '.$recipe->label)."'";
    if (columnExists('llx_bom_bom','fk_product')) $data['fk_product'] = (int)$recipe->fk_product;
    // Brug batch_volume_l som output qty for BOM (så MO qty kan være 1)
    if (columnExists('llx_bom_bom','qty')) $data['qty'] = ($recipe->batch_volume_l>0 ? (float)$recipe->batch_volume_l : 1);
    if (columnExists('llx_bom_bom','status')) $data['status'] = 1; // aktiv
    if (columnExists('llx_bom_bom','datec')) $data['datec'] = "NOW()";
    list($bomid,$err) = insertAuto('llx_bom_bom', $data);
    if (!$bomid){ $db->rollback(); setEventMessages('Kunne ikke oprette BOM: '.$err, null, 'errors'); header("Location: /custom/brewmo/www/brewsession_plan.php?recipe_id=".$rid); exit; }
    // Rens linjer hvis nogen
    if (columnExists('llx_bom_bomline','fk_bom')) {
        $db->query("DELETE FROM llx_bom_bomline WHERE fk_bom=".$bomid);
    }
    // Tilføj linjer for hver ingrediens (kun dem med fk_product)
    $inspos = 10;
    $addline = function($name,$fkprod,$qty) use($db,$conf,$bomid,&$inspos){
        if ($fkprod<=0 || $qty<=0) return;
        $d=array();
        $d['entity']=(int)$conf->entity;
        $d['fk_bom']=(int)$bomid;
        if (columnExists('llx_bom_bomline','fk_product')) $d['fk_product']=(int)$fkprod;
        if (columnExists('llx_bom_bomline','qty')) $d['qty']=(float)$qty;
        if (columnExists('llx_bom_bomline','position')) $d['position']=(int)$inspos;
        if (columnExists('llx_bom_bomline','description')) $d['description']="'".$db->escape($name)."'";
        $inspos += 10;
        insertAuto('llx_bom_bomline',$d);
    };
    // Malt (kg)
    $r = $db->query("SELECT name, amount_kg as qty, fk_product FROM llx_brew_recipe_malts WHERE fk_recipe=".$rid);
    while($r && ($o=$db->fetch_object($r))){ $addline($o->name,(int)$o->fk_product,(float)$o->qty); }
    // Hops (g)
    $r = $db->query("SELECT name, grams as qty, fk_product FROM llx_brew_recipe_hops WHERE fk_recipe=".$rid);
    while($r && ($o=$db->fetch_object($r))){ $addline($o->name,(int)$o->fk_product,(float)$o->qty); }
    // Yeast (g)
    $r = $db->query("SELECT name, grams as qty, fk_product FROM llx_brew_recipe_yeasts WHERE fk_recipe=".$rid);
    while($r && ($o=$db->fetch_object($r))){ $addline($o->name,(int)$o->fk_product,(float)$o->qty); }
    // Extras (g)
    $r = $db->query("SELECT name, grams as qty, fk_product FROM llx_brew_recipe_extras WHERE fk_recipe=".$rid);
    while($r && ($o=$db->fetch_object($r))){ $addline($o->name,(int)$o->fk_product,(float)$o->qty); }
}

// 2) Opret MO (draft)
$moqty = 1; // fordi BOM.qty = batch_volume_l
$moref = 'BREWMO-MO-'.$recipe->rowid.'-'.date('YmdHi');
$moData = array();
$moData['entity'] = (int)$conf->entity;
if (columnExists('llx_mrp_mo','ref')) $moData['ref'] = "'".$db->escape($moref)."'";
if (columnExists('llx_mrp_mo','fk_product')) $moData['fk_product'] = (int)$recipe->fk_product;
if (columnExists('llx_mrp_mo','fk_bom') && $bomid) $moData['fk_bom'] = (int)$bomid;
if (columnExists('llx_mrp_mo','qty')) $moData['qty'] = (float)$moqty;
if (columnExists('llx_mrp_mo','note_private')) $moData['note_private'] = "'".$db->escape('Oprettet fra Brewmo opskrift #'.$recipe->rowid.' ('.$recipe->label.')')."'";
if (columnExists('llx_mrp_mo','status')) $moData['status'] = 0; // draft
if (columnExists('llx_mrp_mo','date_creation')) $moData['date_creation'] = "NOW()";
if (columnExists('llx_mrp_mo','origin')) $moData['origin'] = "'brewmo'";
if (columnExists('llx_mrp_mo','origin_id')) $moData['origin_id'] = (int)$recipe->rowid;

list($moid,$errmo) = insertAuto('llx_mrp_mo', $moData);
if (!$moid){ $db->rollback(); setEventMessages('Kunne ikke oprette MO: '.$errmo, null, 'errors'); header("Location: /custom/brewmo/www/brewsession_plan.php?recipe_id=".$rid); exit; }

$db->commit();

// Redirect til MO-kort hvis muligt
$dest = '/mrp/mo/card.php?id='.$moid;
header('Location: '.$dest);
exit;
