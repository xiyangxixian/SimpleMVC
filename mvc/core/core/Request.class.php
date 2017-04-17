<?php

namespace core;
use core\FileFilter;
use core\Validate;

class Request {
    
    private static $instance=null;  //Request实例
    private $get;  //$_GET参数，已过滤
    private $post;  //$_POST参数，已过滤
    private $server;  //$_SERVER参数
    private $url; //请求的url
    private $absoluteUrl; //请求的url的就饿路径
    private $module; //请求的模块名称
    private $controller; //请求的控制器名称
    private $action; //请求的操作名称
    private $pathinfo; //pathinfo信息
    private $ip;  //请求的IP地址
    private $header;  //请求的头信息
    
    private function __construct() {
        $this->get=escape_input($_GET);
        $this->post=escape_input($_POST);
        $this->server=$_SERVER;
        //pathinfo信息
        $this->pathinfo=htmlspecialchars(isset($this->server['PATH_INFO'])?$this->server['PATH_INFO']:'',ENT_QUOTES);
        //IP地址
        $this->ip=$this->getClientIP();
        //url地址
        $this->url=htmlspecialchars($this->server['REQUEST_URI'],ENT_QUOTES);
        //端口号
        $port=$this->port()==80?'':':'.$this->port();
        //绝对路径
        $this->absoluteUrl=$this->server('REQUEST_SCHEME').'://'.$this->server['SERVER_NAME'].$port.$this->url(); 
        //初始化路由配置
        $this->init();
        //请求头信息
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
    
    /**
     * 单例的实现
     * @return Request
     */
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Request();
        }
        return self::$instance;
    }
    
    /**
     * 初始化路由配置
     */
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
    
    /**
     * 获取IP地址
     * @return string
     */
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
    
    /**
     * 格式化模块名字
     * @param string $char
     * @return string
     */
    public static function formatM($char){
        if(empty($char)){
            $out=explode(',',MODULE);
            return $out[0];
        }
        return $char;
    }

    /**
     * 格式化控制器名字
     * @param string $char
     * @return string
     */
    public static function formatC($char){
        if(empty($char)){
            return config('ROUTER','DEFAULT_CONTROLLER');
        }
        return small_to_hump($char);
    }
    
    /**
     * 格式化操作名字
     * @param string $char
     * @return string
     */
    public static function formatA($char){
        if(empty($char)){
            return config('ROUTER','DEFAULT_ACTION');
        }
        return small_to_hump($char,false);
    }

    /**
     * 获取get参数值
     * @param string $key 为空则表示获取全部的get参数
     * @param string $default 返回的默认值
     * @return mixed
     */
    public function get($key=null,$default=null){
        if($key==null){
            return $this->get;
        }
        return $this->hasGet($key)&&Validate::required($this->get[$key])?$this->get[$key]:$default;
    }
    
    /**
     * 获取get参数值，转换为int形式
     * @param string $key 为空则表示获取全部的get参数
     * @param int $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 获取get参数值，转换为float形式
     * @param string $key 为空则表示获取全部的get参数
     * @param float $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 获取get参数值，转换为double形式
     * @param string $key 为空则表示获取全部的get参数
     * @param double $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 获取post参数值，转换为int形式
     * @param string $key 为空则表示获取全部的get参数
     * @param int $default 返回的默认值
     * @return mixed
     */
    public function post($key=null,$default=null){
        if($key==null){
            return $this->post;
        }
        return $this->hasPost($key)&&Validate::required($this->post[$key])?$this->post[$key]:$default;
    }
    
    /**
     * 获取post参数值，转换为int形式
     * @param string $key 为空则表示获取全部的get参数
     * @param int $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 获取post参数值，转换为float形式
     * @param string $key 为空则表示获取全部的get参数
     * @param float $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 获取double参数值，转换为double形式
     * @param string $key 为空则表示获取全部的get参数
     * @param double $default 返回的默认值
     * @return mixed
     */
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
    
    /**
     * 判断get参数是否存在
     * @param string $key
     * @return bool
     */
    public function hasGet($key){
        return isset($this->get[$key]);
    }

    /**
     * 判断post参数是否存在
     * @param string $key
     * @return bool
     */
    public function hasPost($key){
        return isset($this->post[$key]);
    }

    /**
     * 获取请求的方法名字
     * @return string
     */
    public function method(){
        return $this->server['REQUEST_METHOD'];
    }
    
    /**
     * 获取请求的模块名字
     * @return string
     */
    public function module(){
        return $this->module;
    }
    
    /**
     * 获取请求的控制器名字
     * @return string
     */
    public function control(){
        return $this->controller;
    }
    
    /**
     * 获取请求的操作方法名字
     * @return string
     */
    public function action(){
        return $this->action;
    }
    
    /**
     * 请求转发
     * @param string $module  转发的模块名字，为空表示不改变模块名字
     * @param string $controller  转发的控制器名字，为空表示不改变控制器名字
     * @param string $action  转发的操作名字，为空表示不改变操作名字
     */
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

    /**
     * 判断是否为get请求
     * @return bool
     */
    public function isGet(){
        return $this->method()=='GET'?true:false;
    }
    
    /**
     * 判断是否为get请求
     * @return bool
     */
    public function isPost(){
        return $this->method()=='POST'?true:false;
    }
    
    /**
     * 获取请求的时间
     * @return int
     */
    public function time(){
        return $this->server['REQUEST_TIME'];
    }
    
    /**
     * 获取请求的端口号
     * @return int
     */
    public function port(){
        return $this->server['SERVER_PORT'];
    }
    
    /**
     * 获取请求的url
     * @return string
     */
    public function url(){
        return $this->url;
    }
    
    /**
     * 获取请求的url的绝对路径
     * @return string
     */
    public function absoluteUrl(){
        return $this->absoluteUrl;
    }

    /**
     * 获取请求的pathinfo信息
     * @return string
     */
    public function pathinfo(){
        return $this->pathinfo();
    }
    
    /**
     * 获取请求的IP地址信息
     * @return string
     */
    public function ip(){
        return $this->ip;
    }
    
    /**
     * 获取请求的头信息
     * @param string $key  请求头字段，为空则获取全部的头信息
     * @param string $default  返回的默认值
     * @return mixed
     */
    public function header($key=null,$default=null){
        if($key==null){
            return $this->header;
        }
        return isset($this->header[$key])?$this->header[$key]:$default;
    }
    
    /**
     * 返回$_SERVER变量信息
     * @param string $key  字段值  
     * @param string $default  返回的默认值
     * @return mixed
     */
    public function server($key=null,$default=null){
        if($key==null){
            return $this->server;
        }
        return isset($this->server[$key])?$this->server[$key]:$default;
    }
    
    /**
     * 获取上传的文件
     * @param string $key 上传文件的字段值
     * @param FileFilter $filter 文件过滤类
     * @return UploadFile
     */
    public function file($key,FileFilter $filter=null){
        if($filter==null){
            $filter=new FileFilter();
        }
        return $filter->file($key);
    }
    
    /**
     * 获取多个上传的文件
     * @param string $key 上传文件的字段值
     * @param FileFilter $filter 文件过滤类
     * @return UploadFile
     */
    public function files($key,FileFilter $filter=null){
        if($filter==null){
            $filter=new FileFilter();
        }
        return $filter->files($key);
    }
    
}
