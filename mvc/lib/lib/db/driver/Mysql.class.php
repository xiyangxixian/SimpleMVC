<?php

namespace lib\db\driver;
use lib\db\Driver;
use mysqli;

class Mysql extends Driver{
    
    private $param=null;
            
    public function connect(){
        if(empty($this->config)){
            $this->err('数据库配置错误');
        }
        if (!$this->conn=new mysqli($this->config['DB_HOST'], $this->config['DB_USER'], $this->config['DB_PWD'],$this->config['DB_NAME'])) {
            $this->error($this->conn->connect_error);
        }
        $this->conn->set_charset('utf8');
        return $this;
    }
    
    public function exec($sql) {
        return $this->query($sql);
    }
    
    public function bind($param){
        $this->param=$param;
        return $this;
    }
    
    public function query($sql) {
       $this->initConnect();
        $query = $this->conn->query($sql);
        if (!$query) {
            $this->err($sql);
        }
        $this->param=null;
        return $query;
    }
    
    public function execute($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $method=new \ReflectionMethod($stmt,'bind_param');
        $method->invokeArgs($stmt, $this->refValues($this->param,$sql));
        $result=$stmt->execute();
        $this->param=null;
        return $result;
    }
    
    public function find($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $method=new \ReflectionMethod($stmt, 'bind_param');
        $method->invokeArgs($stmt, $this->refValues($this->param,$sql));
        $stmt->execute();
        $result=$stmt->get_result();
        $row=$result->fetch_assoc();
        $this->param=null;
        return $row;
    }
    
    public function select($sql){
        $this->initConnect();
        $stmt=$this->conn->prepare($sql);
        $method=new \ReflectionMethod($stmt, 'bind_param');
        $method->invokeArgs($stmt,$this->refValues($this->param,$sql));
        $stmt->execute();
        $result=$stmt->get_result();
        $arr=array();
        while ($row=$result->fetch_assoc()){
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
    
    private function refValues($arr,&$sql){
        $nPos = 0;
        $type='';
        $refs = array();  
        foreach($arr as $key=>$value){
            if (($nPos = strpos($sql, '?', $nPos + 1)) === false){ 
                continue;
            }
            if($value===null){
                $sql=substr_replace($sql,'NULL',$nPos, 1);
                continue;
            }
            $refs[$key]=&$arr[$key];
            $type.=substr(gettype($value),0,1);
        }
        array_unshift($refs,$type);
        return $refs;
    }
    
    
}
