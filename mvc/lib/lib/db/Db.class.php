<?php

namespace lib\db;
use lib\db\driver\PDODriver;

class Db {
    
    protected $db=null;
    protected $currentTableName='';
    protected $options;
    protected $saveOption=array();
    
    protected static $tablePrefix='';
    protected static $instanceArray=array();

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
    
    private function init(){
        $this->createDriver();
        $this->initOptions();
    }

    public function createDriver(){
        if($this->db==null){
            $this->db=PDODriver::instance();  
        }
    }
    
    private function initOptions(){
        $this->options['where']=array();
        $this->options['whereParam']=array();
        $this->options['dataParam']=array();
        $this->options['or']=array();
        $this->options['orParam']=array();
        $this->options['havingParam']=array();
        $this->options['join']=array();
        $this->options['join'][]=$this->currentTableName;
        $this->options=array_merge($this->options,$this->saveOption);
    }
    
    public function saveOptions(){
        $this->saveOption=$this->options;
    }
    
    public function clearOptions(){
        $this->saveOption=array();
        $this->initOptions();
    }
    
    public function find($column=null){
        $sql=$this->selectSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->find($sql);
    }
    
    public function count($column=null){
        $sql=$this->selectSql($column.'|count[`count`]');
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        $result=$this->db->find($sql);
        return $result['count'];
    }
    
    public function select($column=null){
        $sql=$this->selectSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->select($sql);
    }

    public function add(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->insertSql('('.  $this->getKeys($currentData).') values ('.$this->getValues($currentData).')');
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->execute($sql);
    }
    
    public function addAll(array $data=null){
       $currentData=$this->getData($data);
       $dataArray=array();
       foreach ($currentData as $item){
            $str='('.$this->getValues($item).')';
            $dataArray[]=$str;
       }
       $sql=$this->insertSql('('.  $this->getKeys($currentData[0]).') values '.  implode(',',$dataArray));
       if(isset($this->options['sql'])){
           return $this->getSql($sql);
        }
       $this->initOptions();
       return $this->db->execute($sql);
    }
    
    public function update(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->updateSql($this->setKeyAndValue($currentData));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->execute($sql);
    }
    
    public function updateAll($case,array $data){
        $arr=array();
        $keys=array_keys($data[0]);
        $conditionArray=array();
        foreach ($keys as $value){
            if($value==$case){
                continue;
            }
            $conditionArray[$value]=array();
            foreach ($data as $item){
                $conditionArray[$value][]='when ? then ?';
                $arr[]=$item[$case];$arr[]=$item[$value];
            }
        }
        $out=array();
        foreach ($conditionArray as $key=>$value){
            $out[]='`'.$key.'`=case`'.$case.'` '.implode(' ', $value).' end';
        }
        $this->options['dataParam']=array_merge($this->options['dataParam'],$arr);
        $sql=$this->updateSql(implode(',',$out));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->execute($sql);
    }


    public function delete(){
        $sql=$this->deleteSql();
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return $this->db->execute($sql);
    }
    
    private function selectSql($column){
        $distinct=isset($this->options['distinct'])?'distinct ':'';
        $sql='select '.$distinct.$this->buildFeilds($column).' from '.$this->buildJoin().$this->getOption();
        $this->bind();
        return $sql;
    }
    
    private function buildFeilds($column){
        if(empty($column)||$column=='*'){
            return '*';
        }
        $columns=is_array($column)?$column:explode(',', $column);
        $arr=array();
        foreach ($columns as $value){
            $arr[]=$this->formatFeild($value);
        }
        return implode(',', $arr);
    }
    
    private function formatFeild($field){
        $result=preg_match('/\[(.+?)\]/',$field,$m);
        $alias='';
        if($result&&isset($m[1])){
            $alias=$this->addSlashes($m[1]);
            $field=preg_replace('/\[(.+?)\]/','',$field);
        }
        if(strpos($field,'|')===false){
            return $this->addSlashes($field).' '.$alias;
        }
        $fieldArr=explode('|', $field);
        $column=$this->addSlashes(array_shift($fieldArr));
        foreach ($fieldArr as $fun){
            $params=explode(':',$fun);
            $funName=array_shift($params);
            array_unshift($params,$column);
            $column=$funName.'('.implode(',',$params).')';
        }
        return $column.' '.$alias;
    }
    
    private function addSlashes($field){
        if($field=='*'||empty($field)){
            return '*';
        }
        if(strpos($field,'`')!==false||strpos($field,'(')!==false){
            return $field;
        }
        return '`'.$field.'`';
    }
    
    private function insertSql($data){
        $sql='insert into '.$this->currentTableName.' '.$data;
        $this->bind();
        return $sql;
    }

    private function updateSql($data){
        $sql='update '.$this->currentTableName.' set '.$data.$this->getOption();
        $this->bind();
        return $sql;
    }

    private function deleteSql(){
        $sql='delete from '.$this->currentTableName.$this->getOption();
        $this->bind();
        return $sql;
    }

    public static function query($sql,$param=null){
        $db=PDODriver::instance();
        if($param!=null){
            $db->bind(is_array($param)?$param:array($param));
        }
        return $db->select($sql);
    }
    
    public static function execute($sql,$param=null){
        $db=PDODriver::instance();
        if($param!=null){
            $db->bind(is_array($param)?$param:array($param));
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
        $this->options['dataParam']=array_merge($this->options['dataParam'],array_values($data));
        return substr(str_repeat(',?',count($data)),1);
    }
    
    public function setKeyAndValue(array $data){
        $arr=array();
        $keyAndValueArr=array();
        foreach ($data as $key => $value) {
            $keyAndValueArr[] = '`'.$key.'`=?';
            $arr[]=$value;
        }
        $this->options['dataParam']=array_merge($this->options['dataParam'],$arr);
        return implode(',', $keyAndValueArr);
    }
    
    public function where($column,$mixed,$param=null){
        if($param===null){
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
            $data=array($param);
        }
        $this->buildWhereOption($data,$sql);
        return $this;
    }
    
    public function xwhere($column,$mixed,$param=null){
        if(empty($param)||($param!==null&&empty($param))){
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
    
    public function whereOr($column,$mixed,$param=null,$group=null){
        if($param===null){
            $condition='=';
            $param=$mixed;
        }else{
            $condition=$mixed;
        }
        $group=$group===null?'db':$group;
        $sql='';
        $data=null;
        if($condition=='in'){
            $sql=self::whereIn($column,$param);
            $data=$param;
        }else{
            $sql='`'.$column.'`'.' '.$condition.' ?';
            $data=array($param);
        }
        $this->buildOrOption($data,$sql,$group);
        return $this;
    }
    
    private function buildWhereOption($options,$condition){
        $this->options['whereParam']=array_merge($this->options['whereParam'],$options);
        $this->options['where'][]=$condition;
    }
    
    private function buildOrOption($options,$condition,$group){
        if(!isset($this->options['or'][$group])){
            $this->options['or'][$group]=array();
            $this->options['orParam'][$group]=array();
        }
        $this->options['orParam'][$group]=array_merge($this->options['orParam'][$group],$options);
        $this->options['or'][$group][]=$condition;
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
        $this->options['havingParam']=array_merge($this->options['havingParam'],$args);
        $this->options['having']='having '.$having;
        return $this;
    }
    
    public function join($table,$condition,$type='inner'){
        $currentTable=self::getCurrentTableName($table);
        $this->options['join'][]=$type.' join '.$currentTable.' on '.$condition;
        return $this;
    }
    
    private function buildJoin(){
        $join=$this->options['join'];
        $i=0;
        $out='';
        foreach ($join as $item){
            if($i===0){
                $out.=$item;
            }else if($i>0){
                $out=' ('.$out.' '.$item.')';
            }
            $i++;
        }
        return $out;
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
        foreach (array_merge($this->options['dataParam'],$this->options['whereParam'],$this->options['havingParam']) as $value){
            if(is_string($value)){
               $sql=preg_replace('/\?/','\''.$value.'\'',$sql,1);
            }else{
               $sql=preg_replace('/\?/',$value,$sql,1); 
            }
        }
        return $sql;
    }


    private function bind($param=null){
        if($param===null){
            $optionArr=array_merge($this->options['dataParam'],$this->options['whereParam'],$this->options['havingParam']);
            $this->db->bind($optionArr);
            return $this;
        }
        if(is_array($param)){
            $this->db->bind($param);
        }else{
            $this->db->bind(array($param));
        }
        return $this;
    }

    public function getOption(){
        $option='';
        $optionWhere=array();
        if(isset($this->options['where'])&&!empty($this->options['where'])){
            $optionWhere[]='('.implode(') and (', $this->options['where']).')';
        }
        if(isset($this->options['or'])&&!empty($this->options['or'])){
            $tempArr=array();
            foreach ($this->options['or'] as $key=>$value){
                $tempArr[]='('.implode(') or (', $value).')';
                $params=$this->options['orParam'][$key];
                $this->options['whereParam']=array_merge($this->options['whereParam'],$params);
            }
            $optionWhere[]='('.implode(') and (', $tempArr).')';
        }
        if(!empty($optionWhere)){
            $option.=' where '.implode(' and ',$optionWhere);
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
