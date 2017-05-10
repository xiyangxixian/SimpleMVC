<?php

namespace core;
use lib\db\Db;
use lib\util\HashTable;
/**
 * 实体映射类
 */
class Model implements ValidateRule{
    
    protected $mapMode;  //类属性映射数据库字段的模式
    private $classDoc;  //所有实体类的注释
    private $reflect;  //实体类的反射类
    private $propNames;  //所有公有属性的名字
    private $props;  //所有属性的反射类
    private $propDoc=array();  //属性的注释
    private $propToColumn=array();  //属性名称转化为数据库字段名称
    private $columnToProp=array();  //数据库字段名称转换为属性名称
    private $db;  //数据库类
    private static $modeInstanceArray=array();  //实例数组
    private $idFieldName=null;  //id的字段名称

    protected function __construct($className,$alias=null) {
        $this->parseMapMode();  //设置映射模式
        $this->reflect=new \ReflectionClass($className);  //实例化反射类
        $this->classDoc=doc_parse($this->reflect->getDocComment());  //获取类的注释
        $this->props=$this->reflect->getProperties(\ReflectionProperty::IS_PUBLIC);  //获取所有公有字段
        $this->parseField();  //设置属性映射
        $this->db=db($this->getTableName().(empty($alias)?'':'['.$alias.']')); //获取表名称
    }
    
    /**
     * 实例化模型类
     * @param string $className  类名称，默认为model命名空间下的类 如Entity::mat(admin)等同于Entity::mat(model\\admin)
     * @return \core\Entity
     */
    public static function map($className){
        if(strpos($className,'\\')===false){
            $className='model\\'.$className;
        }
        if(isset(self::$modeInstanceArray[$className])){
            return self::$modeInstanceArray[$className];
        }
        $aliasArr=Db::getAlias($className);
        $model=new Model($aliasArr[0],$aliasArr[1]);
        self::$modeInstanceArray[$className]=$model;
        return $model;
    }
    
    /**
     * 添加数据
     * @param mixed $obj 实体类的实例，或者为数组，为数组时，字段名称需要与数据库字段名称对应。
     * @return bool
     */
    public function add($obj){
        if(is_array($obj)){
            $data=$obj;
        }else{
            $data=$this->mapObjToArr($obj);
        }
        
        //如果id字段为空，则去除
        $columnName=$this->propToColumn[$this->idFieldName];
        if(isset($data[$columnName])&&empty($data[$columnName])){
            unset($data[$columnName]);
        }
        return $this->db->add($data);
    }
    
    /**
     * 获取数据表数量
     * @param mixed $column 查询的字段名称，与Db类中的保持一致
     * @return bool
     */
    public function count($column=null){
        return $this->db->count($column);
    }

    /**
     * 查找一条数据
     * @param mixed $column 与Db类中的保持一致
     * @return 成功返回对应的实体类，失败返回null
     */
    public function find($column=null){
        $data=$this->db->find($this->getColumns(func_get_args()));
        if($data!=null){
            return $this->mapArrToObj($data);
        }
        return null;
    }
    
    
    /**
     * 查找一条数据
     * @param mixed $column 与Db类中的保持一致
     * @param int $id 字段的id值
     * @return 成功返回对应的实体类，失败返回null
     */
    public function findById($column=null,$id=null){
        $args=func_get_args();
        $id= array_pop($args);
        $this->parseId($id);
        return $this->find($this->getColumns($args));
    }

    /**
     * 查询多条数据
     * 可接受数组或者不定长的参数作为查询的字段值，与Db类保持一致
     * @return \core\ModelIterator
     */
    public function select(){
        $args=func_get_args();
        $result=null;
        if(isset($args[0])&&is_array($args[0])){
            $result=$this->db->select($args[0]);
        }else{
            $result=$this->db->select($args);
        }
        return new ModelIterator($this,$result);
    }
    
    /**
     * 更新数据
     * @param mixed $obj 实体类的实例，或者为数组，为数组时，字段名称需要与数据库字段名称对应。
     * @return bool
     */
    public function update($obj){
        if(is_array($obj)){
            $data=$obj;
        }else{
            $data=$this->mapObjToArr($obj);
        }
        //如果id字段不为空,则将id作为查询条件
        if($this->idFieldName!=null){
            $idProp=$this->idFieldName;
            if(isset($obj->$idProp)){
                $id=$obj->$idProp;
                $this->parseId($id); 
            }
        }
        return $this->db->update($data);
    }
    
    /**
     * 删除数据
     * @return bool
     */
    public function delete(){
        return $this->db->delete();
    }
    
    
    /**
     * 通过id删除数据
     * @return bool
     */
    public function deleteById($id){
        $this->parseId($id);
        return $this->db->delete();
    }

    /**
     * 设置查询条件
     * @param string $column 查询的字段，与Db类保持一致
     * @param string $mixed 字段值
     * @param string $param 非medoo时用
     * @return \core\Model
     */
    public function where($column,$mixed,$param=null){
        $column=$this->getColumn($column);
        $this->db->where($column,$mixed,$param);
        return $this;
    }
    
    /**
     * 谨慎设置查询条件
     * @param string $column 查询的字段，与Db类保持一致
     * @param string $mixed 字段值
     * @param string $param 非medoo时用
     * @return \core\Model
     */
    public function xwhere($column,$mixed,$param=null){
        $column=$this->getColumn($column);
        $this->db->where($column,$mixed,$param);
        return $this;
    }
    
    /**
     * 设置查询条件
     * @param type $column 查询的OR字段，与medoo保持一致，whereOR('column[!]','hello')
     * @param type $mixed 字段值
     * @param type $param 非medoo时用
     * @return \core\Entity
     */
    public function whereOr($column,$mixed,$param=null,$group=null){
        $column=$this->getColumn($column);
        $this->db->where($column,$mixed,$param,$group);
        return $this;
    }
    
    //非medoo时用
    public function xwhereOr($column,$mixed,$param=null,$group=null){
        $column=$this->getColumn($column);
        $this->db->where($column,$mixed,$param,$group);
        return $this;
    }
    
    /**
     * 根据属性名称获取真实的数据库字段名称用于数据库查询
     * @param array $props
     * @return mixed
     */
    private function getColumns($props){
        $arr=array();
        foreach ($props as $value){
            if(isset($this->propToColumn[$value])){
                $arr[]=$this->propToColumn[$value];
            }else{
                $arr[]=$value;
            }
        }
        if(empty($arr)){
            return null;
        }
    }
    
    /**
     * 根据属性名称获取真实的数据库字段名称用于数据库查询
     * @param array $prop
     * @return mixed
     */
    private function getColumn($prop){
        if(isset($this->propToColumn[$prop])){
            return $this->propToColumn[$prop];
        }
        return $prop;
    }

    /**
     * 用于获取数据库示例，闭包查询
     * @param callback $fun 回调函数
     * @return \core\Entity
     */
    public function query($fun){
        $fun($this->db);
        return $this;
    }

    /**
     * 如果设置了id，则将id作为查询条件
     * @param type $id
     */
    private function parseId($id){
        if($this->idFieldName!==NULL){
            $idColumn=$this->propToColumn[$this->idFieldName];
            $this->where($idColumn,$id);
        }
    }

    /**
     * 设置属性映射，将字段名称与数据库中的相对应
     * 实体类中，添加@column字段的属性，将被映射为数据库字段，默认将属性名称转换为小驼峰，大驼峰，小写下划线模式，@column 字段名   可以直接指定数据库字段名称
     */
    private function parseField(){
        foreach ($this->props as $value){
            $docArr=doc_parse($value->getDocComment());
            $propName=$value->getName();
            $this->propDoc[$propName]=$docArr;
            $this->propNames[]=$propName;
            //获取@column值
            if(isset($docArr['column'])){
                if(empty($docArr['column'])){
                    $column=$this->fieldMap($propName);
                    $this->propToColumn[$propName]=$column;
                    $this->columnToProp[$column]=$propName;
                }else{
                    $this->propToColumn[$propName]=$docArr['column'];
                    $this->columnToProp[$docArr['column']]=$propName; 
                } 
            }
            if(isset($docArr['id'])){
                $this->idFieldName=$propName;
            }
        }
    }
    
    /**
     * 用于获取属性的注解
     * @param string $prop 属性名
     * @param string $name 注解名
     * @return string
     */
    public function getAnimation($prop,$name){
        $propName=$prop->getName();
        return $this->propDoc[$propName][$name];
    }
    
    /**
     * 设置映射模式
     */
    private function parseMapMode(){
        $mode=config('DB_CONFIG','DB_MAP');
        switch ($mode){
            case 'hump':
                //大驼峰
                $this->mapMode=new \ReflectionFunction('ucfirst');
                break;
                //小驼峰
            case 'smallHumb':
                $this->mapMode=new \ReflectionFunction('lcfirst');
                break;
            default :
                //小写下划线
                $this->mapMode=new \ReflectionFunction('hump_to_small');
        }
    }
    
    /**
     * 获取表名称
     * @return string
     */
    private function getTableName(){
        if(isset($this->classDoc['table'])){
            return $this->classDoc['table'];
        }
        $name=strrchr($this->reflect->getName(),'\\');
        return $this->fieldMap(substr($name,1));
    }
    
    /**
     * 字段名称映射
     * @param string $str
     * @return string
     */
    private function fieldMap($str){
        return $this->mapMode->invoke($str);
    }
    
    /**
     * 将对象映射为数组，主要用于数据库和实体类的映射
     * @param mixed $obj 实体类对象
     * @return array
     */
    public function mapObjToArr($obj){
        $data=array();
        foreach ($this->propToColumn as $key=>$value){
            if(isset($obj->$key)){
                $data[$value]=$obj->$key; 
            }
        }
        return $data;
    }
    
    /**
     * 将数组映射为对象，主要用于数据库和实体类的映射
     * @param array $arr 数组
     * @return mixed 实体类对象
     */
    public function mapArrToObj($arr){
        $obj=$this->reflect->newInstance();
        foreach ($arr as $key=>$value){
            $propName=$this->columnToProp[$key];
            $obj->$propName=$value;
        }
        return $obj;
    }
    
    /**
     * 通过数组快速构建实体类，主要用于请求数据与实体类的映射
     * @param array $arr 数组
     * @param array $mapArr 映射数组 ，比如需要将post中的 username字段映射为实体类的name属性，那么$mapArr=array('username'=>'name')
     * @return mixed 书体类对象
     */
    public function factory($arr,$mapArr=array()){
        $obj=$this->reflect->newInstance();
        foreach ($arr as $key=>$value){
            $propName=isset($mapArr[$key])?$mapArr[$key]:$key;
            $obj->$propName=$value;
        }
        return $obj;
    }

    /**
     * 获取属性注解上的@msg信息，用于验证提示
     * @return array
     */
    public function getMsg() {
        $arr=array();
        foreach ($this->propDoc as $key=>$value){
            if(isset($value['msg'])){
                $arr[$key]=$value['msg'];
            }
        }
        return $arr;
    }

    /**
     * 获取属性注解上的@rule信息，用于验证
     * @return type
     */
    public function getRule() {
        $arr=array();
        foreach ($this->propDoc as $key=>$value){
             if(isset($value['rule'])){
                $arr[$key]=$value['rule'];
            }
        }
        return $arr;
    }
    
    /**
     * 进行验证
     * @param array 验证的数组或实体类对象，为数组时，需要键值需要与属性名称保存一致
     * @param array $checkArr 验证的字段
     * @param array $unCheckArr 不需要验证的字段
     * @param bool $isRescursion  是否进行批量验证，是则验证全部，否则一个验证不通过时，则终断验证
     * @return bool 返回验证结果 
     * @throws \Exception  抛出错误信息
     */
    public function check($data,$checkArr=null,$unCheckArr=array(),$isRescursion=false){
        $validate=new Validate($this);
        $validate->scene($checkArr);
        $validate->unScene($unCheckArr);
        if(is_array($data)){
            $result=$validate->check($data, $isRescursion);
        }else{
            $arr=array();
            foreach ($data as $key=>$value){
                $arr[$key]=$value;
            }
            $result=$validate->check($arr, $isRescursion);
        }
        if(!$result){
            throw new \Exception($validate->getError());
        }
        return $result;
    }

}
