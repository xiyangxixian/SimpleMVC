<?php

namespace lib\db\driver;
use lib\db\Driver;
use mysqli;

class Mysql extends Driver{
            
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

    public function execute($sql) {
        return $this->query($sql);
    }
    
    public function query($sql) {
        $query = $this->conn->query($sql);
        if (!$query) {
            $this->err($sql);
        }
        return $query;
    }

}
