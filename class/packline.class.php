<?php
class BrewmoPackLine {
    public $db, $id, $ref, $label, $line_type, $units_per_hour, $enabled, $note;
    
    function __construct($db) { 
        $this->db = $db; 
    }
    
    function fetch($id) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE rowid = ?";
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
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."brew_packaging_lines(";
        $sql .= "entity, ref, label, line_type, units_per_hour, enabled, note)";
        $sql .= " VALUES(?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            (int)$conf->entity,
            $this->ref,
            $this->label,
            $this->line_type,
            $this->units_per_hour !== null ? (float)$this->units_per_hour : null,
            (int)$this->enabled,
            $this->note
        ];
        
        if ($this->db->query($sql, $params)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'brew_packaging_lines');
            return $this->id;
        }
        return -1;
    }
    
    function update($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".MAIN_DB_PREFIX."brew_packaging_lines SET ";
        $sql .= "ref = ?, label = ?, line_type = ?, units_per_hour = ?, enabled = ?, note = ? ";
        $sql .= "WHERE rowid = ?";
        
        $params = [
            $this->ref,
            $this->label,
            $this->line_type,
            $this->units_per_hour !== null ? (float)$this->units_per_hour : null,
            (int)$this->enabled,
            $this->note,
            (int)$this->id
        ];
        
        return $this->db->query($sql, $params) ? 1 : -1;
    }
    
    function delete($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE rowid = ?";
        return $this->db->query($sql, [(int)$this->id]) ? 1 : -1;
    }
}
