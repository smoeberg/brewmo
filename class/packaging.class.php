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

        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
        $sql .= "entity, fk_session, fk_product, fk_warehouse, qty, lot, datem";
        $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            (int) getEntity($this->table_element),
            (int) $this->fk_session,
            (int) $this->fk_product,
            (int) $this->fk_warehouse,
            (float) $this->qty,
            $this->lot,
            $this->datem ? $this->db->idate($this->datem) : null
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

    public function fetch($id)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "SELECT * FROM ".$this->db->prefix().$this->table_element." WHERE rowid = ?";
        $resql = $this->db->query($sql, [(int)$id]);
        if (!$resql || !$this->db->num_rows($resql)) return 0;

        $obj = $this->db->fetch_object($resql);
        $this->id           = $obj->rowid;
        $this->fk_session   = $obj->fk_session;
        $this->fk_product   = $obj->fk_product;
        $this->fk_warehouse = $obj->fk_warehouse;
        $this->qty          = $obj->qty;
        $this->lot          = $obj->lot;
        $this->datem        = $this->db->jdate($obj->datem);

        return 1;
    }

    public function update($user, $notrigger = false)
    {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " fk_session = ?, fk_product = ?, fk_warehouse = ?, qty = ?, lot = ?, datem = ?";
        $sql .= " WHERE rowid = ?";

        $params = array(
            (int) $this->fk_session,
            (int) $this->fk_product,
            (int) $this->fk_warehouse,
            (float) $this->qty,
            $this->lot,
            $this->datem ? $this->db->idate($this->datem) : null,
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
