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

        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, label, capacity_l, tank_type, location, is_active, note_public, note_private";
        $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            (int) getEntity($this->table_element),
            $this->ref,
            $this->label,
            (float) $this->capacity_l,
            $this->tank_type,
            $this->location,
            (int) $this->is_active,
            $this->note_public,
            $this->note_private
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

    public function update($user, $notrigger = false)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " ref = ?, label = ?, capacity_l = ?, tank_type = ?, location = ?, is_active = ?, note_public = ?, note_private = ?";
        $sql .= " WHERE rowid = ?";

        $params = array(
            $this->ref,
            $this->label,
            (float) $this->capacity_l,
            $this->tank_type,
            $this->location,
            (int) $this->is_active,
            $this->note_public,
            $this->note_private,
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
