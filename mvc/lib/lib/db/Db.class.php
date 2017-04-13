<?php

namespace lib\db;
use lib\db\driver\Mysql;
use lib\db\driver\PDODriver;

class Db {
    
    protected static $db=null;
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
        $dbtype=isset($config['DB_TYPE'])?$config['DB_TYPE']:'pdo';
        if($dbtype=='mysql'){
            self::$db=Mysql::instance();
        }else{
            self::$db=PDODriver::instance();
        }
        self::$db->loadConfig($config);
    }

    public static function getCurrentTableName($tableName){
        $currentTableName=null;
        if(strpos($tableName,'/')!=false){
            $currentTableName=substr($tableName,1);
        }else{
            $currentTableName=self::$tablePrefix.$tableName;
        }
        $aliasArr=self::getAlias($currentTableName);
        return '`'.$aliasArr[0].'`'.(empty($aliasArr[1])?'':' AS '.$aliasArr[1]);
    }
    
    private static function getAlias($str){
        $result=preg_match('/\[(.+?)\]/',$str,$m);
        $alias=null;
        if($result&&isset($m[1])){
            $alias='`'.($m[1]).'`';
            $str=preg_replace('/\[(.+?)\]/','',$str);
        }
        return array($str,$alias);
    }
    
    private function init(){
        self::checkDb();
        $this->initOptions();
    }
    
    private static function checkDb(){
        if(self::$db==null){
            throw new \Exception('Can not load database driver.The config may not set up.');
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
        return self::$db->find($sql);
    }
    
    public function count($column=null){
        $result=$this->find($column.'|count[`count`]');
        return isset($result['count'])?$result['count']:$result;
    }
    
    public function max($column=null){
        $result=$this->find($column.'|max[`max`]');
        return isset($result['max'])?$result['max']:$result;
    }
    
    public function min($column=null){
        $result=$this->find($column.'|min[`min`]');
        return isset($result['min'])?$result['min']:$result;
    }
    
    public function avg($column=null){
        $result=$this->find($column.'|avg[`avg`]');
        return isset($result['avg'])?$result['avg']:$result;
    }
    
    public function sum($column=null){
        $result=$this->find($column.'|sum[`sum`]');
        return isset($result['sum'])?$result['sum']:$result;
    }
    
    public function select($column=null){
        $sql=$this->selectSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->select($sql);
    }

    public function add(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->insertSql('('.  $this->getKeys($currentData).') VALUES ('.$this->getValues($currentData).')');
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }
    
    public function addAll(array $data=null){
       $currentData=$this->getData($data);
       $dataArray=array();
       foreach ($currentData as $item){
            $str='('.$this->getValues($item).')';
            $dataArray[]=$str;
       }
       $sql=$this->insertSql('('.  $this->getKeys($currentData[0]).') VALUES '.  implode(',',$dataArray));
       if(isset($this->options['sql'])){
           return $this->getSql($sql);
        }
       $this->initOptions();
       return self::$db->execute($sql);
    }
    
    public function update(array $data=null){
        $currentData=$this->getData($data);
        $sql=$this->updateSql($this->setKeyAndValue($currentData));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
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
                $conditionArray[$value][]='WHEN ? THEN ?';
                $arr[]=$item[$case];$arr[]=$item[$value];
            }
        }
        $out=array();
        foreach ($conditionArray as $key=>$value){
            $out[]='`'.$key.'`=CASE`'.$case.'` '.implode(' ', $value).' END';
        }
        $this->options['dataParam']=array_merge($this->options['dataParam'],$arr);
        $sql=$this->updateSql(implode(',',$out));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }


    public function delete(){
        $sql=$this->deleteSql();
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }
    
    private function selectSql($column){
        $distinct=isset($this->options['distinct'])?'DISTINST ':'';
        $sql='SELECT '.$distinct.$this->buildFeilds($column).' FROM '.$this->buildJoin().$this->getOption();
        $this->bind();
        return $sql;
    }
    
    private function insertSql($data){
        $sql='INSERT INTO '.$this->currentTableName.' '.$data;
        $this->bind();
        return $sql;
    }

    private function updateSql($data){
        $sql='UPDATE '.$this->currentTableName.' SET '.$data.$this->getOption();
        $this->bind();
        return $sql;
    }

    private function deleteSql(){
        $sql='DELETE FROM '.$this->currentTableName.$this->getOption();
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
        $aliasArr=$this->getAlias($field);
        $alias=$aliasArr[0];
        $field=$aliasArr[1];
        if(strpos($field,'|')===false){
            return $this->addSlashes($field).(empty($alias)?'':' AS '.$alias);
        }
        $fieldArr=explode('|', $field);
        $column=$this->addSlashes(array_shift($fieldArr));
        foreach ($fieldArr as $fun){
            $params=explode(':',$fun);
            $funName=array_shift($params);
            array_unshift($params,$column);
            $column=strtoupper($funName).'('.implode(',',$params).')';
        }
        return $column.(empty($alias)?'':' AS '.$alias);
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

    public static function query($sql,$param=null){
        self::checkDb();
        if($param!=null){
            self::$db->bind(is_array($param)?$param:array($param));
        }
        return self::$db->select($sql);
    }
    
    public static function execute($sql,$param=null){
        self::checkDb();
        if($param!=null){
            self::$db->bind(is_array($param)?$param:array($param));
        }
        return self::$db->execute($sql);
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
        $args=$this->parseWhere($column,$mixed,$param);
        $this->buildWhereOption($args[0],$args[1]);
        return $this;
    }
    
    public function xwhere($column,$mixed,$param=null){
        if(empty($param)||($param!==null&&empty($param))){
            return $this;
        }
        return $this->where($column,$mixed,$param);
    }
    
    private static function whereIn($column,$condition,array $array){
        $sql=str_repeat(',?',count($array));
        $sql=substr($sql,1);
        $sql='`'.$column.'` '.$condition.' ('.$sql.')';
        return $sql;
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
    
    private function parseWhere($column,$mixed,$param=null){
        if($param===null){
            $condition='=';
            $param=$mixed;
        }else{
            $condition=$mixed;
        }
        $sql='';
        $data=null;
        $condition=strtoupper($condition);
        if(strpos($condition,'BETWEEN')!==false&&is_array($param)){
            $sql='`'.$column.'`'.' '.$condition.' ? AND ?';
            $data=array($param);
        }else if(is_array($param)){  
            $sql=self::whereIn($column,$condition,$param);
            $data=$param;
        }else{
            $sql='`'.$column.'`'.' '.$condition.' ?';
            $data=array($param);
        }
        return array($data,$sql);
    }
    
    public function whereOr($column,$mixed,$param=null,$group=null){
        $args=$this->parseWhere($column,$mixed,$param);
        $group=$group===null?'db':$group;
        $this->buildOrOption($args[0],$args[1],$group);
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
        $this->options['order']='ORDER BY '.$this->buildFeilds($order);
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
        $this->options['group']='GROUP BY '.$this->buildFeilds($group);
        return $this;
    }
    
    public function having($having){
        if(empty($having)){
            return $this;
        }
        $args=func_get_args();
        array_shift($args);
        $this->options['havingParam']=array_merge($this->options['havingParam'],$args);
        $this->options['having']='HAVING '.$having;
        return $this;
    }
    
    public function join($table,$condition,$type='inner'){
        $currentTable=self::getCurrentTableName($table);
        $this->options['join'][]=$type.' JOIN '.$currentTable.' ON '.$condition;
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
            self::$db->bind($optionArr);
            return $this;
        }
        if(is_array($param)){
            self::$db->bind($param);
        }else{
            self::$db->bind(array($param));
        }
        return $this;
    }

    public function getOption(){
        $option='';
        $optionWhere=array();
        if(isset($this->options['where'])&&!empty($this->options['where'])){
            $optionWhere[]='('.implode(') AND (', $this->options['where']).')';
        }
        if(isset($this->options['or'])&&!empty($this->options['or'])){
            $tempArr=array();
            foreach ($this->options['or'] as $key=>$value){
                $tempArr[]='('.implode(') OR (', $value).')';
                $params=$this->options['orParam'][$key];
                $this->options['whereParam']=array_merge($this->options['whereParam'],$params);
            }
            $optionWhere[]='('.implode(') AND (', $tempArr).')';
        }
        if(!empty($optionWhere)){
            $option.=' WHERE '.implode(' AND ',$optionWhere);
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
        $union=$all?'UNION ALL':'UNION';
        $sql=$db1->sql()->select($column).' '.$union.' '.$db2->sql()->select($column);
        return $this->query($sql);
    }
    
}
