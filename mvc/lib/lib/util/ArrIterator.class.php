<?php

namespace lib\util;
use Iterator;

abstract class ArrIterator implements Iterator{
   
    public function arr(){
        $arr=array();
        foreach ($this as $value){
           $arr[]=$value;
        }
        return $arr;
    }
    
    public function __toString() {
        return json_encode($this->arr());
    }
}
