<?php

namespace core;

class View {
    
    private $template;
    private $param;
    
    public function __construct($template=null) {
       $this->param=array();
       //获取模板的绝对路径
       $this->template=template($template);
    }
    
    /**
     * 为模板赋值， 数组或者key=>value形式
     * @param mixed $mixed
     * @param type $value
     * @return \core\View
     */
    public function assign($mixed,$value=null){
        if(is_array($mixed)){
            $this->param=array_merge($this->param,$mixed);
        }else{
            $this->param[$mixed]=$value;
        }
        return $this;
    }

    /**
     * 渲染模板
     * @param type $mixed
     * @param type $value
     * @return \core\View
     */
    function show(){
        extract($this->param);
        include $this->template;
    }
}
