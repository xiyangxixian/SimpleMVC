<?php

namespace lib\db;
use lib\db\driver\Mysql;

class MysqlDb {
    
    protected $db=null;
    protected $tablePrefix='';
    protected $currentTableName='';
    protected $options;
    protected static $instanceArray=[];

    private function __construct($tableName) {
        $this->createDriver();
        $this->initOptons();
        $config=$this->db->getConfig();
        $this->tablePrefix=isset($config['DB_PREFIX'])?$config['DB_PREFIX']:'';
        if(strpos($tableName,',')){
            $tables=explode(',',$tableName);
            $tableArray=array();
            foreach($tables as $table){
                $tableArray[]=$this->getCurrentTableName($table);
            }
            $this->currentTableName=implode(',',$tableArray);
        }else{
            $this->currentTableName=$this->getCurrentTableName($tableName);
        }
    }
    
    public static function table($table){
        if(isset(self::$instanceArray[$table])){
            $db=self::$instanceArray[$table];
            $db->createDriver();
            $db->initOptons();
        }else{
           $db=new MysqlDb($table);
           self::$instanceArray[$table]=$db;
        }
        return $db;
    }
    
    public static function loadConfig(array $config){
        Mysql::instance()->loadConfig($config);
    }
    
    public function getCurrentTableName($tableName){
        if(strpos($tableName,'/')!=false){
            $tableName=substr($tableName,1,strlen($tableName)-1);
            return $tableName;
        }
        return $this->tablePrefix.$tableName;
    }

    public function createDriver(){
        $this->db=Mysql::instance();
    }
    
    public function initOptons(){
        $this->options=[];
    }

    public function add(array $data=null){
        $currentData=$this->getData($data);
        return $this->insertSql('('.  self::getKeys($currentData).') values ('.self::getValues($currentData).')');
    }
    
    public function addAll(array $data=null){
       $currentData=$this->getData($data);
       $dataArray=[];
       foreach ($currentData as $item){
            $str='('.self::getValues($item).')';
            $dataArray[]=$str;
       }
       return $this->insertSql('('.  self::getKeys($currentData).') values '.  implode(',',$dataArray));
    }


    public function find($column='*'){
        $result=$this->seleteSql($column);
        if(isset($this->options['sql'])){
            return $result;
        }
        $row=$result->fetch_assoc();
        $result->close();
        if($row){
            return $row;
        }
        return null;
    }
    
    public function count($column='*'){
        $count='count('.$column.')';
        $result=$this->seleteSql($count);
        if(isset($this->options['sql'])){
            return $result;
        }
        $row=$result->fetch_assoc();
        $result->close();
        return $row[$count];
    }
    
    public function select($column='*'){
        $result=$this->seleteSql($column);
        if(isset($this->options['sql'])){
            return $result;
        }
        $data=[];
        while ($row=$result->fetch_assoc()){
            $data[]=$row;
        }
        $result->close();
        return $data;
    }
    public function update(array $data=null){
        $currentData=$this->getData($data);
        return $this->updateSql(self::setKeyAndValue($currentData));
    }
    
    public function updateAll($case,array $data){
        $keys=array_keys($data[0]);
        $conditionArray=[];
        foreach ($keys as $value){
            if($value==$case){
                continue;
            }
            $conditionArray[$value]=[];
            foreach ($data as $item){
                $conditionArray[$value][]='when \''.$item[$case].'\' then \''.$item[$value].'\'';
            }
        }
        $out=[];
        foreach ($conditionArray as $key=>$value){
            $out[]='`'.$key.'`=case`'.$case.'` '.implode(' ', $value).' end';
        }
        return $this->updateSql(implode(',',$out));
    }


    public function delete(){
        return $this->deleteSql();
    }
    
    private function seleteSql($column){
        $distinct=isset($this->options['distinct'])?'distinct ':'';
        $sql='select '.$distinct.$column.' from '.$this->currentTableName.$this->getOption();
        return $this->query($sql);
    }
    
    private function insertSql($data){
        $sql='insert into '.$this->currentTableName.' '.$data;
        return $this->execute($sql);
    }

    private function updateSql($data){
        $sql='update '.$this->currentTableName.' set '.$data.$this->getOption();
        return $this->execute($sql);
    }

    private function deleteSql(){
        $sql='delete from '.$this->currentTableName.$this->getOption();
        return $this->execute($sql);
    }

    public function query($sql){
        if(isset($this->options['sql'])){
            return $sql;
        }
        return $this->db->query($sql);
    }

    public function execute($sql){
        if(isset($this->options['sql'])){
            return $sql;
        }
        return $this->db->execute($sql);
    }
    
    public static function queryBySql($sql){
        return Mysql::instance()->query($sql);
    }
    
    public static function excuteBySql($sql){
        return Mysql::instance()->excute($sql);
    }
    
    private function getData($data){
        if($data!=null){
            $this->options['data']=$data;
        }
        return isset($this->options['data'])?$this->options['data']:array();
    }


    public static function getKeys(array $data){
        return '`'.implode('`,`',array_keys($data)).'`';
    }
    
    public static function getValues(array $data){
        return '\''.implode('`,`', array_values($data)).'\'';
    }
    
    public static function setKeyAndValue(array $data){
        $keyAndValueArr=array();
        foreach ($data as $key => $value) {
            $keyAndValueArr[] = '`'.$key.'`=\''.$value .'\'';
        }
        return implode(',', $keyAndValueArr);
    }
    
    public function where($where){
        if(empty($where)){
            return $this;
        }
        $this->options['where']='where '.$where;
        return $this;
    }
    
    public function data(array $data){
        $this->options['data']=$data;
        return $this;
    }
    
    public function order($order){
        $this->options['order']='order by '.$order;
        return $this;
    }
    
    public function limit($offest,$length=null){
        if($length==null){
            $this->options['offest']=0;
            $this->options['length']=$offest;
        }else{
            $this->options['offest']=$offest;
            $this->options['length']=$length;
        }
        return $this;
    }
    
    public function page($page,$rowNum=null){
        if($rowNum==null&&isset($this->options['length'])){
            $this->options['offest']=($page-1)*$this->options['length'];
        }else{
            $this->options['offest']=($page-1)*$rowNum;
            $this->options['length']=$rowNum;
        }
        return $this;
    }
    
    public function group($group){
        $this->options['group']='group by '.$group;
        return $this;
    }
    
    public function having($having){
        $this->having['having']='having '.$having;
        return $this;
    }
    
    public function join($table,$condition,$type='inner'){
        $this->options['join']=$type.' join '.$table.' on '.$condition;
        return $this;
    }
    
    public function distinct(){
        $this->options[distinct]=true;
        return $this;
    }
    
    public function sql(){
        $this->options['sql']=true;
        return $this;
    }

    public function getOption(){
        $option='';
        if(isset($this->options['join'])){
            $option.=' '.$this->options['join'];
        }
        if(isset($this->options['where'])){
            $option.=' '.$this->options['where'];
        }
        if(isset($this->options['group'])){
            $option.=' '.$this->options['group'];
        }
        if(isset($this->options['having'])){
            $option.=' '.$this->options['having'];
        }
        if(isset($this->options['order'])){
            $option.=' '.$this->options['order'];
        }
        if(isset($this->options['offest'])){
            $option.=' limit '.$this->options['offest'].','.$this->options['length'];
        }
        return $option;
    }

    public static function union($column,Db $db1,Db $db2,$all=false){
        $union=$all?'union all':'union';
        $sql=$db1->sql()->select($column).' '.$union.' '.$db2->sql()->select($column);
        return $this->query($sql);
    }
    
}
