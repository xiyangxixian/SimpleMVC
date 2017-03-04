<?php


namespace core;

class Cookie {
    
    private static $instance=null;
    private $cookie;
    
    private function __construct() {
        $this->cookie=escape_input($_COOKIE);
    }
    
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Cookie();
        }
        return self::$instance;
    }
    
    public function get($key=null,$default=null){
        if($key==null){
            return $this->cookie;
        }
        return $this->has($key)&&Validate::required($this->cookie[$key])?$this->cookie[$key]:$default;
    }
    
    public function getInt($key=null,$default=0){
        if($key==null){
            return intval_array($this->cookie);
        }
        if($this->hasGet($key)&&Validate::required($this->cookie[$key])){
            if(is_array($this->cookie[$key])){
                return intval_array($this->cookie[$key]);
            }else{
                return intval($this->cookie[$key]);
            }
        }
        return $default;
    }
    
    public function getFloat($key=null,$default=0){
        if($key==null){
            return floatval_array($this->cookie);
        }
        if($this->hasGet($key)&&Validate::required($this->cookie[$key])){
            if(is_array($this->cookie[$key])){
                return floatval_array($this->cookie[$key]);
            }else{
                return floatval($this->cookie[$key]);
            }
        }
        return $default;
    }
    
    public function getDouble($key=null,$default=0){
        if($key==null){
            return doubleval_array($this->cookie);
        }
        if($this->hasGet($key)&&Validate::required($this->cookie[$key])){
            if(is_array($this->cookie[$key])){
                return doubleval_array($this->cookie[$key]);
            }else{
                return doubleval($this->cookie[$key]);
            }
        }
        return $default;
    }
    
    public function set($key,$value,$time=null){
        $time==null?setcookie($key, $value):setcookie($key,$value,$time);
        return $this;
    }
    
    public function has($key){
        return isset($this->cookie[$key]);
    }
    
}
