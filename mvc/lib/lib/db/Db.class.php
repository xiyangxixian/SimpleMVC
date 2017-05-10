<?php

namespace lib\db;
use lib\db\driver\Mysql;
use lib\db\driver\PDODriver;

class Db {
    
    protected static $db=null;  //数据库驱动实例
    protected $currentTableName=''; //表的真实名字
    protected $options;  //表的真实名字
    protected $saveOption=array();  //保存的查询条件
    
    protected static $tablePrefix='';  //前缀
    protected static $instanceArray=array();  //数据库连接实例池

    /**
     * 构造方法
     * @param string $tableName 表真实名称
     */
    protected function __construct($tableName) {
        $this->currentTableName=$tableName;
        $this->init();
    }
    
    /**
     * 实例化Db类
     * @param string $table 表名称，非全表名，执行该方法后，会自动加上表前缀，如果名称以/开头则忽略表前缀，直接视为全表名
     * @return Db;
     */
    public static function table($table){
        $table=self::getCurrentTableName($table);
        if(isset(self::$instanceArray[$table])){
            $db=self::$instanceArray[$table];
            $db->clearOptions();
        }else{
           $db=new Db($table);
           self::$instanceArray[$table]=$db;
        }
        return $db;
    }
    
    /**
     * 加载数据库配置文件
     * @param array $config 数据库配置，参考内置配置文件的写法
     */
    public static function loadConfig(array $config){
        self::$tablePrefix=isset($config['DB_PREFIX'])?$config['DB_PREFIX']:'';
        $dbtype=isset($config['DB_TYPE'])?$config['DB_TYPE']:'pdo';
        if($dbtype=='mysqli'){
            self::$db=Mysql::instance();
        }else{
            self::$db=PDODriver::instance();
        }
        self::$db->loadConfig($config);
    }

    /**
     * 获取真实的表名称
     * @param string $tableName  传入的表名称
     * @return string
     */
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
    
    /**
     * 获取别名，包括表别名和字段别名，中括号内的视为别名，如'tableName[name]'
     * @param string $str
     * @return array
     */
    public static function getAlias($str){
        $alias=null;
        $name=$str;
        if(($offset=strpos($str,'['))>0){
            $name=substr($str,0,$offset);
            $alias=substr($str,$offset);
            $alias=str_replace(array('[',']'),'',$alias);
        }
        return array($name,$alias);
    }
    
    /**
     * 初始化参数
     */
    private function init(){
        self::checkDb();
        $this->initOptions();
    }
    
    /**
     * 检测db是否被实例化
     * @throws \Exception
     */
    private static function checkDb(){
        if(self::$db==null){
            throw new \Exception('Can not load database driver.The config may not set up.');
        }
    }
    
    /**
     * 初始化参数
     */
    private function initOptions(){
        $this->options=array();
        $this->options['where']=array();   //where条件参数
        $this->options['whereParam']=array();  //where值参数
        $this->options['dataParam']=array();  //data值参数
        $this->options['or']=array();  //or条件参数
        $this->options['orParam']=array();  //or值参数
        $this->options['havingParam']=array();  //having条件参数
        $this->options['join']=array();  //join条件参数
        $this->options['join'][]=$this->currentTableName;
        $this->options['order']=array();  //order条件参数
        $this->options=array_merge($this->options,$this->saveOption);
    }
    
    /**
     * 保存当前的查询条件
     */
    public function saveOptions(){
        $this->saveOption=$this->options;
        return $this;
    }
    
    /**
     * 清除当前的查询条件
     */
    public function clearOptions(){
        $this->saveOption=array();
        $this->initOptions();
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name,age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function find(){
        $column=func_get_args();
        $sql=$this->selectSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->find($sql);
    }
    
    public function rowCount(){
        return $this->db->rowCount();
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name,age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function count($column=null){
        $result=$this->find($column.'|count[count]');
        return is_array($result)?$result['count']:$result;
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name,age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function max($column=null){
        $result=$this->find($column.'|max[max]');
        return is_array($result)?$result['max']:$result;
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name,age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function min($column=null){
        $result=$this->find($column.'|min[min]');
        return is_array($result)?$result['min']:$result;
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name,age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function avg($column=null){
        $result=$this->find($column.'|avg[avg]');
        return is_array($result)?$result['avg']:$result;
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如find('name','age')等同于find(array('name','age'))
     * 支持函数表达式find('id|count')等同于find(count(`id`))；find('age|round:2')等同于find(array('round(`age`,2)'))只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return type
     */
    public function sum($column=null){
        $result=$this->find($column.'|sum[sum]');
        return is_array($result)?$result['sum']:$result;
    }
    
    /**
     * 查询一条数据
     * @param mixed $column 查询的字段，用逗号隔开，也可为数组如select('name','age')或者select(array('name','age'));
     * 支持函数表达式select('id|count')等同于select(count(`id`))；select('age|round:2')等同于select('round(`age`,2)')只有，round中有逗号，只能用数组表示字段
     * 表达式规律为：字段名|函数名:参数1:参数2...:参数n[别名]
     * @return DbResult;
     */
    public function select(){
        $column=func_get_args();
        $sql=$this->selectSql($column);
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->select($sql);
    }

    /**
     * 添加数据，成功时返回true，失败时发回false
     * @param array $data 添加的数据
     * @return bool
     */
    public function add($data){
        $currentData=$this->getData($data);
        $sql=$this->insertSql('('.  $this->getKeys($currentData).') VALUES ('.$this->getValues($currentData).')');
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }
    
    /**
     * 添加多条数据，成功时返回true，失败时返回false
     * @param array $data 添加的数据
     * @return bool
     */
    public function addAll(array $data){
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

    /**
     * 更新数据，成功时发回true，失败时返回false
     * @param array $data 更新的数据
     * @return bool
     */
    public function update($data){
        $currentData=$this->getData($data);
        $sql=$this->updateSql($this->setKeyAndValue($currentData));
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }
    
    /**
     * 更新多条数据，成功时发回true，失败时范湖false
     * @param string $case case的栏目
     * @param array $data
     * @return bool
     */
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

    /**
     * 删除数据
     */
    public function delete(){
        $sql=$this->deleteSql();
        if(isset($this->options['sql'])){
            return $this->getSql($sql);
        }
        $this->initOptions();
        return self::$db->execute($sql);
    }
    
    /**
     * 返回select语句
     * @param string $column 字段名称
     * @return string
     */
    private function selectSql($column){
        if(isset($column[0])&&is_array($column[0])){
            $column=$column[0];
        }
        $distinct=isset($this->options['distinct'])?'DISTINCT ':'';
        $sql='SELECT '.$distinct.$this->buildFeilds($column).' FROM '.$this->buildJoin().$this->getOption();
        $this->bind();
        return $sql;
    }
    
    /**
     * 返回insert语句
     * @param string 添加的数据
     * @return string
     */
    private function insertSql($data){
        $sql='INSERT INTO '.$this->currentTableName.' '.$data;
        $this->bind();
        return $sql;
    }

    /**
     * 返回update语句
     * @param array $data 更新的数据
     * @return string
     */
    private function updateSql($data){
        $sql='UPDATE '.$this->currentTableName.' SET '.$data.$this->getOption();
        $this->bind();
        return $sql;
    }

    /**
     * 返回删除的sql
     * @return string
     */
    private function deleteSql(){
        $sql='DELETE FROM '.$this->currentTableName.$this->getOption();
        $this->bind();
        return $sql;
    }
    
    /**
     * 将查询的字段表达式，转换为SQL语句
     * @param string $column
     * @return string
     */
    private function buildFeilds($column){
        if(empty($column)){
            return '*';
        }
        $arr=array();
        foreach ($column as $value){
            $arr[]=$this->formatFeild($value);
        }
        return implode(',', $arr);
    }
    
    /**
     * 格式化单个字段
     * @param string $field
     * @return string
     */
    private function formatFeild($field){
        $aliasArr=$this->getAlias($field);
        $field=$aliasArr[0];
        $alias=$aliasArr[1];
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
    
    /**
     * 为字段添加反引号
     * @param string $field
     * @return string
     */
    private function addSlashes($field){
        if($field=='*'||empty($field)){
            return '*';
        }
        if(strpos($field,'`')!==false||strpos($field,'(')!==false){
            return $field;
        }
        return '`'.$field.'`';
    }

    /**
     * 原生sql查询
     * @param string $sql
     * @param 预准备语句绑定的参数
     * @return type
     */
    public static function query($sql,$param=null){
        self::checkDb();
        if($param!=null){
            self::$db->bind(is_array($param)?$param:array($param));
        }
        return self::$db->select($sql);
    }
    
    /**
     * 执行原生SQL语句
     * @param string $sql
     * @param $param 预准备语句绑定的参数
     * @return type
     */
    public static function execute($sql,$param=null){
        self::checkDb();
        if($param!=null){
            self::$db->bind(is_array($param)?$param:array($param));
        }
        return self::$db->execute($sql);
    }
    
    public static function exec($sql){
        self::checkDb();
        return self::$db->exec($sql);
    }
    
    /**
     * 获取添加或者更新的数据
     * @param string $data
     * @return array
     */
    private function getData($data){
        return is_array($data)?$data:array();
    }

    /**
     * 获取插入数据对应的字段信息
     * @param array $data
     * @return string
     */
    public function getKeys(array $data){
        return '`'.implode('`,`',array_keys($data)).'`';
    }
    
    /**
     * 获取插入数据的值信息
     * @param array $data
     * @return string
     */
    public function getValues(array $data){
        $this->options['dataParam']=array_merge($this->options['dataParam'],array_values($data));
        return substr(str_repeat(',?',count($data)),1);
    }
    
    /**
     * 获取更新数据的key和value的sql
     * @param array $data
     * @return type
     */
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
    
    /**
     * where语句，可执行多次，为and连接
     * @param type $column 字段名称
     * @param type $mixed 条件或者条件值，为条件值时，条件默认为=号
     * @param type $param 条件值
     * @return \lib\db\Db
     */
    public function where($column,$mixed,$param=null){
        $args=$this->parseWhere($column,$mixed,$param);
        $this->buildWhereOption($args[0],$args[1]);
        return $this;
    }
    
    /**
     * 条件值为空时，不执行where语句
     * @param type $column 字段名称
     * @param type $mixed 条件或者条件值，为条件值时，条件默认为=号
     * @param type $param 条件值
     * @return \lib\db\Db
     */
    public function xwhere($column,$mixed,$param=null){
        if(($param==null&&(empty($mixed)&&$mixed!='0'))||($param!==null&&(empty($param)&&$param!='0'))){
            return $this;
        }
        return $this->where($column,$mixed,$param);
    }
    
    /**
     * where语句，可执行多次，为or连接
     * @param type $column 字段名称
     * @param type $mixed 条件或者条件值，为条件值时，条件默认为=号
     * @param type $param 条件值
     * @param type $group 在执行多次whereOr情况下，group相同的之间为or连接，不同为and连接
     * @return \lib\db\Db
     */
    public function whereOr($column,$mixed,$param=null,$group=null){
        $args=$this->parseWhere($column,$mixed,$param);
        $group=$group===null?'db':$group;
        $this->buildOrOption($args[0],$args[1],$group);
        return $this;
    }
    
    /**
     * 条件值为空时，不执行whereOr语句
     * @param type $column 字段名称
     * @param type $mixed 条件或者条件值，为条件值时，条件默认为=号
     * @param type $param 条件值
     * @param type $group 在执行多次whereOr情况下，group相同的之间为or连接，不同为and连接
     */
    public function xwhereOr($column,$mixed,$param=null,$group=null){
        if(empty($param)||($param!==null&&empty($param))){
            return $this;
        }
        return $this->whereOr($column,$mixed,$param,$group);
    }
    
    /**
     * 返回where in或者 not in的sql语句
     * @param string $column 字段名
     * @param string $condition 条件名
     * @param array $array in的数组
     * @return string
     */
    private static function whereIn($column,$condition,array $array){
        $sql=str_repeat(',?',count($array));
        $sql=substr($sql,1);
        $sql=$column.' '.$condition.' ('.$sql.')';
        return $sql;
    }

    /**
     * 原生where语句，参数不确定，第一个为原生的where语句，绑定的参数用?代替，并在后面的参数参入，如condition('id=? and age=?',1,20)
     * @param string $condition 条件表达式
     * @return \lib\db\Db
     */
    public function condition($condition){
        if(empty($condition)){
            return $this;
        }
        $args=func_get_args();
        array_shift($args);
        $this->buildWhereOption($args,$condition);
        return $this;
    }
    
    /**
     * 构造where语句
     * @param string $column 字段名称
     * @param string $mixed 条件或条件值
     * @param type $param 绑定的参数
     * @return type
     */
    private function parseWhere($column,$mixed,$param=null){
        if($param===null){
            $condition='=';  //默认条件为=号
            $param=$mixed;
        }else{
            $condition=$mixed;
        }
        $sql='';
        $data=null;
        $condition=strtoupper($condition);
        $column=$this->formatFeild($column);
        if(strpos($condition,'BETWEEN')!==false&&is_array($param)){
            //between and
            $sql=$column.' '.$condition.' ? AND ?';
            $data=$param;
        }else if(is_array($param)){
            // in ()
            $sql=self::whereIn($column,$condition,$param);
            $data=$param;
        }else{
            $sql=$column.' '.$condition.' ?';
            $data=array($param);
        }
        return array($data,$sql);
    }
    
    /**
     * 设置where参数
     * @param array $options where绑定的预准备语句参数
     * @param string $condition 绑定的条件
     */
    private function buildWhereOption($options,$condition){
        $this->options['whereParam']=array_merge($this->options['whereParam'],$options);
        $this->options['where'][]=$condition;
    }
    
    /**
     * 设置where参数
     * @param array $options where绑定的预准备语句参数
     * @param string $condition 绑定的条件
     * @param string $group or的分钟
     */
    private function buildOrOption($options,$condition,$group){
        if(!isset($this->options['or'][$group])){
            $this->options['or'][$group]=array();
            $this->options['orParam'][$group]=array();
        }
        $this->options['orParam'][$group]=array_merge($this->options['orParam'][$group],$options);
        $this->options['or'][$group][]=$condition;
    }
    
    /**
     * order by语句
     * @param string $order 支持字段表达式，同select
     * @return \lib\db\Db
     */
    public function order($order,$type='ASC'){
        $this->options['order'][]=$this->formatFeild($order).' '.strtoupper($type);
        return $this;
    }
    
    /**
     * 分页查询
     * @param int $offest 查询的条数或者起始值，1个参数表示查询的条数，2个参数时表示起始值
     * @param int $length
     * @return \lib\db\Db
     */
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
    
    /**
     * 分页查询，可配合limit使用
     * @param int $page 查询的页数
     * @param int $rowNum 每页的条数
     * @return \lib\db\Db
     */
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
    
    /**
     * group by 语句，支持字段表达式，同select
     * @param type $group
     * @return \lib\db\Db
     */
    public function group(){
        $group=func_get_args();
        $this->options['group']='GROUP BY '.$this->buildFeilds($group);
        return $this;
    }
    
    /**
     * having语句，用法同condition
     * @param string $having
     * @return \lib\db\Db
     */
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
    
    /**
     * join语句
     * @param string $table join的表不带表前缀，以/开头代表全表名
     * @param string $condition join的条件
     * @param string $type join的类型，默认为INNER
     * @return \lib\db\Db
     */
    public function join($table,$condition,$type='INNER'){
        $currentTable=self::getCurrentTableName($table);
        $this->options['join'][]=strtoupper($type).' JOIN '.$currentTable.' ON '.$condition;
        return $this;
    }
    
    /**
     * 构造join语句，join方法可悲执行多次
     * @return string
     */
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

    /**
     * distinct选项，通了不重复查询
     * @return \lib\db\Db
     */
    public function distinct(){
        $this->options['distinct']=true;
        return $this;
    }
    
    /**
     * sql方法被执行之后，不进行数据库查询，脂肪sql语句
     * @return \lib\db\Db
     */
    public function sql(){
        $this->options['sql']=true;
        return $this;
    }
    
    /**
     * 不执行数据库查询，返回真是的sql语句
     * @param string $sql 预准备的sql语句
     * @return string
     */
    public function getSql($sql){
        foreach (array_merge($this->options['dataParam'],$this->options['whereParam'],$this->options['havingParam']) as $value){
            if(is_string($value)){
               $sql=preg_replace('/\?/','\''.addslashes($value).'\'',$sql,1);
            }else{
               $sql=preg_replace('/\?/',$value,$sql,1); 
            }
        }
        return $sql;
    }

    /**
     * 绑定与准语句的参数
     * @param string $param 绑定预准备聚聚参数
     * @return \lib\db\Db
     */
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

    /**
     * 根据条件构造sql语句
     * @return string
     */
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
        if(!empty($this->options['order'])){
            $option.='ORDER BY '.implode(',', $this->options['order']);
        }
        if(isset($this->options['offest'])){
            $option.=' LIMIT '.$this->options['offest'].','.$this->options['length'];
        }
        return $option;
    }

    /**
     * 联合查询
     * @param string $column 查询的字段，支持字段表达式
     * @param \lib\db\Db $db1 Db类
     * @param \lib\db\Db $db2 Db类
     * @param type $all true 为UNION ALL
     * @return array
     */
    public static function union($column,Db $db1,Db $db2,$all=false){
        $union=$all?'UNION ALL':'UNION';
        $sql=$db1->sql()->select($column).' '.$union.' '.$db2->sql()->select($column);
        return self::query($sql);
    }
    
    public static function close(){
        self::$db->close();
    }
    
    public static function beginTransaction(){
        self::$db->beginTransaction();
    }
    
    public static function commit(){
        self::$db->commit();
    }
    
    public static function rollBack(){
        self::$db->rollBack();
    }
    
}
