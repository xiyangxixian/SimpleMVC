<?php

namespace core;
use ReflectionClass;
use ReflectionMethod;
use Exception;

class Context {
    
    private static $instance=null;
    private $name;
    private $ip;
    private $attribute;
    private $modules;
    private $filters;
    private $running;
    private $currentMethod=null;
    
    private function __construct() {
        $this->name=request()->server('SERVER_NAME');
        $this->ip=request()->server('SERVER_ADDR');
        $this->modules=explode(',',MODULE);
        $this->attribute=array();
        $this->filters=array();
        $this->running=false;
    }

    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Context();
        }
        return self::$instance;
    }
    
    public function ip(){
        return $this->ip;
    }
    
    public function name(){
        return $this->name;
    }

    public function isRun(){
        return $this->running;
    }
    
    public function get($key=null,$default=null){
        if($key==null){
            return $this->attribute;
        }
        return $this->has($key)?$this->attribute[$key]:$default;
    }
    
    public function set($key,$value){
        $this->attribute[$key]=$value;
        return $this;
    }
    
    public function has($key){
        return isset($this->attribute[$key]);
    }
    
    public function remove($key){
        unset($this->attribute[$key]);
        return $this;
    }


    public function filter_register($regex,callable $callable){
        $this->filters[$regex]=$callable;
    }
    
    private function doFilte(){
        $url=request()->url();
        foreach ($this->filters as $key=>$callable){
            if(preg_match('#'.$key.'#',$url)){
                $callable(request(),response());
            }
        }
    }
    
    public function run(){
        $module=request()->module();
        $controller=request()->control();
        $action=request()->action();
        if(!$this->running){
            $this->doFilte();
            if(!in_array($module,$this->modules)){
                $error='模块非法访问';
                response()->error(ROOT_PATH.'common/page/error.php',$error);
            }
        }
        $this->running=true;
        $this->execute($module,$controller,$action);
    }
    
    private function execute($module,$controller,$action){
        if(!DEBUG){
            try {
                $class='modules\\'.$module.'\\controller\\'.$controller;
                if(!class_exists($class)){
                    throw new Exception(''); 
                }
                $reflector=new ReflectionClass($class);
                $method=$reflector->getMethod($action);
                $instance=$reflector->newInstance();
                $this->invoke($method,$instance,$this->getParams($method));
            }catch (Exception $ex) {
                response()->noFound(config('404_PAGE'));
            }
            return;
        }
        $this->debugInvoke($module, $controller, $action);
    }
    
    private function debugInvoke($module,$controller,$action){
        $controllerFile = APP_PATH.'modules/'.$module.'/controller/'.$controller.'.class.php';
        if (!file_exists($controllerFile)) {
            $error = request()->control().'控制器文件不存在';
            response()->error(ROOT_PATH.'common/page/error.php',$error);
        }
        $class='modules\\'.$module.'\\controller\\'.$controller;
        if (!class_exists($class)) {
            $error=request()->control(). '控制器类不存在';
            response()->error(ROOT_PATH.'common/page/error.php',$error);
        }
        $control=new $class();
        if(!is_callable([$control,$action])){
            $error = $controller.'控制器非法操作：'.$action;
            response()->error(ROOT_PATH.'common/page/error.php',$error);
        }
        try{
            $method=new ReflectionMethod($control,$action);
            $this->invoke($method,$control,$this->getParams($method));
        }catch (Exception $ex) {
            $ex=str_replace("\n","</br>\n",$ex);
            response()->error(ROOT_PATH.'common/page/error2.php',$ex);
        }
    }
    
    private function invoke($method,$instance,$params){
        if(!empty($this->currentMethod)&&(($this->currentMethod=='post'&&!request()->isPost())||($this->currentMethod=='get'&&!request()->isGET()))){
            response()->noFound(config('404_PAGE'));
            return;
        }
        $result=$method->invokeArgs($instance,$params);
        if($result===null){
            return;
        }
        if(is_array($result)){
            echo json_encode($result);
        }else{
            echo $result;
        }
    }
    
    private function getParams($method){
        $option=array_merge(request()->get(),request()->post(),array('request'=>request(),'get'=>request()->get(),'post'=>request()->post()));
        $result=array();
        $params=$method->getParameters();
        $this->currentMethod=empty($params[0])?null:$params[0]->getName();
        foreach($params as $param){
          if(isset($option[$param->getName()])){ 
            $result[] = $option[$param->getName()]; 
          } 
          else if($param->isDefaultValueAvailable()){ 
            $result[]=$param->getDefaultValue(); 
          }else{
            $result[]=null;
          }
        } 
        return $result;
    }
    
}
