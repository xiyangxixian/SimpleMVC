<?php

namespace lib\util;

class Macher {
    
    private $regex;

    private function __construct($regex){
        $this->regex=$regex;
    }
    
    public static function compile ($regex){
        return new Macher($regex);
    }
    
    public function matchAll($str){
        $m=null;
        $len=preg_match_all($this->regex,$str,$m);
        for($i=0;$i<$len;$i++){
            $item=array();
            foreach($m as $value){
                $item[]=$value[$i];
            }
            yield $item;
        }
    }    
    
}
