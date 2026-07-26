<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class BrewTank extends CommonObject
{
    public $element       = 'brewtank';
    public $table_element = 'brew_tank';
    public $picto         = 'generic';
    public $ismultientitymanaged = 1;

    public $id;
    public $ref;
    public $label;
    public $capacity_l;
    public $tank_type;
    public $location;
    public $is_active;
    public $note_public;
    public $note_private;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = false)
    {
        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, label, capacity_l, tank_type, location, is_active, note_public, note_private";
        $sql .= ") VALUES (";
        $sql .= (int) getEntity($this->table_element).", ";
        $sql .= "'".$this->db->escape($this->ref)."', ";
        $sql .= "'".$this->db->escape($this->label)."', ";
        $sql .= (float) $this->capacity_l.", ";
        $sql .= "'".$this->db->escape($this->tank_type)."', ";
        $sql .= "'".$this->db->escape($this->location)."', ";
        $sql .= (int) $this->is_active.", ";
        $sql .= "'".$this->db->escape($this->note_public)."', ";
        $sql .= "'".$this->db->escape($this->note_private)."'";
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
        $this->id          = $obj->rowid;
        $this->ref         = $obj->ref;
        $this->label       = $obj->label;
        $this->capacity_l  = $obj->capacity_l;
        $this->tank_type   = $obj->tank_type;
        $this->location    = $obj->location;
        $this->is_active   = $obj->is_active;
        $this->note_public = $obj->note_public;
        $this->note_private= $obj->note_private;

        return 1;
    }
}
