<?php
class BrewmoPackLine {
    public $db,$id,$ref,$label,$line_type,$units_per_hour,$enabled,$note;
    function __construct($db){ $this->db=$db; }
    function fetch($id){ $res=$this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE rowid=".(int)$id); if($res&&$o=$this->db->fetch_object($res)){ foreach($o as $k=>$v)$this->$k=$v; $this->id=$o->rowid; return 1;} return -1; }
    function create($user){ global $conf; $sql="INSERT INTO ".MAIN_DB_PREFIX."brew_packaging_lines(entity,ref,label,line_type,units_per_hour,enabled,note) VALUES(".(int)$conf->entity.",'".$this->db->escape($this->ref)."','".$this->db->escape($this->label)."','".$this->db->escape($this->line_type)."',".($this->units_per_hour!==null?(float)$this->units_per_hour:"NULL").",".(int)$this->enabled.",'".$this->db->escape($this->note)."')"; if($this->db->query($sql)){ $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.'brew_packaging_lines'); return $this->id;} return -1; }
    function update($user){ $sql="UPDATE ".MAIN_DB_PREFIX."brew_packaging_lines SET ref='".$this->db->escape($this->ref)."',label='".$this->db->escape($this->label)."',line_type='".$this->db->escape($this->line_type)."',units_per_hour=".($this->units_per_hour!==null?(float)$this->units_per_hour:"NULL").",enabled=".(int)$this->enabled.",note='".$this->db->escape($this->note)."' WHERE rowid=".(int)$this->id; return $this->db->query($sql)?1:-1; }
    function delete($user){ return $this->db->query("DELETE FROM ".MAIN_DB_PREFIX."brew_packaging_lines WHERE rowid=".(int)$this->id)?1:-1; }
}
