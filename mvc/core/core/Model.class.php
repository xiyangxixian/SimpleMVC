<?php

namespace core;

class Model {
    
    protected $attribute;
    protected $db;
    
    public function __construct(array $attribute=array()) {
        $this->attribute=$attribute;
        $this->db=db(static::table());
        $this->init();
    }
    
    public function init(){}
    
    protected static function table(){
        $name=strrchr(get_class($this),'\\');
        return hump_to_small(substr($name,1));
    }

    public function get($key=null,$default=null) {
        if($key==null){
            return $this->attribute;
        }
        return isset($this->attribute[$key])&&Validate::required($this->attribute[$key])?$this->attribute[$key]:$default;
    }

    public function set($mixed,$value=null) {
        if(is_array($mixed)){
            $this->attribute=array_merge($this->attribute,$mixed);
            return $this;
        }
        $this->attribute[$mixed]=$value;
        return $this;
    }
    
    public function add(){
        return $this->db->add($this->attribute);
    }
    
    public function find(){
        foreach ($this->attribute as $key=>$value){
            $this->db->where($key,$value);
        }
        if(($row=$this->db->find())==null){
            return null;
        }
        $this->attribute=array_merge($this->attribute,$row);
        return $this;
    }
    
    public function delete(){
        foreach ($this->attribute as $key=>$value){
           $this->db->where($key,$value);
        }
        return $this->db->delete();
    }
    
    public function update(){
        return $this->db->update($this->attribute);
    }
    
    public function where($column,$mixed,$param){
        $this->db->where($column,$mixed,$param);
        return $this;
    }
    
    public function xwhere($column,$mixed,$param){
        $this->db->xwhere($column,$mixed,$param);
        return $this;
    }
    
    public static function db(){
        return db(self::table());
    }
    
}
