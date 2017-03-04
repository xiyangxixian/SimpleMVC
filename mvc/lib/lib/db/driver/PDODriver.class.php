<?php

namespace lib\db\driver;
use lib\db\Driver;
use PDO;

class PDODriver extends Driver{
    
    private $param=null;
    
    public function connect(){
        if(empty($this->config)){
            $this->err('数据库配置错误');
        }
        $type=isset($this->config['DB_TYPE'])?$this->config['DB_TYPE']:'mysql';
        $dsn=$type.':dbname='.$this->config['DB_NAME'].';host='.$this->config['DB_HOST'];
        $this->conn=new PDO($dsn, $this->config['DB_USER'], $this->config['DB_PWD'],array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this;
    }

    public function exec($sql) {
        $this->initConnect();
        return $this->conn->exec($sql);
    }
    
    public function bind($param){
        $this->param=$param;
        return $this;
    }
    
    public function query($sql) {
        $this->initConnect();
        return $this->conn->query($sql);
    }
    
    public function execute($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $result=$stmt->execute($this->param);
        $this->param=null;
        return $result;
    }
    
    public function find($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $stmt->execute($this->param);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        $this->param=null;
        return $row;
    }
    
    public function select($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $stmt->execute($this->param);
        $arr=array();
        while ($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $arr[]=$row;
        }
        $this->param=null;
        return $arr;
    }

    public function close(){
        if($this->conn!=null){
            $this->conn=null;
        }
    }
    
}
