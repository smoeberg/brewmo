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
$_GET['leftmenu']  = 'brewmo_recipes';

dol_include_once('/brewmo/class/recipe.class.php');
dol_include_once('/brewmo/class/brewcalc.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->write)) accessforbidden();

$action = GETPOST('action', 'alpha');
$id     = GETPOSTINT('id');

$object = new BrewmoRecipe($db);

if ($id > 0) {
    $object->fetch($id);
}

if ($action == 'save') {
    $object->ref            = GETPOST('ref', 'alphanohtml');
    $object->label          = GETPOST('label', 'alphanohtml');
    $object->fk_product     = GETPOSTINT('fk_product');
    $object->og_sg          = (float) GETPOST('og_sg', 'alpha');
    $object->fg_sg          = (float) GETPOST('fg_sg', 'alpha');
    $object->batch_volume_l = (float) GETPOST('batch_volume_l', 'alpha');
    $object->description    = GETPOST('description', 'restricthtml');

    $object->abv = BrewmoCalc::abv($object->og_sg, $object->fg_sg);

    if ($object->id > 0) {
        // simple update
        $sql = "UPDATE ".$db->prefix().$object->table_element." SET ";
        $sql.= "ref='".$db->escape($object->ref)."',";
        $sql.= "label='".$db->escape($object->label)."',";
        $sql.= "fk_product=".($object->fk_product>0?(int)$object->fk_product:"NULL").",";
        $sql.= "og_sg=".($object->og_sg?:'NULL').",";
        $sql.= "fg_sg=".($object->fg_sg?:'NULL').",";
        $sql.= "batch_volume_l=".($object->batch_volume_l?:'NULL').",";
        $sql.= "abv=".($object->abv!==null?(float)$object->abv:'NULL').",";
        $sql.= "description='".$db->escape($object->description)."'";
        $sql.= " WHERE rowid=".(int)$object->id;
        $db->query($sql);
    } else {
        $object->create($user);
    }

    header('Location: '.dol_buildpath('/brewmo/www/recipe_list.php', 1));
    exit;
}

llxHeader('', $langs->trans("Recipe"));

print load_fiche_titre($langs->trans("Recipe"));

print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';
if ($object->id > 0) {
    print '<input type="hidden" name="id" value="'.(int)$object->id.'">';
}
print '<table class="border centpercent">';
print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td><input class="flat" type="text" name="ref" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';
print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" name="label" value="'.dol_escape_htmltag($object->label).'"></td></tr>';
print '<tr><td>'.$langs->trans("OGSG").'</td><td><input class="flat" type="text" name="og_sg" value="'.dol_escape_htmltag($object->og_sg).'"></td></tr>';
print '<tr><td>'.$langs->trans("FGSG").'</td><td><input class="flat" type="text" name="fg_sg" value="'.dol_escape_htmltag($object->fg_sg).'"></td></tr>';
print '<tr><td>'.$langs->trans("BatchVolume").'</td><td><input class="flat" type="text" name="batch_volume_l" value="'.dol_escape_htmltag($object->batch_volume_l).'"> L</td></tr>';
print '<tr><td>'.$langs->trans("Description").'</td><td><textarea class="flat" name="description" rows="4">'.dol_escape_htmltag($object->description).'</textarea></td></tr>';
print '</table>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</form>';

llxFooter();
