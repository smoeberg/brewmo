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

        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, ref, fk_recipe, fk_tank, status, volume_l, note_public, note_private, datec, date_start, date_end";
        $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

        $params = array(
            (int) getEntity($this->table_element),
            $this->ref,
            (int) $this->fk_recipe,
            $this->fk_tank > 0 ? (int) $this->fk_tank : null,
            (int) $this->status,
            $this->volume_l !== null ? (float) $this->volume_l : null,
            $this->note_public,
            $this->note_private,
            $this->date_start ? $this->db->idate($this->date_start) : null,
            $this->date_end ? $this->db->idate($this->date_end) : null
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

    public function update($user, $notrigger = false)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " ref = ?, fk_recipe = ?, fk_tank = ?, status = ?, volume_l = ?, note_public = ?, note_private = ?, date_start = ?, date_end = ?";
        $sql .= " WHERE rowid = ?";

        $params = array(
            $this->ref,
            (int) $this->fk_recipe,
            $this->fk_tank > 0 ? (int) $this->fk_tank : null,
            (int) $this->status,
            $this->volume_l !== null ? (float) $this->volume_l : null,
            $this->note_public,
            $this->note_private,
            $this->date_start ? $this->db->idate($this->date_start) : null,
            $this->date_end ? $this->db->idate($this->date_end) : null,
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
