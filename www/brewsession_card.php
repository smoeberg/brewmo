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
$_GET['leftmenu']  = 'brewmo_batches';

dol_include_once('/brewmo/class/brewsession.class.php');
dol_include_once('/brewmo/class/recipe.class.php');
dol_include_once('/brewmo/class/brewtank.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

$action = GETPOST('action', 'alpha');
$id     = GETPOSTINT('id');

$object = new BrewmoBrewSession($db);

if ($id > 0) {
    $object->fetch($id);
}

if ($action == 'save' && $user->rights->brewmo->write) {
    $object->fk_recipe   = GETPOSTINT('fk_recipe');
    $object->fk_tank     = GETPOSTINT('fk_tank');
    $object->volume_l    = (float) GETPOST('volume_l', 'alpha');
    $object->status      = GETPOSTINT('status');
    $object->note_public = GETPOST('note_public', 'restricthtml');
    $object->note_private= GETPOST('note_private', 'restricthtml');

    if ($object->id > 0) {
        $sql = "UPDATE ".$db->prefix().$object->table_element." SET ";
        $sql.= "fk_recipe=".(int)$object->fk_recipe.",";
        $sql.= "fk_tank=".($object->fk_tank>0?(int)$object->fk_tank:"NULL").",";
        $sql.= "volume_l=".($object->volume_l?:'NULL').",";
        $sql.= "status=".(int)$object->status.",";
        $sql.= "note_public='".$db->escape($object->note_public)."',";
        $sql.= "note_private='".$db->escape($object->note_private)."'";
        $sql.= " WHERE rowid=".(int)$object->id;
        $db->query($sql);
    } else {
        $object->ref = '';
        $object->create($user);
    }

    header('Location: '.dol_buildpath('/brewmo/www/brewsession_list.php', 1));
    exit;
}

llxHeader('', $langs->trans("BrewSession"));

print load_fiche_titre($langs->trans("BrewSession"));

print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';
if ($object->id > 0) {
    print '<input type="hidden" name="id" value="'.(int)$object->id.'">';
}
print '<table class="border centpercent">';
print '<tr><td>'.$langs->trans("Ref").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';

// Recipe select
print '<tr><td>'.$langs->trans("Recipe").'</td><td>';
print '<select name="fk_recipe" class="flat">';
print '<option value="0">&nbsp;</option>';
$sql = "SELECT rowid, ref, label FROM ".$db->prefix()."brew_recipes WHERE entity = ".((int)$conf->entity)." ORDER BY ref";
$resql = $db->query($sql);
while ($rec = $db->fetch_object($resql)) {
    $sel = ($object->fk_recipe == $rec->rowid ? ' selected' : '');
    print '<option value="'.$rec->rowid.'"'.$sel.'>'.dol_escape_htmltag($rec->ref.' - '.$rec->label).'</option>';
}
print '</select>';
print '</td></tr>';

// Tank select
print '<tr><td>'.$langs->trans("BrewmoTank").'</td><td>';
print '<select name="fk_tank" class="flat">';
print '<option value="0">&nbsp;</option>';
$sql = "SELECT rowid, ref, capacity_l FROM ".$db->prefix()."brew_tank WHERE entity = ".((int)$conf->entity)." AND is_active = 1 ORDER BY ref";
$resql = $db->query($sql);
while ($tk = $db->fetch_object($resql)) {
    $sel = ($object->fk_tank == $tk->rowid ? ' selected' : '');
    print '<option value="'.$tk->rowid.'"'.$sel.'>'.dol_escape_htmltag($tk->ref.' ('.$tk->capacity_l.' L)').'</option>';
}
print '</select>';
print '</td></tr>';

print '<tr><td>'.$langs->trans("Volume").'</td><td><input class="flat" type="text" name="volume_l" value="'.dol_escape_htmltag($object->volume_l).'"> L</td></tr>';
print '<tr><td>'.$langs->trans("Status").'</td><td><input class="flat" type="text" name="status" value="'.dol_escape_htmltag($object->status).'"></td></tr>';
print '<tr><td>'.$langs->trans("Note").'</td><td><textarea class="flat" name="note_public" rows="3">'.dol_escape_htmltag($object->note_public).'</textarea></td></tr>';
print '<tr><td>'.$langs->trans("NotePrivate").'</td><td><textarea class="flat" name="note_private" rows="3">'.dol_escape_htmltag($object->note_private).'</textarea></td></tr>';

print '</table>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</form>';

llxFooter();
