<?php

namespace lib\db;
use Exception;

abstract class Driver {
    
    protected $conn=null;
    protected $config=null;
    protected static $insrance=null;
    protected $rowCount=0;


    private function __construct() {}
    
    /**
     * 单例实现
     * @return Driver
     */
    public static function instance(){
        if(self::$insrance==null){
            self::$insrance=new static();
        }
        return self::$insrance;
    }
    
    protected function initConnect(){
        if($this->conn==null){
            $this->connect();
        }
    }
    
    public function loadConfig($config){
        if($this->config!=$config){
            $this->close();
            $this->config = $config;
        }
        return $this;
    }
    
    public function getConfig(){
        return $this->config;
    }


    protected function err($error) {
        throw new Exception('error：' . $error);
    }
    
    public function rowCount(){
        return $this->rowCount;
    }
    
    abstract function connect();
    abstract function query($sql);
    abstract function execute($sql);
    
    public function close(){
        if($this->conn!=null){
            $this->conn->close();
            $this->conn=null;
        }
    }
    
    public function __destruct() {
        $this->close();
    }
    
}
