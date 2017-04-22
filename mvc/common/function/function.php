<?php

use core\Request;
use core\Response;
use core\Context;
use core\Session;
use core\Cookie;
use core\View;
use lib\db\Db;
use lib\util\HashTable;

/**
 * 快速实例化Request类
 * @return core\Request
 */
function request(){
    return Request::instance();
}

/**
 * 快速实例化Context类
 * @return core\Context
 */
function context(){
    return Context::instance();
}

/**
 * 快速实例化Response类
 * @return core/Response
 */
function response(){
    return Response::instance();
}

/**
 * 快速实例化Session类
 * @return core\Session
 */
function session(){
    return Session::instance();
}

/**
 * 快速实例化Cookie类
 * @return core\Cookie
 */
function cookie(){
    return Cookie::instance();
}
/**
 * 快速实例化View类
 * @return core\View
 */
function view($file=null){
    return new View($file);
}

/**
 * 快速实例化Db类
 * @param string $table 表名称，不带前缀，表全名用“/表全名”表示,否则自动加上配置文件中的表前缀
 * @return lib\db\Db
 */
function db($table){
    return Db::table($table);
}

/**
 * 快速进行联合查询
 * @param mixed $column 字段名称，查询多个字段可用“,”隔开，或者用数组表示
 * @param Db $db1 Db类
 * @param Db $db2 Db类
 * @param type $all 是否为UNION All，默认为false
 * @return array
 */
function db_union($column,Db $db1,Db $db2,$all=false){
    return Db::union($column, $db1, $db2, $all);
}

/**
 * 快速构造HashTable类
 * @param array $array 数组
 * @return lib\util\HashTable
 */
function arr_create(array $array){
    return new HashTable($array);
}

/**
 * 应用目录中的类自动加载
 * @param string $class 类名称
 */
function load_app($class){
    $file=APP_PATH.str_replace('\\','/', $class).'.class.php';
    if (is_file($file)) {
        require($file);
    }
}

function doc_parse($doc){
    $result=array();
    if($doc===false||preg_match_all('/^\s*\*\s*(@.*)/m',$doc,$lines)===0){
        return $result;
    }
    foreach ($lines[1] as $line){
        $line=rtrim($line);
        if (($offset=strpos($line,' '))>0) {
            $param=substr($line,1,$offset-1 );  
            $value=ltrim(substr($line,strlen($param)+2)); // Get the value  
        } else {  
            $param=substr($line,1);  
            $value='';  
        }
        if($param!='param'&&$param!='return'&&$param!='throw'){
            if(!isset($result[$param])){
                $result[$param]=$value;
            }else if(!is_array($result[$param])){
                $result[$param]=array($result[$param],$value);
            }else{
                $result[$param][]=$value;
            }
        }
    }
    return $result;
}

/**
 * 下划线转驼峰
 * @param string $char 输入字符串
 * @param type $ucfirst  首字母是否小写
 * @return string
 */
function small_to_hump($char,$ucfirst = true){
    $char = ucwords(str_replace('_', ' ', $char));
    $char = str_replace(' ','',$char);
    return $ucfirst?$char:lcfirst($char);
}

/**
 * 驼峰转下划线
 * @param string $char 输入字符串
 * @return string
 */
function hump_to_small($char){
    $new='';
    for($i=0,$len=mb_strlen($char);$i<$len;$i++){
        $pre=$i==0?0:ord($char[$i-1]);
        $num=ord($char[$i]);
        $next=$i==$len-1?0:ord($char[$i+1]);
        $new.=($i!=0&&$num>=65&&$num<=90&&(($pre>=97&&$pre<=122)||($next>=97&&$next<=122)))?'_'.$char[$i]:$char[$i];
    }
    return strtolower($new);
}

/**
 * 根据模板名称返回模板绝对路径
 * @param string $template 模板名称，有三种表示形式，模块目录@控制器目录/模板名；控制器目录/模板名；模板名
 * 模块目录名为空则表示，全局模块目录，为this则表示当前请求的模块
 * @return string
 */
function template($template=null){
    //为空则表示当前操作名转小写
    if($template==null){
        $template=hump_to_small(request()->action());
    }
    if(strpos($template,'@')!==false){
        //指定模块
        $array=explode('@', $template);
        $module=$array[0]=='this'?request()->module():$array[0];
        $view=$array[1];
    }else{
        //默认为当前模块
        $module=request()->module();
        //是否指定控制器
        $view=strpos($template,'/')!==false?$template:(hump_to_small(request()->control()).'/'.$template);
    }
    $moduleDir=empty($module)?'':$module.'/';
    $viewPth=APP_PATH.'modules/'.$moduleDir.'view/';
    return $viewPth.$view.'.php';
}

/**
 * 快速导入模板
 * @param string $template  模板名称
 */
function import($template){
    include template($template);
}

/**
 * 格式化字符串，将HTML，单引号和双引号进行转码
 * @param string $str
 * @return string
 */
function str_decode($str){
    $pattern=array(
        '#javascript[\S\s]*?:#i'
    );
    $replacement=array(
        ''
    );
    return preg_replace($pattern, $replacement,htmlspecialchars(escape_str(trim($str)),ENT_QUOTES));
}

/**
 * 格式化字符串，SQL的注释进行转义
 * @param string $str
 * @return string
 */
function str_encode($str){
    return preg_replace('#/\*([\S\s]*?)\*/#','$1',$str);
}

/**
 * 格式化字符串，去反斜杠
 * @param string $str
 * @return string
 */
function escape_str($str) {
    return (get_magic_quotes_gpc()) ? stripslashes($str):$str;
}

/**
 * 格式化数组中的全部字符串
 * @param array $array
 * @return array
 */
function escape_input(array $array){
    foreach($array as $key=>$value){
        if (is_array($value)){
            $array[$key] = escape_input($value);
        }else{
            $array[$key] = str_decode($value);
        }
    }
    return $array;
}

/**
 * 格式化数组中的参数为int
 * @param array $array
 * @return array
 */
function intval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = intvar_array($value);
        }else{
            $array[$key] = intval($value);
        }
    }
    return $array;
}

/**
 * 格式化数组中的参数为float
 * @param array $array
 * @return array
 */
function floatval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = floatval_array($value);
        }else{
            $array[$key] = floatval($value);
        }
    }
    return $array;
}

/**
 * 格式化数组中的参数为double
 * @param array $array
 * @return array
 */
function doubleval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = doubleval_array($value);
        }else{
            $array[$key] = doubleval($value);
        }
    }
}

/**
 * 在模板中根据索引输出数组
 * @param array $array 需要遍历的数组
 * @param callable $output 循环时输出函数，并传入值和索引
 * @param callable $empty 数组为空时输出的函数
 * @param int $offest 开始索引
 * @param int $length 遍历的长度
 * @param int $step  遍历的长度
 */
function volist(array $array,callable $output,callable $empty=null,$offest=0,$length=null,$step=1){
    if(empty($array)&&$empty!=null){
        $empty();
        return;
    }
    $len=count($array);
    $end=$length==null?$len:$offest+$length*$step;
    $end=$end>$len?$len:$end;
    for($i=$offest;$i<$end;$i+=$step){
        $output($array[$i],$i);
    }
}

/**
 * 直接表里数组在模板中输出。
 * @param array $array 需要遍历的数组
 * @param callable $output 循环时输出函数，并传入值和索引
 * @param callable $empty 数组为空时输出的函数
 */
function voeach($array,callable $output,callable $empty=null){
    $isEmpty=true;
    foreach($array as $k=>$v){
        $output($v,$k);
        $isEmpty=false;
    }
    if($isEmpty&&$empty!=null){
        $empty();
    }
}
