<?php
// ======================================================================
// BREWMO • Realiser bryg – registrér forbrug, målinger, trin og tab
// URL: /custom/brewmo/brewsession_realize.php?id=XX
// ======================================================================
$res = 0;
$paths = array(
    __DIR__.'/../../main.inc.php',
    __DIR__.'/../../../main.inc.php',
    __DIR__.'/../../../../main.inc.php',
    __DIR__.'/../main.inc.php',
    __DIR__.'/main.inc.php',
    '../../main.inc.php','../../../main.inc.php','../../../../main.inc.php','../main.inc.php','main.inc.php'
);
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }

global $db, $user, $conf, $langs;
$_GET['mainmenu']='brewmo';
$_GET['leftmenu']='brewmo_brewsession';

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

dol_include_once('/core/class/html.form.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/mouvementstock.class.php');

$form = new Form($db);

// ---------- Utils ----------
function esc($s){ return dol_escape_htmltag($s,1); }
function n($v){ return is_numeric($v)?(0+$v):null; }
function table_exists(DoliDB $db, $name){
    $sql = "SHOW TABLES LIKE '".$db->escape($name)."'";
    $rs = $db->query($sql);
    return ($rs && $db->num_rows($rs)>0);
}

// Load brewsession + recipe
$action = GETPOST('action','alpha');
$id      = (int) GETPOST('id','int');
$token   = GETPOST('token','alpha');
if ($id<=0) accessforbidden();

$bs = null;
$sql = "SELECT bs.*, r.ref as recipe_ref, r.label as recipe_label
        FROM ".MAIN_DB_PREFIX."brew_brewsession bs
        LEFT JOIN ".MAIN_DB_PREFIX."brew_recipes r ON r.rowid = bs.fk_recipe
        WHERE bs.rowid=".$id;
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql)>0) $bs = $db->fetch_object($resql);
if (!$bs) accessforbidden();

// ----------------------------------------------------------------------
// KONFIG: Lot-krav (kan overstyres i conf->global)
// ----------------------------------------------------------------------
$cat_hops  = (int) (!empty($conf->global->BREWMO_CAT_HOPS)  ? $conf->global->BREWMO_CAT_HOPS  : 0);
$cat_yeast = (int) (!empty($conf->global->BREWMO_CAT_YEAST) ? $conf->global->BREWMO_CAT_YEAST : 0);

$requireLotHops  = isset($conf->global->BREWMO_REQUIRE_LOT_HOPS)  ? (int)$conf->global->BREWMO_REQUIRE_LOT_HOPS  : 1;
$requireLotYeast = isset($conf->global->BREWMO_REQUIRE_LOT_YEAST) ? (int)$conf->global->BREWMO_REQUIRE_LOT_YEAST : 1;

$rawWhId      = !empty($conf->global->BREWMO_DEFAULT_RAW_WAREHOUSE) ? (int)$conf->global->BREWMO_DEFAULT_RAW_WAREHOUSE : 0;
$finishedWhId = !empty($conf->global->BREWMO_DEFAULT_FINISHED_WAREHOUSE) ? (int)$conf->global->BREWMO_DEFAULT_FINISHED_WAREHOUSE : 0;
$finishedProd = !empty($conf->global->BREWMO_FINISHED_PRODUCT_ID) ? (int)$conf->global->BREWMO_FINISHED_PRODUCT_ID : 0;
$unitsPerL    = isset($conf->global->BREWMO_UNITS_PER_L) ? (float)$conf->global->BREWMO_UNITS_PER_L : 1.0;

// ----------------------------------------------------------------------
// UI helpers (simple selectors hvis html.form* ikke er tilgængelig)
// ----------------------------------------------------------------------
function brewmo_render_lot_selector($htmlname){ return '<input type="number" name="'.$htmlname.'" placeholder="lot_id" style="width:110px">'; }
function brewmo_render_wh_selector($htmlname, $default=0){
    $val = $default>0 ? ' value="'.(int)$default.'"' : '';
    return '<input type="number" name="'.$htmlname.'" placeholder="warehouse_id" style="width:120px"'.$val.'>';
}

// ---------- Kategori-check ----------
function brewmo_product_in_category(DoliDB $db, $product_id, $cat_id){
    if ($product_id<=0 || $cat_id<=0) return false;
    $q = "SELECT 1 FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product=".(int)$product_id." AND fk_categorie=".(int)$cat_id." LIMIT 1";
    $rs = $db->query($q);
    return ($rs && $db->num_rows($rs)>0);
}

// ---------- Lagercheck ----------
function brewmo_product_stock_total(DoliDB $db, $product_id){
    $sql = "SELECT SUM(reel) as stock FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product=".(int)$product_id;
    $rs = $db->query($sql);
    if ($rs && ($o=$db->fetch_object($rs))) return (float)$o->stock;
    return 0.0;
}

// Hent alle (kladde) forbrugslinjer for sessionen – bruges til "kan starte?"
function brewmo_required_from_consumption(DoliDB $db, $session_id){
    $out = array(); // product_id => qty (i indtastet enhed; vi checker kun total mod lager)
    $sql = "SELECT fk_product, product_ref, COALESCE(actual_qty, plan_qty) as need_qty
            FROM ".MAIN_DB_PREFIX."brew_consumption
            WHERE fk_brewsession=".(int)$session_id;
    $rs = $db->query($sql);
    if ($rs){
        while($o=$db->fetch_object($rs)){
            $pid = (int)$o->fk_product;
            if ($pid<=0 && !empty($o->product_ref)){
                $rq = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref='".$db->escape($o->product_ref)."' ORDER BY entity DESC LIMIT 1");
                if ($rq && $db->num_rows($rq)) $pid = (int)$db->fetch_object($rq)->rowid;
            }
            if ($pid>0 && is_numeric($o->need_qty)){
                if (!isset($out[$pid])) $out[$pid]=0.0;
                $out[$pid] += (float)$o->need_qty;
            }
        }
    }
    return $out;
}

// Valgfri: Tjek om linked leverandørordrer er modtaget (kræver mapping-tabel)
function brewmo_all_pos_received(DoliDB $db, $session_id){
    $maptable = MAIN_DB_PREFIX."brew_session_po";
    if (!table_exists($db, $maptable)) return true; // ingen mapping → intet krav
    $sql = "SELECT cf.fk_statut
            FROM ".MAIN_DB_PREFIX."brew_session_po bsp
            JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet cfd ON cfd.rowid = bsp.fk_cfd
            JOIN ".MAIN_DB_PREFIX."commande_fournisseur cf ON cf.rowid = cfd.fk_commande
            WHERE bsp.fk_session = ".((int)$session_id);
    $rs = $db->query($sql);
    if (!$rs) return false;
    while($o=$db->fetch_object($rs)){
        // I mange versioner: 6 = Received
        if ((int)$o->fk_statut < 6) return false;
    }
    return true;
}

// Kan vi starte? (lager + PO’er modtaget)
function brewmo_can_start(DoliDB $db, $session_id, &$reason=''){
    $need = brewmo_required_from_consumption($db, $session_id);
    foreach ($need as $pid=>$qty){
        $stock = brewmo_product_stock_total($db, $pid);
        if ($stock < (float)$qty){
            $reason = "Mangler råvare på lager (product_id=".$pid.", behøver ".$qty.", har ".$stock.").";
            return false;
        }
    }
    if (!brewmo_all_pos_received($db, $session_id)){
        $reason = "En eller flere indkøbsordrer er ikke modtaget endnu.";
        return false;
    }
    return true;
}

// Gem realiseret volumen (L)
function brewmo_save_realized_volume(DoliDB $db, $session_id, $volL){
    $sql = "UPDATE ".MAIN_DB_PREFIX."brew_brewsession SET realized_volume_l=".(float)$volL." WHERE rowid=".(int)$session_id;
    return $db->query($sql)?true:false;
}

// Postér forbrugslinje (lager ud – med lot-krav hvor nødvendigt)
function brewmo_post_consumption_line(DoliDB $db, $line_id, $brewsession_id, User $user){
    global $cat_hops, $cat_yeast, $requireLotHops, $requireLotYeast;
    if (empty($user->rights->brewmo->write)) return array(false, 'Permission denied');

    $db->begin();
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_consumption WHERE rowid=".(int)$line_id." AND fk_brewsession=".(int)$brewsession_id." FOR UPDATE";
    $rs = $db->query($sql);
    if(!$rs || $db->num_rows($rs)!=1){ $db->rollback(); return array(false,'Line not found'); }
    $l = $db->fetch_object($rs);
    if ((int)$l->posted===1){ $db->rollback(); return array(false,'Already posted'); }

    // Find produkt
    $fkprod = (int)$l->fk_product;
    if ($fkprod<=0 && !empty($l->product_ref)){
        $rq = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref='".$db->escape($l->product_ref)."' ORDER BY entity DESC LIMIT 1");
        if ($rq && $db->num_rows($rq)) $fkprod = (int)$db->fetch_object($rq)->rowid;
    }
    $prod = new Product($db);
    if ($fkprod<=0 || $prod->fetch($fkprod)<=0){ $db->rollback(); return array(false,'Product not found'); }

    // Lot-krav for humle/gær
    $lotRequired = false;
    if ($requireLotHops  && brewmo_product_in_category($db, $prod->id, $cat_hops))  $lotRequired = true;
    if ($requireLotYeast && brewmo_product_in_category($db, $prod->id, $cat_yeast)) $lotRequired = true;
    if ($lotRequired && (int)$l->fk_lot<=0){
        $db->rollback(); return array(false, 'Lot required for this product');
    }

    // Mængde – brug actual ellers plan
    $qty = n($l->actual_qty);
    if ($qty===null) $qty = n($l->plan_qty);
    if (!is_numeric($qty) || $qty<=0){ $db->rollback(); return array(false,'Quantity required'); }

    // Lagerbevægelse ud (negativ mængde)
    $fk_wh = (int)$l->fk_warehouse ?: (!empty($GLOBALS['rawWhId'])?$GLOBALS['rawWhId']:0);
    if ($fk_wh<=0){ $db->rollback(); return array(false,'Warehouse required'); }

    $fk_lot = (int)$l->fk_lot ?: 0;
    $ms = new MouvementStock($db);
    $label = 'BREWMO consumption batch #'.$brewsession_id.' ('.$prod->ref.')';
    $resms = $ms->create($user, $fk_wh, $prod->id, $fk_lot, -1 * (float)$qty, $label);
    if ($resms<=0){ $err=$ms->error; $db->rollback(); return array(false,$err?$err:'Stock movement failed'); }

    // Markér linje bogført + gem ref til movement
    $upd = "UPDATE ".MAIN_DB_PREFIX."brew_consumption SET posted=1, fk_stock_movement=".(int)$ms->id." WHERE rowid=".(int)$line_id;
    if (!$db->query($upd)){ $err=$db->lasterror(); $db->rollback(); return array(false,$err); }

    $db->commit();
    return array(true,'OK');
}

// Fortryd postering (lager ind)
function brewmo_unpost_consumption_line(DoliDB $db, $line_id, $brewsession_id, User $user){
    if (empty($user->rights->brewmo->write)) return array(false, 'Permission denied');

    $db->begin();
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_consumption WHERE rowid=".(int)$line_id." AND fk_brewsession=".(int)$brewsession_id." FOR UPDATE";
    $rs = $db->query($sql);
    if(!$rs || $db->num_rows($rs)!=1){ $db->rollback(); return array(false,'Line not found'); }
    $l = $db->fetch_object($rs);
    if ((int)$l->posted!==1){ $db->rollback(); return array(false,'Not posted'); }

    // Find produkt
    $fkprod = (int)$l->fk_product;
    if ($fkprod<=0 && !empty($l->product_ref)){
        $rq = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref='".$db->escape($l->product_ref)."' ORDER BY entity DESC LIMIT 1");
        if ($rq && $db->num_rows($rq)) $fkprod = (int)$db->fetch_object($rq)->rowid;
    }
    $prod = new Product($db);
    if ($fkprod<=0 || $prod->fetch($fkprod)<=0){ $db->rollback(); return array(false,'Product not found'); }

    $qty = n($l->actual_qty); if ($qty===null) $qty = n($l->plan_qty);
    $fk_wh = (int)$l->fk_warehouse ?: (!empty($GLOBALS['rawWhId'])?$GLOBALS['rawWhId']:0);
    $fk_lot= (int)$l->fk_lot ?: 0;

    $ms = new MouvementStock($db);
    $label = 'BREWMO reversal batch #'.$brewsession_id.' ('.$prod->ref.')';
    $resms = $ms->create($user, $fk_wh, $prod->id, $fk_lot, +1 * (float)$qty, $label);
    if ($resms<=0){ $err=$ms->error; $db->rollback(); return array(false,$err?$err:'Stock reversal failed'); }

    $upd = "UPDATE ".MAIN_DB_PREFIX."brew_consumption SET posted=0, fk_stock_movement=NULL WHERE rowid=".(int)$line_id;
    if (!$db->query($upd)){ $err=$db->lasterror(); $db->rollback(); return array(false,$err); }

    $db->commit();
    return array(true,'OK');
}

// Samlet kost af forbrug: summer price*abs(qty) fra lagerbevægelser markeret med vores label
function brewmo_compute_total_cost(DoliDB $db, $session_id){
    $labelLike = $db->escape('BREWMO consumption batch #'.$session_id.' (%');
    $sql = "SELECT SUM(ABS(s.qty) * ABS(s.price)) as total_cost
            FROM ".MAIN_DB_PREFIX."stock_mouvement s
            WHERE s.label LIKE '".$labelLike."'";
    $rs = $db->query($sql);
    if ($rs && ($o=$db->fetch_object($rs)) && $o->total_cost!==null) return (float)$o->total_cost;
    return 0.0;
}

// Afslutning: beregn kost, overfør færdigvare til lager (hvis konfigureret), sæt status=2
function brewmo_finish(DoliDB $db, $session){
    global $user, $finishedWhId, $finishedProd, $unitsPerL;

    // 1) Kost
    $total_cost = brewmo_compute_total_cost($db, $session->rowid);

    // 2) Gem kost + finished_at + status=2
    $db->begin();
    $q = "UPDATE ".MAIN_DB_PREFIX."brew_brewsession
          SET production_cost=".(float)$total_cost.", finished_at=NOW(), status=2
          WHERE rowid=".(int)$session->rowid;
    if (!$db->query($q)){ $db->rollback(); return array(false,'Kunne ikke gemme produktionskost.'); }

    // 3) Lager-tilgang af færdigvaren (valgfrit)
    if ($finishedProd>0 && $finishedWhId>0){
        $volL = isset($session->realized_volume_l) ? (float)$session->realized_volume_l : 0.0;
        if ($volL<=0){
            // Mangler realiseret volumen → vi gennemfører afslutning men uden lager-tilgang
            $db->commit();
            return array(true, 'Afsluttet (færdigvare ikke tilført: Realiseret volumen mangler).');
        }

        // Hvor mange enheder: liter * unitsPerL (fx flasker pr. liter)
        $qtyFinished = max(0, $volL * (float)$unitsPerL);
        if ($qtyFinished>0){
            $m = new MouvementStock($db);
            $label = 'BREWMO finished batch #'.$session->rowid;
            $resm = $m->create($user, $finishedWhId, (int)$finishedProd, 0, +1 * $qtyFinished, $label);
            if ($resm<=0){ $db->rollback(); return array(false, $m->error?:'Lager-tilgang for færdigvare fejlede'); }
        }
    }
    $db->commit();
    return array(true,'OK');
}

// ---------- CSRF for state changes ----------
$stateChanging = array('add_consumption','post_consumption','unpost_consumption','post_all','add_measurement','start_step','stop_step','save_step','add_loss','finalize_batch','start_production','save_realized');
if (in_array($action, $stateChanging, true) && !verifyToken($token)) accessforbidden();

// ======================================================================
// ACTIONS
// ======================================================================

// Gem realiseret volumen (L)
if ($action=='save_realized'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $vol = n(GETPOST('realized_volume_l','alpha'));
    if (!is_numeric($vol) || $vol<0){ setEventMessages($langs->trans("Ugyldig volumen"), null, 'errors'); }
    else {
        if (brewmo_save_realized_volume($db, $id, (float)$vol)) setEventMessages($langs->trans("Saved"), null, 'mesgs');
        else setEventMessages($db->lasterror(), null, 'errors');
    }
}

// Start produktion (bloker hvis lager/PO ikke ok)
if ($action=='start_production'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $reason='';
    if (!brewmo_can_start($db, $id, $reason)){
        setEventMessages($reason, null, 'errors');
    } else {
        // sæt status=1 (STARTED) hvis <1
        $q = "UPDATE ".MAIN_DB_PREFIX."brew_brewsession SET status=1, started_at=IFNULL(started_at,NOW()) WHERE rowid=".(int)$id." AND (status IS NULL OR status<1)";
        if ($db->query($q)) setEventMessages($langs->trans("Produktion startet"), null, 'mesgs');
        else setEventMessages($db->lasterror(), null, 'errors');
    }
}

// 1) Opret/Opdater forbrugslinje (kladde)
if ($action=='add_consumption'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $fk_product = (int) GETPOST('c_fk_product','int');
    $product_ref= trim(GETPOST('c_product_ref','alpha'));
    $plan_qty   = n(GETPOST('c_plan_qty','alpha'));
    $actual_qty = n(GETPOST('c_actual_qty','alpha'));
    $unit       = trim(GETPOST('c_unit','alpha'));
    $fk_wh      = (int) GETPOST('c_fk_warehouse','int');
    $fk_lot     = (int) GETPOST('c_fk_lot','int');
    $note       = trim(GETPOST('c_note','restricthtml'));

    $db->begin();
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."brew_consumption(entity,fk_brewsession,fk_product,product_ref,plan_qty,actual_qty,unit,fk_warehouse,fk_lot,posted,fk_stock_movement,note,fk_user)
            VALUES(".((int)$conf->entity).",".$id.",".($fk_product>0?$fk_product:'NULL').",".($product_ref!==''?"'".$db->escape($product_ref)."'":'NULL').",".($plan_qty!==null?$plan_qty:'NULL').",".($actual_qty!==null?$actual_qty:'NULL').",".($unit!==''?"'".$db->escape($unit)."'":'NULL').",".($fk_wh>0?$fk_wh:'NULL').",".($fk_lot>0?$fk_lot:'NULL').",0,NULL,".($note!==''?"'".$db->escape($note)."'":'NULL').",".(int)$user->id.")";
    $ok = $db->query($sql);
    if($ok) { setEventMessages($langs->trans("LineAdded"), null, 'mesgs'); $db->commit(); }
    else { setEventMessages($db->lasterror(), null, 'errors'); $db->rollback(); }
}

// 2) Bogfør forbrugslinje til lager
if ($action=='post_consumption'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $line_id = (int) GETPOST('line_id','int');
    list($ok,$msg) = brewmo_post_consumption_line($db, $line_id, $id, $user);
    if ($ok) setEventMessages($langs->trans("PostedToStock"), null, 'mesgs');
    else setEventMessages($msg, null, 'errors');
}

// 3) Fortryd postering
if ($action=='unpost_consumption'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $line_id = (int) GETPOST('line_id','int');
    list($ok,$msg) = brewmo_unpost_consumption_line($db, $line_id, $id, $user);
    if ($ok) setEventMessages($langs->trans("Unposted"), null, 'mesgs');
    else setEventMessages($msg, null, 'errors');
}

// 4) Backflush – bogfør alle ikke-bogførte
if ($action=='post_all'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $q = "SELECT rowid FROM ".MAIN_DB_PREFIX."brew_consumption WHERE fk_brewsession=".$id." AND posted=0 ORDER BY rowid ASC";
    $rs = $db->query($q);
    $okc = 0; $errc = 0;
    if ($rs){
        while($o=$db->fetch_object($rs)){
            list($ok,$msg) = brewmo_post_consumption_line($db, (int)$o->rowid, $id, $user);
            if ($ok) $okc++; else { $errc++; setEventMessages('Line #'.$o->rowid.': '.$msg, null, 'errors'); }
        }
    }
    if ($okc>0) setEventMessages($langs->trans("Processed")." ($okc OK, $errc fejl)", null, $errc>0?'warnings':'mesgs');
}

// 5) Tilføj måling
if ($action=='add_measurement'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $stage = trim(GETPOST('m_stage','alpha'));
    $metric= trim(GETPOST('m_metric','alpha'));
    $val   = n(GETPOST('m_value','alpha'));
    $unit  = trim(GETPOST('m_unit','alpha'));
    $at    = trim(GETPOST('m_at','alpha'));
    $note  = trim(GETPOST('m_note','restricthtml'));
    if ($stage!=='' && $metric!=='' && is_numeric($val) && $at!==''){
        $db->begin();
        $sql="INSERT INTO ".MAIN_DB_PREFIX."brew_measurement(entity,fk_brewsession,stage,metric,value_numeric,unit,at,fk_user,note)
              VALUES(".((int)$conf->entity).",".$id.",'".$db->escape($stage)."','".$db->escape($metric)."',".(0+$val).",".($unit!==''?"'".$db->escape($unit)."'":'NULL').",'".$db->escape($at)."',".(int)$user->id.",".($note!==''?"'".$db->escape($note)."'":'NULL').")";
        if ($db->query($sql)){ setEventMessages($langs->trans("Saved"), null, 'mesgs'); $db->commit(); }
        else { setEventMessages($db->lasterror(), null, 'errors'); $db->rollback(); }
    } else setEventMessages($langs->trans("MissingFields"), null, 'errors');
}

// 6) Start/stop trin + actuals
if ($action=='start_step' || $action=='stop_step' || $action=='save_step'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $type = trim(GETPOST('s_type','alpha'));
    $name = trim(GETPOST('s_name','alpha'));
    $rowid= (int) GETPOST('s_rowid','int');
    $at   = trim(GETPOST('s_at','alpha'));
    $a_temp = n(GETPOST('s_actual_temp','alpha'));
    $a_min  = n(GETPOST('s_actual_time_min','alpha'));
    $a_days = n(GETPOST('s_actual_time_days','alpha'));

    $db->begin();
    if ($action=='start_step'){
        $sql="INSERT INTO ".MAIN_DB_PREFIX."brew_step_actual(entity,fk_brewsession,type,name,started_at,fk_user)
              VALUES(".((int)$conf->entity).",".$id.",'".$db->escape($type)."','".$db->escape($name)."',".($at!==''?"'".$db->escape($at)."'":"NOW()").",".(int)$user->id.")";
        $ok=$db->query($sql);
    } elseif ($action=='stop_step'){
        $sql="UPDATE ".MAIN_DB_PREFIX."brew_step_actual SET ended_at=".($at!==''?"'".$db->escape($at)."'":"NOW()")." WHERE rowid=".$rowid." AND fk_brewsession=".$id;
        $ok=$db->query($sql);
    } else {
        $set = array();
        if (is_numeric($a_temp)) $set[]="actual_temp_c=".$a_temp;
        if (is_numeric($a_min))  $set[]="actual_time_min=".$a_min;
        if (is_numeric($a_days)) $set[]="actual_time_days=".$a_days;
        if (!count($set)) $set[]="name=name"; // no-op
        $sql="UPDATE ".MAIN_DB_PREFIX."brew_step_actual SET ".implode(',', $set)." WHERE rowid=".$rowid." AND fk_brewsession=".$id;
        $ok=$db->query($sql);
    }
    if($ok){ setEventMessages($langs->trans("Saved"), null, 'mesgs'); $db->commit(); }
    else { setEventMessages($db->lasterror(), null, 'errors'); $db->rollback(); }
}

// 7) Registrer tab/udbytte
if ($action=='add_loss'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    $loss_type = trim(GETPOST('l_type','alpha'));
    $vol       = n(GETPOST('l_volume','alpha'));
    $at        = trim(GETPOST('l_at','alpha'));
    $note      = trim(GETPOST('l_note','restricthtml'));
    if ($loss_type!=='' && is_numeric($vol) && $at!==''){
        $db->begin();
        $sql="INSERT INTO ".MAIN_DB_PREFIX."brew_yield_loss(entity,fk_brewsession,loss_type,volume_l,at,note)
              VALUES(".((int)$conf->entity).",".$id.",'".$db->escape($loss_type)."',".(0+$vol).",'".$db->escape($at)."',".($note!==''?"'".$db->escape($note)."'":'NULL').")";
        if ($db->query($sql)){ setEventMessages($langs->trans("Saved"), null, 'mesgs'); $db->commit(); }
        else { setEventMessages($db->lasterror(), null, 'errors'); $db->rollback(); }
    } else setEventMessages($langs->trans("MissingFields"), null, 'errors');
}

// 8) Afslut batch (beregn kost, lager-tilførsel af færdigvare, status=2)
if ($action=='finalize_batch'){
    if (empty($user->rights->brewmo->write)) accessforbidden();
    // Luk evt. åbne trin
    $db->query("UPDATE ".MAIN_DB_PREFIX."brew_step_actual SET ended_at=IFNULL(ended_at,NOW()) WHERE fk_brewsession=".$id);

    // Genindlæs session
    $rs = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."brew_brewsession WHERE rowid=".$id." LIMIT 1");
    $sess = $rs && $db->num_rows($rs) ? $db->fetch_object($rs) : null;
    if (!$sess){ setEventMessages('Session not found', null, 'errors'); }
    else {
        list($ok,$msg) = brewmo_finish($db, $sess);
        if ($ok) setEventMessages($langs->trans("BatchClosed"), null, 'mesgs');
        else setEventMessages($msg, null, 'errors');
    }
}

// ======================================================================
// VIEW
// ======================================================================
llxHeader('', $langs->trans("RealiserBryg"));
print load_fiche_titre($langs->trans("RealiserBryg").' #'.$id.' — '.esc($bs->recipe_ref).' / '.esc($bs->recipe_label));

$tok = newToken();

// ---- Status/volumen + actions (Start/Finish) ----
print '<div class="fichecenter"><div class="fichehalfleft">';
print '<table class="border centpercent">';
print '<tr><td>Status</td><td>'.(isset($bs->status)?(int)$bs->status:0).'</td></tr>';
print '<tr><td>Planlagt volumen (L)</td><td>'.(isset($bs->planned_volume_l)?esc($bs->planned_volume_l):'').'</td></tr>';
print '<tr><td>Realiseret volumen (L)</td><td>';
print '<form method="post" style="display:inline">';
print '<input type="hidden" name="token" value="'.$tok.'">';
print '<input type="hidden" name="action" value="save_realized">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<input type="number" step="0.01" name="realized_volume_l" value="'.esc(isset($bs->realized_volume_l)?$bs->realized_volume_l:'').'" style="width:120px"> ';
print '<input type="submit" class="button small" value="'.$langs->trans("Save").'">';
print '</form>';
print '</td></tr>';
print '<tr><td>Produktionskost (sum forbrug)</td><td>'.(isset($bs->production_cost)?price($bs->production_cost,0,'',1,-1,-1,$conf->currency):'-').'</td></tr>';
print '</table>';
print '</div><div class="fichehalfright">';
print '<div class="tabsAction">';

// Start-knap (blokeret hvis ikke kan starte)
$why=''; $canStart = brewmo_can_start($db, $id, $why);
if ((int)$bs->status < 1){
    if ($canStart){
        print '<form method="post" style="display:inline">';
        print '<input type="hidden" name="token" value="'.$tok.'"><input type="hidden" name="action" value="start_production"><input type="hidden" name="id" value="'.$id.'">';
        print '<input type="submit" class="butAction" value="'.$langs->trans("Start produktion").'">';
        print '</form>';
    } else {
        print '<span class="butActionRefused" title="'.esc($why).'">'.$langs->trans("Start produktion").'</span> ';
    }
} else {
    print '<span class="butActionRefused">'.$langs->trans("Start produktion").'</span> ';
}

// Afslut-knap (kun hvis status=1 (STARTED))
if ((int)$bs->status == 1){
    print '<form method="post" style="display:inline;margin-left:8px" onsubmit="return confirm(\''.$langs->transnoentities("Lukke alle trin og markere batch som færdig?").'\');">';
    print '<input type="hidden" name="token" value="'.$tok.'"><input type="hidden" name="action" value="finalize_batch"><input type="hidden" name="id" value="'.$id.'">';
    print '<input type="submit" class="butAction" value="'.$langs->trans("AfslutBatch").'">';
    print '</form>';
} else {
    print '<span class="butActionRefused">'.$langs->trans("AfslutBatch").'</span>';
}

print '</div>';
print '</div></div>';

print '<div class="clearboth"></div>';

print '<div class="tabsAction" style="margin-bottom:6px">';
print '<a class="butAction" href="#cons">'.$langs->trans("Råvareforbrug").'</a>';
print '<a class="butAction" href="#steps">'.$langs->trans("Trin").'</a>';
print '<a class="butAction" href="#measure">'.$langs->trans("Målinger").'</a>';
print '<a class="butAction" href="#loss">'.$langs->trans("Tab/udbytte").'</a>';
print '<a class="butAction" href="#variance">'.$langs->trans("Variansrapport").'</a>';
// Backflush
if (!empty($user->rights->brewmo->write)){
    print '<form method="post" style="display:inline;margin-left:12px">';
    print '<input type="hidden" name="token" value="'.$tok.'">';
    print '<input type="hidden" name="action" value="post_all">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    print '<input type="submit" class="butAction" value="'.$langs->trans("BogførAllePlanlagte").'">';
    print '</form>';
}
print '</div>';

// ---------- CONSUMPTION ----------
print '<a id="cons"></a>';
print '<br><div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="11">'.$langs->trans("Råvareforbrug").'</td></tr>';
print '<tr class="liste_titre"><td>Produkt</td><td>Ref</td><td>Plan</td><td>Faktisk</td><td>Enhed</td><td>Lager</td><td>Lot</td><td>Note</td><td>Status</td><td colspan="2" class="right">'.$langs->trans("Handling").'</td></tr>';

$sql = "SELECT c.*, p.ref as pref, p.label as plabel
        FROM ".MAIN_DB_PREFIX."brew_consumption c
        LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid=c.fk_product
        WHERE c.fk_brewsession=".$id."
        ORDER BY c.rowid ASC";
$rs = $db->query($sql);
if ($rs){
  while($o=$db->fetch_object($rs)){
    print '<tr>';
    $pdisp = $o->pref ? esc($o->pref.' - '.$o->plabel) : '&nbsp;';
    print '<td>'.$pdisp.'</td>';
    print '<td>'.esc($o->product_ref).'</td>';
    print '<td>'.esc($o->plan_qty).'</td>';
    print '<td>'.esc($o->actual_qty).'</td>';
    print '<td>'.esc($o->unit).'</td>';
    print '<td>'.((int)$o->fk_warehouse?:'').'</td>';
    print '<td>'.((int)$o->fk_lot?:'').'</td>';
    print '<td>'.esc($o->note).'</td>';
    print '<td>'.((int)$o->posted===1?'<span class="badge badge-status4">POSTED</span>':'<span class="badge badge-status1">DRAFT</span>').'</td>';
    print '<td class="right" style="white-space:nowrap">';
    if (!empty($user->rights->brewmo->write)){
        if ((int)$o->posted!==1){
            print '<form method="post" style="display:inline">';
            print '<input type="hidden" name="token" value="'.$tok.'">';
            print '<input type="hidden" name="action" value="post_consumption">';
            print '<input type="hidden" name="id" value="'.$id.'">';
            print '<input type="hidden" name="line_id" value="'.$o->rowid.'">';
            print '<input type="submit" class="button small" value="'.$langs->trans("BogførTilLager").'">';
            print '</form>';
        } else {
            print '<form method="post" style="display:inline;margin-left:6px">';
            print '<input type="hidden" name="token" value="'.$tok.'">';
            print '<input type="hidden" name="action" value="unpost_consumption">';
            print '<input type="hidden" name="id" value="'.$id.'">';
            print '<input type="hidden" name="line_id" value="'.$o->rowid.'">';
            print '<input type="submit" class="button small" value="'.$langs->trans("Fortryd").'">';
            print '</form>';
        }
    }
    print '</td>';
    print '<td></td>';
    print '</tr>';
  }
}

// add form
if (!empty($user->rights->brewmo->write)){
print '<tr><td colspan="11"><hr></td></tr>';
print '<tr><form method="post">'
    .'<input type="hidden" name="token" value="'.$tok.'">'
    .'<input type="hidden" name="action" value="add_consumption">'
    .'<input type="hidden" name="id" value="'.$id.'">';
print '<td><input type="number" name="c_fk_product" placeholder="product_id" style="width:120px"></td>';
print '<td><input type="text" name="c_product_ref" placeholder="ref"></td>';
print '<td><input type="number" step="0.001" name="c_plan_qty" placeholder="plan"></td>';
print '<td><input type="number" step="0.001" name="c_actual_qty" placeholder="faktisk"></td>';
print '<td><select name="c_unit"><option>kg</option><option>g</option><option>L</option><option>ml</option><option>stk</option></select></td>';
print '<td>'.brewmo_render_wh_selector('c_fk_warehouse', $rawWhId).'</td>';
print '<td>'.brewmo_render_lot_selector('c_fk_lot').'</td>';
print '<td><input type="text" name="c_note" placeholder="note"></td>';
print '<td colspan="3" class="right"><input type="submit" class="button" value="'.$langs->trans("Tilføj").'"></td>';
print '</form></tr>';
}
print '</table></div>';

// ---------- STEPS ----------
print '<a id="steps"></a>';
print '<br><div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="9">'.$langs->trans("Trin (plan vs. faktisk)").'</td></tr>';
print '<tr class="liste_titre"><td>Type</td><td>Navn</td><td>Plan temp</td><td>Plan tid</td><td>Startet</td><td>Sluttet</td><td>Act temp</td><td>Act tid</td><td></td></tr>';

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_step_actual WHERE fk_brewsession=".$id." ORDER BY rowid ASC";
$rs = $db->query($sql);
if ($rs){
  while($o=$db->fetch_object($rs)){
    print '<tr>';
    print '<td>'.esc($o->type).'</td>';
    print '<td>'.esc($o->name).'</td>';
    print '<td>'.esc($o->plan_temp_c).'</td>';
    $plan_time = $o->plan_time_min!==null? $o->plan_time_min.' min' : ($o->plan_time_days!==null? $o->plan_time_days.' d':'');
    print '<td>'.esc($plan_time).'</td>';
    print '<td>'.esc($o->started_at).'</td>';
    print '<td>'.esc($o->ended_at).'</td>';

    print '<td>';
    if (!empty($user->rights->brewmo->write)){
        print '<form method="post" style="display:inline"><input type="hidden" name="token" value="'.$tok.'"><input type="hidden" name="action" value="save_step"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="s_rowid" value="'.$o->rowid.'"><input type="number" step="0.1" name="s_actual_temp" value="'.esc($o->actual_temp_c).'">';
        print '</td><td><input type="number" step="0.1" name="s_actual_time_min" value="'.esc($o->actual_time_min).'" placeholder="min"><input type="number" step="0.1" name="s_actual_time_days" value="'.esc($o->actual_time_days).'" placeholder="d" style="margin-left:6px"></td>';
        print '<td><input type="submit" class="button small" value="'.$langs->trans("Gem").'"></form> ';
        if (empty($o->ended_at)){
            print '<form method="post" style="display:inline"><input type="hidden" name="token" value="'.$tok.'"><input type="hidden" name="action" value="stop_step"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="s_rowid" value="'.$o->rowid.'"><input type="submit" class="button small" value="'.$langs->trans("Stop").'"></form>';
        }
        print '</td>';
    } else {
        print esc($o->actual_temp_c).'</td><td>'.esc($o->actual_time_min).'/'.esc($o->actual_time_days).'</td><td></td>';
    }
    print '</tr>';
  }
}
if (!empty($user->rights->brewmo->write)){
print '<tr><td colspan="9"><hr></td></tr>';
print '<tr><form method="post">'
    .'<input type="hidden" name="token" value="'.$tok.'">'
    .'<input type="hidden" name="action" value="start_step">'
    .'<input type="hidden" name="id" value="'.$id.'">';
print '<td><select name="s_type"><option>mash</option><option>boil</option><option>ferm</option><option>carb</option><option>pasteur</option></select></td>';
print '<td><input type="text" name="s_name" placeholder="navn/step"></td>';
print '<td colspan="3"><input type="text" name="s_at" placeholder="YYYY-mm-dd HH:ii (valgfri)"></td>';
print '<td colspan="2"></td>';
print '<td class="right" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Start").'"></td>';
print '</form></tr>';
}
print '</table></div>';

// ---------- MEASUREMENTS ----------
print '<a id="measure"></a>';
print '<br><div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="8">'.$langs->trans("Målinger").' <span class="opacitymedium">(Temp & Gravity vises som små grafer)</span></td></tr>';
print '<tr class="liste_titre"><td>Stage</td><td>Metric</td><td>Værdi</td><td>Enhed</td><td>Tid</td><td>Note</td><td></td><td>Graf</td></tr>';

$meas = array();
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_measurement WHERE fk_brewsession=".$id." ORDER BY at ASC, rowid ASC";
$rs = $db->query($sql);
if ($rs){
  while($o=$db->fetch_object($rs)){
    $meas[] = $o;
    print '<tr>';
    print '<td>'.esc($o->stage).'</td>';
    print '<td>'.esc($o->metric).'</td>';
    print '<td>'.esc($o->value_numeric).'</td>';
    print '<td>'.esc($o->unit).'</td>';
    print '<td>'.esc($o->at).'</td>';
    print '<td>'.esc($o->note).'</td>';
    print '<td></td>';
    $needCanvas = in_array($o->metric, array('temp','gravity_sg')) ? '✓' : '';
    print '<td style="min-width:140px">'.($needCanvas?'(vises i grafen)':'').'</td>';
    print '</tr>';
  }
}
if (!empty($user->rights->brewmo->write)){
print '<tr><td colspan="8"><hr></td></tr>';
print '<tr><form method="post">'
    .'<input type="hidden" name="token" value="'.$tok.'">'
    .'<input type="hidden" name="action" value="add_measurement">'
    .'<input type="hidden" name="id" value="'.$id.'">';
print '<td><select name="m_stage"><option>mash</option><option>lauter</option><option>boil</option><option>whirlpool</option><option>fermentation</option><option>packaging</option></select></td>';
print '<td><select name="m_metric"><option>temp</option><option>ph</option><option>gravity_sg</option><option>pressure</option><option>do_ppm</option></select></td>';
print '<td><input type="number" step="0.0001" name="m_value" required></td>';
print '<td><input type="text" name="m_unit" placeholder="°C/pH/SG/bar/ppm"></td>';
print '<td><input type="text" name="m_at" placeholder="YYYY-mm-dd HH:ii" required></td>';
print '<td><input type="text" name="m_note" placeholder="note"></td>';
print '<td class="right" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Tilføj").'"></td>';
print '</form></tr>';
}
print '</table></div>';

// Små grafer (Temp & Gravity) – ren vanilla JS på <canvas>
$seriesTemp = array();
$seriesGrav = array();
foreach ($meas as $m) {
    $ts = strtotime($m->at?:'');
    if (!$ts) continue;
    if ($m->metric==='temp') $seriesTemp[] = array('t'=>$ts*1000,'v'=>(float)$m->value_numeric);
    if ($m->metric==='gravity_sg') $seriesGrav[] = array('t'=>$ts*1000,'v'=>(float)$m->value_numeric);
}
print '<div style="display:flex;gap:20px;flex-wrap:wrap;margin:8px 0 18px 0">';
print '<div><div class="opacitymedium">Temperatur</div><canvas id="miniTemp" width="360" height="120" style="border:1px solid #ddd"></canvas></div>';
print '<div><div class="opacitymedium">SG</div><canvas id="miniGrav" width="360" height="120" style="border:1px solid #ddd"></canvas></div>';
print '</div>';

print '<script>';
print 'const tempData = '.json_encode($seriesTemp).';';
print 'const gravData = '.json_encode($seriesGrav).';';
?>
(function(){
  function drawLine(canvasId, data){
    var c=document.getElementById(canvasId); if(!c||!data||!data.length) return;
    var ctx=c.getContext('2d');
    var W=c.width, H=c.height, pad=24;
    var xs=data.map(d=>d.t), ys=data.map(d=>d.v);
    var minx=Math.min.apply(null,xs), maxx=Math.max.apply(null,xs);
    var miny=Math.min.apply(null,ys), maxy=Math.max.apply(null,ys);
    if (minx===maxx){ minx-=60000; maxx+=60000; } // 1 min span
    if (miny===maxy){ miny-=0.1; maxy+=0.1; }

    function X(v){ return pad + (W-2*pad)*( (v-minx)/(maxx-minx) ); }
    function Y(v){ return H-pad - (H-2*pad)*( (v-miny)/(maxy-miny) ); }

    ctx.clearRect(0,0,W,H);
    ctx.strokeStyle='#ccc';
    ctx.beginPath(); ctx.moveTo(pad,H-pad); ctx.lineTo(W-pad,H-pad); ctx.moveTo(pad,pad); ctx.lineTo(pad,H-pad); ctx.stroke();

    ctx.beginPath();
    for(var i=0;i<data.length;i++){
      var x=X(data[i].t), y=Y(data[i].v);
      if(i==0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
    }
    ctx.strokeStyle='#333';
    ctx.lineWidth=1.5;
    ctx.stroke();
  }
  drawLine('miniTemp', tempData);
  drawLine('miniGrav', gravData);
})();
<?php
print '</script>';

// ---------- LOSSES ----------
print '<a id="loss"></a>';
print '<br><div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Tab/udbytte").'</td></tr>';
print '<tr class="liste_titre"><td>Type</td><td>Vol (L)</td><td>Tid</td><td>Note</td><td></td></tr>';

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_yield_loss WHERE fk_brewsession=".$id." ORDER BY at ASC, rowid ASC";
$rs = $db->query($sql);
if ($rs){
  while($o=$db->fetch_object($rs)){
    print '<tr>';
    print '<td>'.esc($o->loss_type).'</td>';
    print '<td>'.esc($o->volume_l).'</td>';
    print '<td>'.esc($o->at).'</td>';
    print '<td>'.esc($o->note).'</td>';
    print '<td></td>';
    print '</tr>';
  }
}
if (!empty($user->rights->brewmo->write)){
print '<tr><td colspan="5"><hr></td></tr>';
print '<tr><form method="post">'
    .'<input type="hidden" name="token" value="'.$tok.'">'
    .'<input type="hidden" name="action" value="add_loss">'
    .'<input type="hidden" name="id" value="'.$id.'">';
print '<td><select name="l_type"><option>trub</option><option>deadspace</option><option>spill</option><option>evap</option><option>filterloss</option><option>other</option></select></td>';
print '<td><input type="number" step="0.01" name="l_volume" required></td>';
print '<td><input type="text" name="l_at" placeholder="YYYY-mm-dd HH:ii" required></td>';
print '<td><input type="text" name="l_note" placeholder="note"></td>';
print '<td class="right"><input type="submit" class="button" value="'.$langs->trans("Tilføj").'"></td>';
print '</form></tr>';
}
print '</table></div>';

// ---------- VARIANCE ----------
print '<a id="variance"></a>';
print '<br><div class="div-table-responsive-no-min"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Variansrapport (Plan vs. Faktisk)").'</td></tr>';
print '<tr class="liste_titre"><td>Produkt/ref</td><td>Plan</td><td>Faktisk</td><td>Enhed</td><td>Afvigelse</td></tr>';

$rows = array();
$sql = "SELECT product_ref, fk_product, unit,
        SUM(COALESCE(plan_qty,0)) as plan_sum,
        SUM(COALESCE(actual_qty,0)) as act_sum
        FROM ".MAIN_DB_PREFIX."brew_consumption
        WHERE fk_brewsession=".$id."
        GROUP BY product_ref, fk_product, unit
        ORDER BY product_ref";
$rs = $db->query($sql);
if ($rs && $db->num_rows($rs)>0){
    while($o=$db->fetch_object($rs)){
        $key = ($o->product_ref?:('ID#'.$o->fk_product)).'|'.$o->unit;
        $rows[$key] = array(
            'label' => ($o->product_ref?:('ID#'.$o->fk_product)),
            'plan'  => (float)$o->plan_sum,
            'act'   => (float)$o->act_sum,
            'unit'  => $o->unit?:'',
        );
    }
} else {
    // fallback (kun visning) fra recipe
    $rid = (int)$bs->fk_recipe;
    $sqls = array(
      "SELECT product_ref as ref, SUM(amount_kg) as qty, 'kg' as unit FROM ".MAIN_DB_PREFIX."brew_recipe_malts WHERE fk_recipe=".$rid." GROUP BY product_ref",
      "SELECT product_ref as ref, SUM(grams) as qty, 'g'  as unit FROM ".MAIN_DB_PREFIX."brew_recipe_hops  WHERE fk_recipe=".$rid." GROUP BY product_ref",
      "SELECT product_ref as ref, SUM(grams) as qty, 'g'  as unit FROM ".MAIN_DB_PREFIX."brew_recipe_yeasts WHERE fk_recipe=".$rid." GROUP BY product_ref",
      "SELECT product_ref as ref, SUM(grams) as qty, 'g'  as unit FROM ".MAIN_DB_PREFIX."brew_recipe_extras WHERE fk_recipe=".$rid." GROUP BY product_ref",
    );
    foreach($sqls as $qq){
        $rr = $db->query($qq);
        if($rr){
            while($o=$db->fetch_object($rr)){
                $key = ($o->ref?:'(uden ref)').'|'.$o->unit;
                if (!isset($rows[$key])) $rows[$key]=array('label'=>$o->ref?:'(uden ref)','plan'=>0,'act'=>0,'unit'=>$o->unit);
                $rows[$key]['plan'] += (float)$o->qty;
            }
        }
    }
}
$total_plan = 0; $total_act = 0;
foreach($rows as $r){
    $dev = ($r['act'] - $r['plan']);
    print '<tr><td>'.esc($r['label']).'</td><td>'.round($r['plan'],3).'</td><td>'.round($r['act'],3).'</td><td>'.esc($r['unit']).'</td><td>'.round($dev,3).'</td></tr>';
    $total_plan += $r['plan'];
    $total_act  += $r['act'];
}
print '<tr><td><b>'.$langs->trans("I alt (uens enheder summeres vejledende)").'</b></td><td><b>'.round($total_plan,3).'</b></td><td><b>'.round($total_act,3).'</b></td><td></td><td><b>'.round(($total_act-$total_plan),3).'</b></td></tr>';
print '</table></div>';

llxFooter();
