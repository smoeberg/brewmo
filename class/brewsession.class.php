<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class BrewmoBrewSession extends CommonObject
{
    public $element       = 'brew_brewsession';
    public $table_element = 'brew_brewsession';
    public $picto         = 'generic';
    public $ismultientitymanaged = 1;

    public $id;
    public $ref;
    public $fk_recipe;
    public $fk_tank;
    public $status;
    public $volume_l;
    public $note_public;
    public $note_private;
    public $date_start;
    public $date_end;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = false)
    {
        global $conf;

        $this->db->begin();

        if (empty($this->ref)) {
            $this->ref = 'B'.date('ymdHis');
        }

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, fk_recipe, fk_tank, status, volume_l, note_public, note_private, datec, date_start, date_end";
        $sql .= ") VALUES (";
        $sql .= (int) getEntity($this->table_element).", ";
        $sql .= "'".$this->db->escape($this->ref)."', ";
        $sql .= (int) $this->fk_recipe.", ";
        $sql .= ($this->fk_tank > 0 ? (int) $this->fk_tank : "NULL").", ";
        $sql .= (int) $this->status.", ";
        $sql .= ($this->volume_l !== null ? (float) $this->volume_l : "NULL").", ";
        $sql .= "'".$this->db->escape($this->note_public)."', ";
        $sql .= "'".$this->db->escape($this->note_private)."', ";
        $sql .= "NOW(), ";
        $sql .= ($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "NULL").", ";
        $sql .= ($this->date_end   ? "'".$this->db->idate($this->date_end)."'"   : "NULL");
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
        $this->fk_recipe   = $obj->fk_recipe;
        $this->fk_tank     = $obj->fk_tank;
        $this->status      = $obj->status;
        $this->volume_l    = $obj->volume_l;
        $this->note_public = $obj->note_public;
        $this->note_private= $obj->note_private;
        $this->date_start  = $this->db->jdate($obj->date_start);
        $this->date_end    = $this->db->jdate($obj->date_end);

        return 1;
    }
}
