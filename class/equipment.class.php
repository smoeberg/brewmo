<?php
class BrewmoEquipment {
    public $db, $id, $ref, $label, $efficiency_pct, $boiloff_l_h, $trub_loss_l, $lauter_deadspace_l, $mash_tun_l, $kettle_l, $note, $enabled;
    
    function __construct($db) { 
        $this->db = $db; 
    }
    
    function fetch($id) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."brew_equipment WHERE rowid = ?";
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
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."brew_equipment(";
        $sql .= "entity, ref, label, efficiency_pct, boiloff_l_h, trub_loss_l, lauter_deadspace_l, mash_tun_l, kettle_l, note, enabled)";
        $sql .= " VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            (int)$conf->entity,
            $this->ref,
            $this->label,
            $this->efficiency_pct !== null ? (float)$this->efficiency_pct : null,
            $this->boiloff_l_h !== null ? (float)$this->boiloff_l_h : null,
            $this->trub_loss_l !== null ? (float)$this->trub_loss_l : null,
            $this->lauter_deadspace_l !== null ? (float)$this->lauter_deadspace_l : null,
            $this->mash_tun_l !== null ? (float)$this->mash_tun_l : null,
            $this->kettle_l !== null ? (float)$this->kettle_l : null,
            $this->note,
            (int)$this->enabled
        ];
        
        if ($this->db->query($sql, $params)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'brew_equipment');
            return $this->id;
        }
        return -1;
    }
    
    function update($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "UPDATE ".MAIN_DB_PREFIX."brew_equipment SET ";
        $sql .= "ref = ?, label = ?, efficiency_pct = ?, boiloff_l_h = ?, trub_loss_l = ?, ";
        $sql .= "lauter_deadspace_l = ?, mash_tun_l = ?, kettle_l = ?, note = ?, enabled = ? ";
        $sql .= "WHERE rowid = ?";
        
        $params = [
            $this->ref,
            $this->label,
            $this->efficiency_pct !== null ? (float)$this->efficiency_pct : null,
            $this->boiloff_l_h !== null ? (float)$this->boiloff_l_h : null,
            $this->trub_loss_l !== null ? (float)$this->trub_loss_l : null,
            $this->lauter_deadspace_l !== null ? (float)$this->lauter_deadspace_l : null,
            $this->mash_tun_l !== null ? (float)$this->mash_tun_l : null,
            $this->kettle_l !== null ? (float)$this->kettle_l : null,
            $this->note,
            (int)$this->enabled,
            (int)$this->id
        ];
        
        return $this->db->query($sql, $params) ? 1 : -1;
    }
    
    function delete($user) {
        // Bruger prepared statements for at forhindre SQL Injection
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."brew_equipment WHERE rowid = ?";
        return $this->db->query($sql, [(int)$this->id]) ? 1 : -1;
    }
}
