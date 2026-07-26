<?php
class BrewmoVessel {
    public $db,$id,$ref,$label,$capacity_l,$type,$location,$enabled,$note,$status;
    function __construct($db){ $this->db=$db; }
    function fetch($id){ $res=$this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."brew_vessels WHERE rowid=".(int)$id); if($res&&$o=$this->db->fetch_object($res)){ foreach($o as $k=>$v)$this->$k=$v; $this->id=$o->rowid; return 1;} return -1; }
    function create($user){ global $conf; $sql="INSERT INTO ".MAIN_DB_PREFIX."brew_vessels(entity,ref,label,capacity_l,type,location,enabled,note,status) VALUES(".(int)$conf->entity.",'".$this->db->escape($this->ref)."','".$this->db->escape($this->label)."',".($this->capacity_l!==null?(float)$this->capacity_l:"NULL").",'".$this->db->escape($this->type)."','".$this->db->escape($this->location)."',".(int)$this->enabled.",'".$this->db->escape($this->note)."','available')"; if($this->db->query($sql)){ $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'brew_vessels'); return $this->id;} return -1; }
    function update($user){ $sql="UPDATE ".MAIN_DB_PREFIX."brew_vessels SET ref='".$this->db->escape($this->ref)."',label='".$this->db->escape($this->label)."',capacity_l=".($this->capacity_l!==null?(float)$this->capacity_l:"NULL").",type='".$this->db->escape($this->type)."',location='".$this->db->escape($this->location)."',enabled=".(int)$this->enabled.",note='".$this->db->escape($this->note)."',status='".$this->db->escape($this->status)."' WHERE rowid=".(int)$this->id; return $this->db->query($sql)?1:-1; }
    function delete($user){ return $this->db->query("DELETE FROM ".MAIN_DB_PREFIX."brew_vessels WHERE rowid=".(int)$this->id)?1:-1; }
}
