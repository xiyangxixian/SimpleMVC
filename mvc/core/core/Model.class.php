<?php

namespace core;
use lib\db\Db;
/**
 * 模型类
 */
class Model extends Db{
    
    protected $attribute;    //模型属性
    
    /**
     * 构造方法
     * @param array $attribute 设置模型属性
     */
    public function __construct(array $attribute=array()) {
        parent::__construct(self::getCurrentTableName($this->name()));
        $this->attribute=$attribute;
    }
    
    public static function map($className){
        return Entity::map($className);
    }


    /**
     * 返回模型名字，对应表名称，默认为类名称
     * @return string
     */
    protected function name(){
        $name=strrchr(get_class($this),'\\');
        return hump_to_small(substr($name,1));
    }

    /**
     * 获取模型属性
     * @param string $key  模型属性名
     * @param string $default  为空时，返回的默认值
     * @return mixed
     */
    public function get($key=null,$default=null) {
        if($key==null){
            return $this->attribute;
        }
        return isset($this->attribute[$key])&&Validate::required($this->attribute[$key])?$this->attribute[$key]:$default;
    }

    /**
     * 设置模型属性
     * @param mixed $mixed 属性键值或者数组，数组时，对已有属性进行合并
     * @param string $value 属性值
     * @return \core\Model
     */
    public function set($mixed,$value=null) {
        if(is_array($mixed)){
            $this->attribute=array_merge($this->attribute,$mixed);
            return $this;
        }
        $this->attribute[$mixed]=$value;
        return $this;
    }
    
    /**
     * 移除属性值
     * @param string $key  属性键值
     * @return \core\Model
     */
    public function remove($key){
        unset($this->attribute[$key]);
        return $this;
    }
    
    /**
     * 设置查询条件
     */
    private function parseCondition(){
        foreach ($this->attribute as $key=>$value){
            $this->where($key,$value);
        }
    }
    
    /**
     * 查询行数
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return int
     */
    public function count($column=null){
        $this->parseCondition();
        return parent::count($column);
    }
    
    /**
     * 查询最大值
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return mixed
     */
    public function max($column=null){
        $this->parseCondition();
        return parent::max($column);
    }
    
    /**
     * 查询最小值
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return mixed
     */
    public function min($column=null){
        $this->parseCondition();
        return parent::min($column);
    }
    
    /**
     * 查询平均值
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return mixed
     */
    public function avg($column=null){
        $this->parseCondition();
        return parent::avg($column);
    }
    
    /**
     * 求和
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return type
     */
    public function sum($column=null){
        $this->parseCondition();
        return parent::sum($column);
    }
    
    /**
     * 模型添加数据
     * @param array $data 此处参数不使用，只为了和父类的参数保存一直
     * @return bool
     */
    public function add(array $data=null){
        return parent::add($this->attribute);
    }
    
    /**
     * 查找一条数据
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return \core\Model  如果反向不到则返回null
     */
    public function find($column=null){
        $this->parseCondition();
        if(($row=parent::find($column))==null){
            return null;
        }
        $this->attribute=array_merge($this->attribute,$row);
        return $this;
    }
    
    /**
     * 查询多条数据
     * @param string $column 查询的字段，用逗号隔开，支持数组
     * @return array
     */
    public function select($column=null){
        $this->parseCondition();
        return parent::select($column);
    }
    
    /**
     * 模型更新数据
     * @param array $data 此处参数不使用，只为了和父类的参数保存一直
     * @return bool
     */
    public function update(array $data=null){
        return parent::update($this->attribute);
    }
    
    /**
     * 模型删除数据
     * @return bool
     */
    public function delete(){
        $this->parseCondition();
        return parent::delete();
    }
    
}
