<?php

namespace core;
use core\FileFilter;
use core\Validate;

class Request {
    
    private static $instance=null;
    private $get;
    private $post;
    private $server;
    private $url;
    private $absoluteUrl;
    private $module;
    private $controller;
    private $action;
    private $pathinfo;
    private $ip;
    private $header;
    
    private function __construct() {
        $this->get=escape_input($_GET);
        $this->post=escape_input($_POST);
        $this->server=$_SERVER;
        $this->pathinfo=htmlspecialchars(isset($this->server['PATH_INFO'])?$this->server['PATH_INFO']:'',ENT_QUOTES);
        $this->ip=$this->getClientIP();
        $this->url=htmlspecialchars($this->server['REQUEST_URI'],ENT_QUOTES);
        $port=$this->port()==80?'':':'.$this->port();
        $this->absoluteUrl=$this->server('REQUEST_SCHEME').'://'.$this->server['SERVER_NAME'].$port.$this->url(); 
        $this->init();
        $this->header=array(
            'host'=>isset($this->server['HTTP_HOST'])?$this->server['HTTP_HOST']:'',
            'connection'=>isset($this->server['HTTP_CONNECTION'])?$this->server['HTTP_CONNECTION']:'',
            'accept'=>isset($this->server['HTTP_ACCEPT'])?$this->server['HTTP_ACCEPT']:'',
            'accept_encoding'=>isset($this->server['HTTP_ACCEPT_ENCODING'])?$this->server['HTTP_ACCEPT_ENCODING']:'',
            'accept_language'=>isset($this->server['HTTP_ACCEPT_LANGUAGE'])?$this->server['HTTP_ACCEPT_LANGUAGE']:'',
            'upgrade_insecure_requests'=>isset($this->server['HTTP_UPGRADE_INSECURE_REQUESTS'])?$this->server['HTTP_UPGRADE_INSECURE_REQUESTS']:'',
            'user_agent'=>isset($this->server['HTTP_USER_AGENT'])?$this->server['HTTP_USER_AGENT']:'',
        );
    }
    
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Request();
        }
        return self::$instance;
    }
    
    private function init(){
        $routerMode=config('ROUTER_TYPE');
        if($routerMode=='default'){
            $this->module=self::formatM($this->get('m'));
            $this->controller=self::formatC($this->get('c'));
            $this->action=self::formatA($this->get('a')); 
        }else if($routerMode=='path_info'){
            $router=explode('/',$this->pathinfo);
            $this->module=self::formatM(isset($router[1])?$router[1]:null);
            $this->controller=self::formatC(isset($router[2])?$router[2]:null);
            $this->action=self::formatA(isset($router[3])?$router[3]:null);
        }
    }
    
    private function getClientIP(){
        if (getenv("HTTP_CLIENT_IP")){
            $ip = getenv("HTTP_CLIENT_IP");
        }
        else if(getenv("HTTP_X_FORWARDED_FOR")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        }
        else if(getenv("REMOTE_ADDR")){
            $ip = getenv("REMOTE_ADDR");
        }
        else {
            $ip = "Unknow";
        }
        return $ip;
    }
    
    public static function formatM($char){
        if(empty($char)){
            $out=explode(',',MODULE);
            return $out[0];
        }
        return $char;
    }

    public static function formatC($char){
        if(empty($char)){
            return config('ROUTER','DEFAULT_CONTROLLER');
        }
        return small_to_hump($char);
    }
    
    public static function formatA($char){
        if(empty($char)){
            return config('ROUTER','DEFAULT_ACTION');
        }
        return small_to_hump($char,false);
    }

    
    public function get($key=null,$default=null){
        if($key==null){
            return $this->get;
        }
        return $this->hasGet($key)&&Validate::required($this->get[$key])?$this->get[$key]:$default;
    }
    
    public function getInt($key=null,$default=0){
        if($key==null){
            return intval_array($this->get);
        }
        if($this->hasGet($key)&&Validate::required($this->get[$key])){
            if(is_array($this->get[$key])){
                return intval_array($this->get[$key]);
            }else{
                return intval($this->get[$key]);
            }
        }
        return $default;
    }
    
    public function getFloat($key=null,$default=0){
        if($key==null){
            return floatval_array($this->get);
        }
        if($this->hasGet($key)&&Validate::required($this->get[$key])){
            if(is_array($this->get[$key])){
                return floatval_array($this->get[$key]);
            }else{
                return floatval($this->get[$key]);
            }
        }
        return $default;
    }
    
    public function getDouble($key=null,$default=0){
        if($key==null){
            return doubleval_array($this->get);
        }
        if($this->hasGet($key)&&Validate::required($this->get[$key])){
            if(is_array($this->get[$key])){
                return doubleval_array($this->get[$key]);
            }else{
                return doubleval($this->get[$key]);
            }
        }
        return $default;
    }
    
    public function post($key=null,$default=null){
        if($key==null){
            return $this->post;
        }
        return $this->hasPost($key)&&Validate::required($this->post[$key])?$this->post[$key]:$default;
    }
    
    public function postInt($key=null,$default=0){
        if($key==null){
            return intval_array($this->post);
        }
        if($this->hasGet($key)){
            if(is_array($this->post[$key])&&Validate::required($this->post[$key])){
                return intval_array($this->post[$key]);
            }else{
                return intval($this->post[$key]);
            }
        }
        return $default;
    }
    
    public function postFloat($key=null,$default=0){
        if($key==null){
            return floatval_array($this->post);
        }
        if($this->hasGet($key)){
            if(is_array($this->post[$key])&&Validate::required($this->post[$key])){
                return floatval_array($this->post[$key]);
            }else{
                return floatval($this->post[$key]);
            }
        }
        return $default;
    }
    
    public function postDouble($key=null,$default=0){
        if($key==null){
            return doubleval_array($this->post);
        }
        if($this->hasGet($key)){
            if(is_array($this->post[$key])&&Validate::required($this->post[$key])){
                return doubleval_array($this->post[$key]);
            }else{
                return doubleval($this->post[$key]);
            }
        }
        return $default;
    }
    
    public function hasGet($key){
        return isset($this->get[$key]);
    }

    public function hasPost($key){
        return isset($this->post[$key]);
    }

    public function method(){
        return $this->server['REQUEST_METHOD'];
    }
    
    public function module(){
        return $this->module;
    }
    
    public function control(){
        return $this->controller;
    }
    
    public function action(){
        return $this->action;
    }
    
    public function forward($module,$controller,$action){
        if($module!=null){
            $this->module=self::formatM($module);
        }
        if($controller!=null){
            $this->controller=self::formatC($controller);
        }
        if($action!=null){
            $this->action=self::formatA($action);
        }
        if(context()->isRun()){
            context()->run();
            exit();
        }
    }

    public function isGet(){
        return $this->method()=='GET'?true:false;
    }
    
    public function isPost(){
        return $this->method()=='POST'?true:false;
    }
    
    public function time(){
        return $this->server['REQUEST_TIME'];
    }
    
    public function port(){
        return $this->server['SERVER_PORT'];
    }
    
    public function url(){
        return $this->url;
    }
    
    public function absoluteUrl(){
        return $this->absoluteUrl;
    }


    public function pathinfo(){
        return $this->pathinfo();
    }
    
    public function ip(){
        return $this->ip;
    }
    
    public function header($key=null,$default=null){
        if($key==null){
            return $this->header;
        }
        return isset($this->header[$key])?$this->header[$key]:$default;
    }
    
    public function server($key=null,$default=null){
        if($key==null){
            return $this->server;
        }
        return isset($this->server[$key])?$this->server[$key]:$default;
    }
    
    public function file($key,FileFilter $filter=null){
        if($filter==null){
            $filter=new FileFilter();
        }
        return $filter->file($key);
    }
    
    public function files($key,FileFilter $filter=null){
        if($filter==null){
            $filter=new FileFilter();
        }
        return $filter->files($key);
    }
    
}
