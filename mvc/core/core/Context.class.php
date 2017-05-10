<?php

namespace core;
use ReflectionClass;
use ReflectionMethod;
use Exception;

class Context {
    
    private static $instance=null;  //context实例
    private $name;  //服务器名称
    private $ip;  //服务器IP
    private $attribute;  //上下文参数
    private $modules;  //全部的模块
    private $filters;  //过滤器
    private $running;  //是否正则运行应用
    private $currentMethod;  //当前请求所使用的方法
    private $factory;  //依赖注入的工厂方法数组
    private $param=null;  //依赖注入所需要的参数
    
    private function __construct() {
        $this->name=request()->server('SERVER_NAME');
        $this->ip=request()->server('SERVER_ADDR');
        $this->modules=explode(',',MODULE);
        $this->attribute=array();
        $this->filters=array();
        $this->running=false;
        $this->factory=array();
    }

    /**
     * Conetxt单例的实现
     * @return Conetxt
     */
    public static function instance(){
        if(self::$instance==null){
            self::$instance=new Context();
        }
        return self::$instance;
    }
    
    /**
     * 返回服务器IP地址
     * @return string
     */
    public function ip(){
        return $this->ip;
    }
    
    /**
     * 返回服务器名称
     * @return string
     */
    public function name(){
        return $this->name;
    }

    /**
     * 应用是否正则运行
     * @return bool
     */
    public function isRun(){
        return $this->running;
    }
    
    /**
     * 获取上下文参数
     * @param string $key 上下文参数键值
     * @param string $default  为空时默认值
     * @return $mixed
     */
    public function get($key=null,$default=null){
        if($key==null){
            return $this->attribute;
        }
        return $this->has($key)&&Validate::required($this->attribute[$key])?$this->attribute[$key]:$default;
    }
    
    /**
     * 设置上下文参数
     * @param string $key  上下文参数键值
     * @param string $value    上下文参数值
     * @return \core\Context
     */
    public function set($key,$value){
        $this->attribute[$key]=$value;
        return $this;
    }
    /**
     * 判断上下文参数key是否存在
     * @param string $key  上下文参数键值
     * @return bool
     */
    public function has($key){
        return isset($this->attribute[$key]);
    }
    
    /**
     * 移除上下文参数
     * @param string $key  上下文参数键值
     * @return \core\Context
     */
    public function remove($key){
        unset($this->attribute[$key]);
        return $this;
    }

    /**
     * 注册过滤器
     * @param type $regex
     * @param type $callable
     */
    public function filter($regex,$callable){
        $this->filters[$regex]=$callable;
    }
    
    /**
     * 注册依赖注入的方法
     * @param string $key
     * @param \Closure $callable 回调函数
     */
    public function factory($key,$callable){
        $this->factory[$key]=$callable;
    }
    
    /**
     * 按照注册的方法，对请求进行过滤操作
     */
    private function doFilte(){
        $url=request()->url();
        foreach ($this->filters as $key=>$callable){
            if(preg_match('#'.$key.'#',$url)){
                $callable(request());
            }
        }
    }
    
    /**
     * 运行应用
     */
    public function run(){
        $module=request()->module();
        $controller=request()->control();
        $action=request()->action();
        if(!$this->running){
            $this->doFilte();
            if(!in_array($module,$this->modules)){
                $error='模块非法访问';
                response()->error(MVC_PATH.'common/page/error.php',$error);
            }
        }
        $this->running=true;
        $this->execute($module,$controller,$action);
    }
    
    /**
     * 执行控制器方法
     * @param string $module  模型名称
     * @param string $controller  控制器名称
     * @param string $action  操作名称
     * @return null  操作名称
     * @throws Exception
     */
    private function execute($module,$controller,$action){
        if(!DEBUG){
            try {
                //控制器类
                $class='modules\\'.$module.'\\controller\\'.$controller;
                if(!class_exists($class)){
                    throw new Exception(''); 
                }
                //通过反射实例化，并执行
                $reflector=new ReflectionClass($class);
                $method=$reflector->getMethod($action);
                $instance=$reflector->newInstance();
                $this->invoke($method,$instance,$this->getParams($method));
            }catch (Exception $ex) {
                //返回404页面
                response()->noFound(config('404_PAGE'));
            }
            return;
        }
        $this->debugInvoke($module, $controller, $action);
    }
    
    /**
     * 调试时，先检查在执行控制器方法
     * @param string $module  模块名称
     * @param string $controller  控制器名称
     * @param string $action  操作名称
     */
    private function debugInvoke($module,$controller,$action){
        //检测控制器文件是否存在
        $controllerFile = APP_PATH.'modules/'.$module.'/controller/'.$controller.'.class.php';
        if (!file_exists($controllerFile)) {
            $error = request()->control().'控制器文件不存在';
            response()->error(MVC_PATH.'common/page/error.php',$error);
        }
        //检测控制器类是否存在
        $class='modules\\'.$module.'\\controller\\'.$controller;
        if (!class_exists($class)) {
            $error=request()->control(). '控制器类不存在';
            response()->error(MVC_PATH.'common/page/error.php',$error);
        }
        //实例化
        $control=new $class();
        //检测控制器方法是否可执行
        if(!is_callable(array($control,$action))){
            $error = $controller.'控制器非法操作：'.$action;
            response()->error(MVC_PATH.'common/page/error.php',$error);
        }
        //通过反射执行控制器方法
        try{
            $method=new ReflectionMethod($control,$action);
            $this->invoke($method,$control,$this->getParams($method));
        }catch (Exception $ex) {
            $ex=str_replace("\n","</br>\n",$ex);
            response()->error(MVC_PATH.'common/page/error2.php',$ex);
        }
    }
    
    /**
     * 执行控制器方法后，对返回的结果进行输出，如果控制器首个参数为$post则视为只接受post请求,若首个参数为$get则视为只接受get请求
     * @param \ReflectionFunctionAbstract $method  反射方法类
     * @param mixed  $instance  控制器类实例
     * @param array $params  传入控制器的参数
     * @return mixed
     */
    private function invoke($method,$instance,$params){
        $docArr=doc_parse($method->getDocComment());
        if(isset($docArr['method'])&&!empty($docArr['method'])&&strtoupper($docArr['method'])!=request()->method()){
            if(isset($docArr['redirect'])){
                response()->redirect($docArr['redirect']);
            }else{
                response()->noFound(config('404_PAGE'));
            }
            return;
        }
        $result=$method->invokeArgs($instance,$params);
        //为空则不输出
        if($result===null){
            return;
        }
        if(is_array($result)){    //为数组，则返回json数据
            echo json_encode($result);    //为数组，则返回json数据
        }else if($result instanceof View){   //View类，则输出模板
            $result->show();
        }else{
            echo $result;   //默认直接输出结果
        }
    }
    
    /**
     * 获取反射方法的参数
     * @param \ReflectionFunctionAbstract $method  反射方法类
     * @param string $methodName 需要注入的参数的名字
     * @return array
     * @throws Exception
     */
    private function getParams($method,$methodName=null){
        if($this->param==null){
            //初始化注入参数
            $request=request();
            $post=$request->post();
            $get=$request->get();
            //内置request，get与post注入
            $this->param=array('request'=>$request,'get'=>$get,'post'=>$post);
        }
        $result=array();
        //获取方法的参数名称
        $params=$method->getParameters();
        $this->currentMethod=empty($params[0])?null:$params[0]->getName();
        foreach($params as $param){
            $paramNmae=$param->getName();
            if(isset($this->param[$paramNmae])){   //返回内置参数
                $result[] = $this->param[$paramNmae];
            }else if(isset($this->factory[$paramNmae])){    //如果注册过，则按照注册的方法返回，注册方法中，也可以注入参数
                if($methodName==$paramNmae){
                    throw new \Exception('Function stack overflow');
                }
                $function=new \ReflectionFunction($this->factory[$paramNmae]);
                $factoryData=$function->invokeArgs($this->getParams($function,$paramNmae));
                $this->param[$paramNmae]=$factoryData;
                $result[]=$factoryData;
            }else if(isset($this->param['post'][$paramNmae])){
                $result[]=$this->param['post'][$paramNmae];
            }else if(isset($this->param['get'][$paramNmae])){
                $result[]=$this->param['get'][$paramNmae];
            }else if($param->isDefaultValueAvailable()){ 
              $result[]=$param->getDefaultValue(); 
            }else{
              $result[]=null;
            }
        }
        return $result;
    }
    
}
