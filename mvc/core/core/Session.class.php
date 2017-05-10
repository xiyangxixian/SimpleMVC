<?php

namespace core;

class Session {
    
    private static $instance=null; //Session类的实例

    private function __construct(){
        //设置超时时间
        define('SESSION_TIMEOUT',config('SESSION_TIMEOUT'));
    }
    
    /**
     * 单例的实现
     * @return Session
     */
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Session();
        }
        return self::$instance;
    }
    
    /**
     * 初始化session变量
     */
    public function init(){
        //全局this_session_timeout表示全局的session过期时间
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

    /**
     * 开启session
     */
    public function start(){
        if(!$this->isActive()){
            session_start();
            $this->init();
        }
    }
    
    /**
     * 关闭session
     */
    public function close(){
        session_register_shutdown();
    }
    
    /**
     * 销毁session
     */
    public function destroy(){
        session_destroy();
    }
    
    /**
     * 获取session值
     * @param string $key 字段值
     * @param mixed $default  默认值
     * @return mixed
     */
    public function get($key=null,$default=null){
        if($key==null){
            return $_SESSION;
        }
        return $this->has($key)&&Validate::required($_SESSION[$key])?$_SESSION[$key]:$default;
    }
    
    /**
     * 设置session值
     * @param string $key  字段值
     * @param string $value 值
     * @param int $timeout  超时时间
     * @return \core\Session
     */
    public function set($key,$value,$timeout=null){
        $_SESSION[$key]=$value;
        if($timeout!=null){
            $_SESSION['session_timeout'][$key]=time()+$timeout;
        }
        return $this;
    }
    
    /**
     * 移除session值
     * @param string $key 字段值
     * @return \core\Session
     */
    public function remove($key){
        unset($_SESSION[$key]);
        unset($_SESSION['session_timeout'][$key]);
        return $this;
    }
    
    /**
     * 判断字段是否存在
     * @param string $key 字段值
     * @return bool
     */
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
    
    /**
     * 判断session是否活跃
     * @return bool
     */
    public function isActive(){
        return isset($_SESSION);
    }
    
}
