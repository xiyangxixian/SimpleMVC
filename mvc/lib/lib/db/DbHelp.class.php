<?php

namespace lib\db;

class DbHelp {

    private $conn;
    private $config;

    public function __construct(array $config=array(),$isConnect=true) {
        $this->config = $config;
        if($isConnect&&!empty($config)){
            $this->connect();
        }
    }
    
    public function loadConfig($config){
        $this->config = $config;
    }

    private function err($error) {
        die('对不起，您的操作有误，错误原因为：' . $error);
    }

    public function connect() {
        if(empty($this->config)){
            $this->err('数据库配置错误');
        }
        if (!$this->conn = new mysqli($this->config['DB_HOST'], $this->config['DB_USER'], $this->config['DB_PWD'], $this->config['DB_NAME'])) {
            $this->error($this->conn->connect_error);
        }
        $this->conn->set_charset('utf8');
    }

    public function queryOne($table, $columns = '', $where ='', $fun = '') {
        $sql = $this->formatQuerySql($table, $columns, 0, 0, $where, $fun);
        $result = $this->query($sql);
        $row = $result->fetch_assoc();
        return $row;
    }

    public function queryOneBySql($sql) {
        $result = $this->query($sql);
        $row = $result->fetch_assoc();
        return $row;
    }

    public function queryAll($table, $columns = '', $start = 0, $length = 1, $where = '', $fun = '') {
        $arr = array();
        $sql = $this->formatQuerySql($table, $columns, $start, $length, $where, $fun);
        $result = $this->query($sql);
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }
        $result->close();
        return $arr;
    }

    public function count($table) {
        $sql='select count(*) from '.$table;
        $result = $this->query($sql);
        $row = $result->fetch_assoc();
        return $row['count(*)'];
    }

    public function queryAllBySql($sql) {
        $result = $this->query($sql);
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }
        $result->close();
        return $arr;
    }

    public function insert($table, $data) {
        foreach ($data as $key => $value) {
            $keyArr[] = '`' . $key . '`';
            $valArr[] = '\'' . $value . '\'';
        }
        $keys = implode(',', $keyArr);
        $values = implode(',', $valArr);
        $sql = 'insert into ' . $table . ' (' . $keys . ') values (' . $values . ')';
        return $this->query($sql);
    }
    
    public function batchInsert($table,$data){
        $keyList= array_keys($data[0]);
        foreach ($keyList as $key) {
            $keyArr[] = '`' . $key . '`';
        }
         $keys = implode(',', $keyArr);
        $valArr=array();
        foreach ($data as $item){
            $str='(\''.  implode('\',\'',array_values($item)).'\')';
            $valArr[]=$str;
        }
        $values=implode(',', $valArr);
        $sql = 'insert into ' . $table . ' (' . $keys . ') values ' . $values ;
        return $this->query($sql);
    }

    public function update($table, $data, $where) {
        foreach ($data as $key => $value) {
            $keyAndValueArr[] = '`' . $key . '`=\'' . $value . '\'';
        }
        $keyAndValues = implode(',', $keyAndValueArr);
        $sql = 'update ' . $table . ' set ' . $keyAndValues . ' where ' . $where;
        return $this->query($sql);
    }
    
    public function batchUpdate($table,$data,$where,$column=null,array $whereIN=array()){
         foreach ($data as $key => $value) {
            $keyAndValueArr[] = '`' . $key . '`=\'' . $value . '\'';
        }
        $keyAndValues = implode(',', $keyAndValueArr);
        $sql = 'update ' . $table . ' set ' . $keyAndValues . ' where '.$where;
        if($column!=null){
            $sql.=' and ' . $column.' in (\''.  implode('\',\'', $whereIN).'\')';
        }
        return $this->query($sql);
    }

    public function delete($table, $where) {
        $sql = 'delete from ' . $table . ' where ' . $where;
        return $this->query($sql);
    }

    public function deleteAll($table) {
        $sql = 'delete from ' . $table;
        return $this->query($sql);
    }

    public function close() {
        $this->conn->close();
    }

    private function formatQuerySql($table, $columns, $start, $length, $where, $fun) {
        $sql = 'select * from ' . $table;
        if ($columns != '') {
            $sql = 'select ' . $columns . ' from ' . $table;
        }
        if ($where != '') {
            $sql = $sql . ' where ' . $where;
        }
        if ($fun != '') {
            $sql = $sql . ' ' . $fun;
        }
        if ($start > 0) {
            $start = $start - 1;
            $sql = $sql . ' limit ' . $start . ',' . $length;
        }
        return $sql;
    }

    public function query($sql) {
        $query = $this->conn->query($sql);
        if ($query) {
            return $query;
        } else {
            $this->err($sql);
        }
    }

}
