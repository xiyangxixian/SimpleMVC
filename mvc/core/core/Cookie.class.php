<?php


namespace core;

class Cookie {
    
    private static $instance=null;  //Cookie的实例
    private $cookie;  //cookie数据
    
    private function __construct() {
        $this->cookie=escape_input($_COOKIE);  //对cookie数据进行过滤
    }
    
    /**
     * 单例的实现
     * @return Cookie
     */
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Cookie();
        }
        return self::$instance;
    }
    
    /**
     * 获取cookie值
     * @param string $key  cookie键值
     * @param string $default  为空时返回的默认值
     * @return mixed
     */
    public function get($key=null,$default=null){
        if($key==null){
            return $this->cookie;
        }
        return $this->has($key)&&Validate::required($this->cookie[$key])?$this->cookie[$key]:$default;
    }
    
    /**
     * 获取cookie参数，并转化为int类型
     * @param string $key
     * @param int $default
     * @return mixed
     */
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
    
    /**
     * 获取cookie参数，并转化为float类型
     * @param string $key
     * @param float $default
     * @return mixed
     */
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
    
    /**
     * 获取cookie参数，并转化为double类型
     * @param string $key
     * @param double $default
     * @return mixed
     */
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
    
    /**
     * 设置cookie值
     * @param string $key  cookie键值
     * @param string $value  cookie值
     * @param int $time
     * @return \core\Cookie
     */
    public function set($key,$value,$time=null){
        $time==null?setcookie($key, $value):setcookie($key,$value,$time);
        return $this;
    }
    
    /**
     * 判断cookie键值是否存在
     * @param string $key
     * @return bool
     */
    public function has($key){
        return isset($this->cookie[$key]);
    }
    
}
