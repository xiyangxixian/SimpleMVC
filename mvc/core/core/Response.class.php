<?php

namespace core;

class Response {
    
    private static $instance=null;
    
    private function __construct(){
        
    }
    
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Response();
        }
        return self::$instance;
    }
    
    public function header($header){
        header($header);
        return $this;
    }
    
    public function redirect($url,$code=302){
        $this->code($code)->header('Location:'.$url);
        exit();
    }
    
    public function send($path,$msg=''){
        include $path;
        exit();
    }
    
    public function error($path,$msg='',$code=500){
        $this->code($code)->send($path,$msg);
    }
    
    public function noFound($path,$msg='',$code=404){
        $this->code($code)->send($path,$msg);
    }

    public function code($code){
        http_response_code($code);
        return $this;
    }
    
}
