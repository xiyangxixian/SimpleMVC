<?php

namespace core;
use lib\db\Db;
/**
 * 模型类
 */
class Entity{
    
    protected $mapMode;
    private $classDoc;
    private $reflect;
    private $propNames;
    private $props;
    private $propDoc=array();
    private $propToColumn=array();
    private $columnToProp=array();
    private $db;
    private static $modeInstanceArray=array();
    
    protected function __construct($className,$alias=null) {
        $this->parseMapMode();
        $this->reflect=new \ReflectionClass($className);
        $this->classDoc=doc_parse($this->reflect->getDocComment());
        $this->props=$this->reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $this->parseField();
        $this->db=db($this->getTableName().(empty($alias))?'':'['.$alias.']');
    }

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
    
    public function add($obj){
        $data=$this->mapObjToArr($obj);
        $this->db->add($data);
    }
    
    public function find($column=null){
        $data=$this->db->find($column);
        if($data!=null){
            return $this->mapArrToObj($data);
        }
        return null;
    }

    public function select($column=null){
        $arr=array();
        $data=$this->db->select($column);
        return new ModelIterator($this, $iterator);
        foreach ($data as $value){
            $arr[]=$this->mapArrToObj($value);
        }
        return $arr;
    }

    private function parseField(){
        foreach ($this->props as $value){
            $docArr=doc_parse($value->getDocComment());
            $propName=$value->getName();
            $this->propDoc[$propName]=$docArr;
            $this->propNames[]=$propName;
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
        }
    }
    
    private function getAnimation($prop,$name){
        $propName=$prop->getName();
        return $this->propDoc[$propName][$name];
    }
    
    private function parseMapMode(){
        $mode=config('DB_CONFIG','DB_TYPE');
        switch ($mode){
            case 'hump':
                $this->mapMode=new \ReflectionFunction('ucfirst');
                break;
            case 'smallHumb':
                $this->mapMode=new \ReflectionFunction('lcfirst');
                break;
            default :
                $this->mapMode=new \ReflectionFunction('hump_to_small');
        }
    }
    
    private function getTableName(){
        if(isset($this->classDoc['table'])){
            return $this->_classDoc['table'];
        }
        $name=strrchr($this->_reflect->getName(),'\\');
        return $this->fieldMap(substr($name,1));
    }
    
    private function fieldMap($str){
        return $this->mapMode->invoke($str);
    }
    
    public function mapObjToArr($obj){
        $data=array();
        foreach ($this->propNames as $value){
            if(isset($obj->$value)){
                $column=$this->propToColumn[$value];
                $data[$column]=$obj->$value; 
            }
        }
        return $data;
    }
    
    public function mapArrToObj($arr){
        $obj=$this->reflect->newInstance();
        foreach ($arr as $key=>$value){
            $propName=$this->columnToProp[$key];
            $obj->$propName=$value;
        }
        return $obj;
    }
    
}
