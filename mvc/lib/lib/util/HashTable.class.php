<?php

namespace lib\util;
use Iterator;

class HashTable implements Iterator,ArrayHelper{
    
    private $array;
    
    public function __construct(array $array) {
        $this->array=$array;
    }


    public function current() {
        return current($this->array);
    }

    public function key() {
        return key($this->array);
    }

    public function next() {
        return next($this->array);
    }

    public function rewind() {
        return reset($this->array);
    }

    public function valid() {
        return key($this->array)!==null;
    }
    
    public function len(){
        return count($this->array);
    }

    public function add($value) {
        $this->array[]=$value;
        return $this;
    }
    
    public function extend(array $arr){
        array_merge($this->array,$arr);
        return $this;
    }

    public function get($key=null,$default=null) {
        if($key==null){
            return $this->array;
        }
        return $this->has($key)?$this->array[$key]:$default;
    }

    public function has($key) {
        return isset($this->array[$key]);
    }

    public function remove($key) {
        unset($this->array[$key]);
        return $this;
    }

    public function removeObject($value) {
        $key=array_search($value,$this->array);
        if($key){
            unset($key);
        }
        return $this;
    }

    public function set($key, $value) {
        $this->array[$key]=$value;
    }

}
