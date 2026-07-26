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
$_GET['leftmenu']  = 'brewmo_tanks';

dol_include_once('/brewmo/class/brewtank.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

$action = GETPOST('action', 'alpha');
$id     = GETPOSTINT('id');

$object = new BrewTank($db);

if ($id > 0) {
    $object->fetch($id);
}

if ($action == 'save' && $user->rights->brewmo->write) {
    $object->ref          = GETPOST('ref', 'alphanohtml');
    $object->label        = GETPOST('label', 'alphanohtml');
    $object->capacity_l   = (float) GETPOST('capacity_l', 'alpha');
    $object->tank_type    = GETPOST('tank_type', 'alphanohtml');
    $object->location     = GETPOST('location', 'alphanohtml');
    $object->is_active    = GETPOSTINT('is_active') ? 1 : 0;
    $object->note_public  = GETPOST('note_public', 'restricthtml');
    $object->note_private = GETPOST('note_private', 'restricthtml');

    if ($object->id > 0) {
        $sql = "UPDATE ".$db->prefix().$object->table_element." SET ";
        $sql.= "ref='".$db->escape($object->ref)."',";
        $sql.= "label='".$db->escape($object->label)."',";
        $sql.= "capacity_l=".(float)$object->capacity_l.",";
        $sql.= "tank_type='".$db->escape($object->tank_type)."',";
        $sql.= "location='".$db->escape($object->location)."',";
        $sql.= "is_active=".(int)$object->is_active.",";
        $sql.= "note_public='".$db->escape($object->note_public)."',";
        $sql.= "note_private='".$db->escape($object->note_private)."'";
        $sql.= " WHERE rowid=".(int)$object->id;
        $db->query($sql);
    } else {
        $object->create($user);
    }

    header('Location: '.dol_buildpath('/brewmo/www/tank_list.php', 1));
    exit;
}

llxHeader('', $langs->trans("BrewmoTank"));

if ($object->id > 0) {
    print load_fiche_titre($langs->trans("BrewmoTank").' '.$object->ref);
} else {
    print load_fiche_titre($langs->trans("NewBrewmoTank"));
}

print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';
if ($object->id > 0) {
    print '<input type="hidden" name="id" value="'.(int)$object->id.'">';
}
print '<table class="border centpercent">';
print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td><input type="text" class="flat" name="ref" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" class="flat" name="label" value="'.dol_escape_htmltag($object->label).'"></td></tr>';
print '<tr><td>'.$langs->trans("Capacity").'</td><td><input type="text" class="flat" name="capacity_l" value="'.dol_escape_htmltag($object->capacity_l).'"> L</td></tr>';
print '<tr><td>'.$langs->trans("Type").'</td><td><input type="text" class="flat" name="tank_type" value="'.dol_escape_htmltag($object->tank_type).'"></td></tr>';
print '<tr><td>'.$langs->trans("Location").'</td><td><input type="text" class="flat" name="location" value="'.dol_escape_htmltag($object->location).'"></td></tr>';
print '<tr><td>'.$langs->trans("Status").'</td><td><input type="checkbox" name="is_active" value="1" '.($object->is_active?'checked':'').'> '.$langs->trans("Active").'</td></tr>';
print '<tr><td>'.$langs->trans("Note").'</td><td><textarea class="flat" name="note_public" rows="3">'.dol_escape_htmltag($object->note_public).'</textarea></td></tr>';
print '<tr><td>'.$langs->trans("NotePrivate").'</td><td><textarea class="flat" name="note_private" rows="3">'.dol_escape_htmltag($object->note_private).'</textarea></td></tr>';
print '</table>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print '</form>';

llxFooter();
