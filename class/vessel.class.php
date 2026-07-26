<?php
class BrewmoVessel {
    public $db, $id, $ref, $label, $capacity_l, $type, $location, $enabled, $note, $status;
    
    function __construct($db) { 
        $this->db = $db; 
    }
    
    function fetch($id) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_vessels WHERE rowid = ?";
        $res = $this->db->query($sql, [(int)$id]);
        if ($res && $o = $this->db->fetch_object($res)) {
            foreach ($o as $k => $v) $this->$k = $v;
            $this->id = $o->rowid;
            return 1;
        }
        return -1;
    }
    
    function create($user) {
        global $conf;
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."brew_vessels(entity, ref, label, capacity_l, type, location, enabled, note, status) ";
        $sql .= "VALUES(?, ?, ?, ?, ?, ?, ?, ?, 'available')";
        
        $params = [
            (int)$conf->entity,
            $this->ref,
            $this->label,
            $this->capacity_l !== null ? (float)$this->capacity_l : null,
            $this->type,
            $this->location,
            (int)$this->enabled,
            $this->note
        ];
        
        if ($this->db->query($sql, $params)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'brew_vessels');
            return $this->id;
        }
        return -1;
    }
    
    function update($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".MAIN_DB_PREFIX."brew_vessels SET ";
        $sql .= "ref = ?, label = ?, capacity_l = ?, type = ?, location = ?, enabled = ?, note = ?, status = ? ";
        $sql .= "WHERE rowid = ?";
        
        $params = [
            $this->ref,
            $this->label,
            $this->capacity_l !== null ? (float)$this->capacity_l : null,
            $this->type,
            $this->location,
            (int)$this->enabled,
            $this->note,
            $this->status,
            (int)$this->id
        ];
        
        return $this->db->query($sql, $params) ? 1 : -1;
    }
    
    function delete($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."brew_vessels WHERE rowid = ?";
        return $this->db->query($sql, [(int)$this->id]) ? 1 : -1;
    }
}
