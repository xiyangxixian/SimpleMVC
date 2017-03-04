<?php

namespace core;

class Session {
    
    private static $instance=null;

    private function __construct(){
        define('SESSION_TIMEOUT',config('SESSION_TIMEOUT'));
    }
    
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Session();
        }
        return self::$instance;
    }
    
    public function init(){
        if(!isset($_SESSION['this_session_timeout'])){
            $_SESSION['session_timeout']=array();
            $_SESSION['this_session_timeout']=time()+SESSION_TIMEOUT;
            return;
        }
        if($_SESSION['this_session_timeout']<time()){
            $_SESSION=array();
            $_SESSION['session_timeout']=array();
            $_SESSION['this_session_timeout']=time()+SESSION_TIMEOUT;
            return;
        }
        $_SESSION['this_session_timeout']=time()+SESSION_TIMEOUT;
    }


    public function start(){
        if(!$this->isActive()){
            session_start();
            $this->init();
        }
    }
    
    public function close(){
        session_register_shutdown();
    }
    
    public function destroy(){
        session_destroy();
    }
    
    public function get($key,$default=null){
        return $this->has($key)&&Validate::required($_SESSION[$key])?$_SESSION[$key]:$default;
    }
    
    public function set($key,$value,$timeout=null){
        $_SESSION[$key]=$value;
        if($timeout!=null){
            $_SESSION['session_timeout'][$key]=time()+$timeout;
        }
        return $this;
    }
    
    public function remove($key){
        unset($_SESSION[$key]);
        unset($_SESSION['session_timeout'][$key]);
        return $this;
    }
    
    public function has($key){
        if(!isset($_SESSION['session_timeout'][$key])){
            return isset($_SESSION[$key]);
        }
        $timeout=$_SESSION['session_timeout'][$key];
        if($timeout<time()){
            $this->remove($key);
        }
        return isset($_SESSION[$key]);
    }
    
    public function isActive(){
        return session_status()==PHP_SESSION_ACTIVE?true:false;
    }
    
}
