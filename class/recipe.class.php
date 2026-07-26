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

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, label, fk_product, abv, ibu, color_ebc, og_sg, fg_sg, batch_volume_l, description, datec";
        $sql .= ") VALUES (";
        $sql .= (int) getEntity($this->table_element).", ";
        $sql .= "'".$this->db->escape($this->ref)."', ";
        $sql .= "'".$this->db->escape($this->label)."', ";
        $sql .= ($this->fk_product > 0 ? (int) $this->fk_product : "NULL").", ";
        $sql .= ($this->abv !== null ? (float) $this->abv : "NULL").", ";
        $sql .= ($this->ibu !== null ? (float) $this->ibu : "NULL").", ";
        $sql .= ($this->color_ebc !== null ? (float) $this->color_ebc : "NULL").", ";
        $sql .= ($this->og_sg !== null ? (float) $this->og_sg : "NULL").", ";
        $sql .= ($this->fg_sg !== null ? (float) $this->fg_sg : "NULL").", ";
        $sql .= ($this->batch_volume_l !== null ? (float) $this->batch_volume_l : "NULL").", ";
        $sql .= "'".$this->db->escape($this->description)."', ";
        $sql .= "NOW()";
        $sql .= ")";

        if (!$this->db->query($sql)) {
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
        $sql = "SELECT * FROM ".$this->db->prefix().$this->table_element." WHERE ";
        if ($id > 0) {
            $sql .= "rowid = ".((int) $id);
        } else {
            $sql .= "ref = '".$this->db->escape($ref)."'";
        }

        $resql = $this->db->query($sql);
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
}
