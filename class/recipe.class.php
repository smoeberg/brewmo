<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class BrewmoRecipe extends CommonObject
{
    public $element       = 'brew_recipe';
    public $table_element = 'brew_recipes';
    public $picto         = 'generic';
    public $ismultientitymanaged = 1;

    public $id;
    public $ref;
    public $label;
    public $fk_product;
    public $abv;
    public $ibu;
    public $color_ebc;
    public $og_sg;
    public $fg_sg;
    public $batch_volume_l;
    public $description;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = false)
    {
        $this->db->begin();

        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description, datec";
        $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = array(
            (int) getEntity($this->table_element),
            $this->ref,
            $this->label,
            $this->fk_product > 0 ? (int) $this->fk_product : null,
            $this->abv !== null ? (float) $this->abv : null,
            $this->ibu !== null ? (float) $this->ibu : null,
            $this->color_ebc !== null ? (float) $this->color_ebc : null,
            $this->og_sg !== null ? (float) $this->og_sg : null,
            $this->fg_sg !== null ? (float) $this->fg_sg : null,
            $this->batch_volume_l !== null ? (float) $this->batch_volume_l : null,
            $this->description
        );

        if (!$this->db->query($sql, $params)) {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        $this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
        $this->db->commit();

        return $this->id;
    }

    public function fetch($id, $ref = '')
    {
        // Bruger prepared statements for at forhindre SQL Injection
        if ($id > 0) {
            $sql = "SELECT * FROM ".$this->db->prefix().$this->table_element." WHERE rowid = ?";
            $params = array((int) $id);
        } else {
            $sql = "SELECT * FROM ".$this->db->prefix().$this->table_element." WHERE ref = ?";
            $params = array($ref);
        }

        $resql = $this->db->query($sql, $params);
        if (!$resql || !$this->db->num_rows($resql)) return 0;

        $obj = $this->db->fetch_object($resql);
        $this->id             = $obj->rowid;
        $this->ref            = $obj->ref;
        $this->label          = $obj->label;
        $this->fk_product     = $obj->fk_product;
        $this->abv            = $obj->abv;
        $this->ibu            = $obj->ibu;
        $this->color_ebc      = $obj->color_ebc;
        $this->og_sg          = $obj->og_sg;
        $this->fg_sg          = $obj->fg_sg;
        $this->batch_volume_l = $obj->batch_volume_l;
        $this->description    = $obj->description;

        return 1;
    }

    public function update($user, $notrigger = false)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " ref = ?, label = ?, fk_product = ?, abv = ?, ibu = ?, color_ebc = ?, og_sg = ?, fg_sg = ?, batch_volume_l = ?, description = ?";
        $sql .= " WHERE rowid = ?";

        $params = array(
            $this->ref,
            $this->label,
            $this->fk_product > 0 ? (int) $this->fk_product : null,
            $this->abv !== null ? (float) $this->abv : null,
            $this->ibu !== null ? (float) $this->ibu : null,
            $this->color_ebc !== null ? (float) $this->color_ebc : null,
            $this->og_sg !== null ? (float) $this->og_sg : null,
            $this->fg_sg !== null ? (float) $this->fg_sg : null,
            $this->batch_volume_l !== null ? (float) $this->batch_volume_l : null,
            $this->description,
            (int) $this->id
        );

        if (!$this->db->query($sql, $params)) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    public function delete($user, $notrigger = false)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "DELETE FROM ".$this->db->prefix().$this->table_element." WHERE rowid = ?";
        $params = array((int) $this->id);

        if (!$this->db->query($sql, $params)) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }
}
