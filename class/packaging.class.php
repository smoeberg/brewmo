<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class BrewmoPackaging extends CommonObject
{
    public $element       = 'brew_packaging';
    public $table_element = 'brew_packaging';
    public $picto         = 'generic';
    public $ismultientitymanaged = 1;

    public $id;
    public $fk_session;
    public $fk_product;
    public $fk_warehouse;
    public $qty;
    public $lot;
    public $datem;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = false)
    {
        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, fk_session, fk_product, fk_warehouse, qty, lot, datem";
        $sql .= ") VALUES (";
        $sql .= (int) getEntity($this->table_element).", ";
        $sql .= (int) $this->fk_session.", ";
        $sql .= (int) $this->fk_product.", ";
        $sql .= (int) $this->fk_warehouse.", ";
        $sql .= (float) $this->qty.", ";
        $sql .= ($this->lot ? "'".$this->db->escape($this->lot)."'" : "NULL").", ";
        $sql .= ($this->datem ? "'".$this->db->idate($this->datem)."'" : "NULL");
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
}
