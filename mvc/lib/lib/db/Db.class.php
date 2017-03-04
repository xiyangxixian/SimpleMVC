<?php

namespace lib\db;
use lib\db\driver\PDODriver;

class Db {
    
    protected $db=null;
    protected $currentTableName='';
    protected $options;
    
    protected static $tablePrefix='';
    protected static $instanceArray=[];

    private function __construct($tableName) {
        $this->currentTableName=$tableName;
        $this->init();
    }
    
    public static function table($table){
        $table=self::getCurrentTableName($table);
        if(isset(self::$instanceArray[$table])){
            $db=self::$instanceArray[$table];
            $db->init();
        }else{
           $db=new Db($table);
           self::$instanceArray[$table]=$db;
        }
        return $db;
    }
    
    public static function loadConfig(array $config){
        self::$tablePrefix=isset($config['DB_PREFIX'])?$config['DB_PREFIX']:'';
        PDODriver::instance()->loadConfig($config);
    }
    
    private static function whereIn($column,array $array){
        $sql=str_repeat(',?',count($array));
        $sql=substr($sql,1);
        $sql='`'.$column.'` in ('.$sql.')';
        return $sql;
    }

    public static function getCurrentTableName($tableName){
        if(strpos($tableName,'/')!=false){
            $tableName=substr($tableName,1);
            return $tableName;
        }
        return self::$tablePrefix.$tableName;
    }
    
    public function init(){
        $this->createDriver();
        $this->initOptons();
    }

    public function createDriver(){
        if($this->db==null){
            $this->db=PDODriver::instance();  
        }
    }
    
    public function initOptons(){
        $this->options=[];
        $this->options['param']=[];
        $this->options['where']=[];
        $this->options['join']=$this->currentTableName;
    }
    
    public function find($column='*'){
        $sql=$this->seleteSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->find($sql);
    }
    
    public function count($column='*'){
        $count='count('.$column.')';
        $sql=$this->seleteSql($count);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->find($sql)[$count];
    }
    
    public function select($column='*'){
        $sql=$this->seleteSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->select($sql);
    }

    public function add(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->insertSql('('.  $this->getKeys($currentData).') values ('.$this->getValues($currentData).')');
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->execute($sql);
    }
    
    public function addAll(array $data=null){
       $currentData=$this->getData($data);
       $dataArray=[];
       foreach ($currentData as $item){
            $str='('.$this->getValues($item).')';
            $dataArray[]=$str;
       }
       $sql=$this->insertSql('('.  $this->getKeys($currentData[0]).') values '.  implode(',',$dataArray));
       if(isset($this->options['sql'])){
           return $this->getSql($sql);
        }
       $this->initOptons();
       return $this->db->execute($sql);
    }
    
    public function update(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->updateSql($this->setKeyAndValue($currentData));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->execute($sql);
    }
    
    public function updateAll($case,array $data){
        $arr=[];
        $keys=array_keys($data[0]);
        $conditionArray=[];
        foreach ($keys as $value){
            if($value==$case){
                continue;
            }
            $conditionArray[$value]=[];
            foreach ($data as $item){
                $conditionArray[$value][]='when ? then ?';
                $arr[]=$item[$case];$arr[]=$item[$value];
            }
        }
        $out=[];
        foreach ($conditionArray as $key=>$value){
            $out[]='`'.$key.'`=case`'.$case.'` '.implode(' ', $value).' end';
        }
        $this->options['param']=array_merge($arr,$this->options['param']);
        $sql=$this->updateSql(implode(',',$out));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->execute($sql);
    }


    public function delete(){
        $sql=$this->deleteSql();
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptons();
        return $this->db->execute($sql);
    }
    
    private function seleteSql($column){
        $distinct=isset($this->options['distinct'])?'distinct ':'';
        $sql='select '.$distinct.$column.' from '.$this->options['join'].$this->getOption();
        $this->bind($this->options['param']);
        return $sql;
    }
    
    private function insertSql($data){
        $sql='insert into '.$this->currentTableName.' '.$data;
        $this->bind($this->options['param']);
        return $sql;
    }

    private function updateSql($data){
        $sql='update '.$this->currentTableName.' set '.$data.$this->getOption();
        $this->bind($this->options['param']);
        return $sql;
    }

    private function deleteSql(){
        $sql='delete from '.$this->currentTableName.$this->getOption();
        $this->bind($this->options['param']);
        return $sql;
    }

    public static function query($sql,$param=null){
        $db=PDODriver::instance();
        if($param!=null){
            $db->bind(is_array($param)?$param:[$param]);
        }
        return $db->select($sql);
    }
    
    public static function execute($sql,$param=null){
        $db=PDODriver::instance();
        if($param!=null){
            $db->bind(is_array($param)?$param:[$param]);
        }
        return $db->execute($sql);
    }


    private function getData($data){
        if($data!=null){
            $this->options['data']=$data;
        }
        return isset($this->options['data'])?$this->options['data']:array();
    }


    public function getKeys(array $data){
        return '`'.implode('`,`',array_keys($data)).'`';
    }
    
    public function getValues(array $data){
        $this->options['param']=array_merge($this->options['param'],array_values($data));
        return substr(str_repeat(',?',count($data)),1);
    }
    
    public function setKeyAndValue(array $data){
        $arr=[];
        $keyAndValueArr=[];
        foreach ($data as $key => $value) {
            $keyAndValueArr[] = '`'.$key.'`=?';
            $arr[]=$value;
        }
        $this->options['param']=array_merge($arr,$this->options['param']);
        return implode(',', $keyAndValueArr);
    }
    
    public function where($column,$mixed,$param=null){
        $args=func_get_args();
        if($param==null){
            $condition='=';
            $param=$mixed;
        }else{
            $condition=$mixed;
        }
        $sql='';
        $data=null;
        if($condition=='in'){
            $sql=self::whereIn($column,$param);
            $data=$param;
        }else{
            $sql='`'.$column.'`'.' '.$condition.' ?';
            $data=[$param];
        }
        $this->buildWhereOption($data,$sql);
        return $this;
    }
    
    public function xwhere($column,$mixed,$param=null){
        if(empty($param)&&empty($mixed)){
            return $this;
        }
        return $this->where($column,$mixed,$param);
    }


    public function condition($condition){
        if(empty($condition)){
            return $this;
        }
        $args=func_get_args();
        array_shift($args);
        $this->buildWhereOption($args,$condition);
        return $this;
    }
    
    private function buildWhereOption($options,$condition){
        if(isset($this->options['having'])){
            $this->options['param']=array_merge($options,$this->options['param']);
        }else{
            $this->options['param']=array_merge($this->options['param'],$options);
        }
        $this->options['where'][]=$condition;
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
        $offest=intval($offest);
        if($length==null){
            $this->options['offest']=0;
            $this->options['length']=$offest;
        }else{
            $length=intval($length);
            $this->options['offest']=$offest;
            $this->options['length']=$length;
        }
        return $this;
    }
    
    public function page($page,$rowNum=null){
        $page=intval($page);
        if($rowNum==null&&isset($this->options['length'])){
            $this->options['offest']=($page-1)*$this->options['length'];
        }else{
            $rowNum=intval($rowNum);
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
        if(empty($having)){
            return $this;
        }
        $args=func_get_args();
        array_shift($args);
        $this->options['param']=array_merge($this->options['param'],$args);
        $this->options['having']='having '.$having;
        return $this;
    }
    
    public function join($table,$condition,$type='inner'){
        $currentTable=self::getCurrentTableName($table);
        $join=$this->options['join'];
        if($join==$this->currentTableName){
           $join.=' '.$type.' join '.$currentTable.' on '.$condition;
        }else{
           $join=preg_replace('#^.+$#','($0)', $this->options['join']);
           $join.=' '.$type.' join '.$currentTable.' on '.$condition;
        }
        $this->options['join']=$join;
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
    
    public function getSql($sql){
        foreach ($this->options['param'] as $value){
            if(is_string($value)){
               $sql=preg_replace('#\?#','\''.$value.'\'',$sql,1);
            }else{
               $sql=preg_replace('#\?#',$value,$sql,1); 
            }
        }
        return $sql;
    }


    private function bind($param){
        if(is_array($param)){
            $this->db->bind($param);
        }else{
            $this->db->bind([$param]);
        }
        return $this;
    }

    public function getOption(){
        $option='';
        if(isset($this->options['where'])&&!empty($this->options['where'])){
            $option.=' where ('.implode(') and (', $this->options['where']).')';
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
