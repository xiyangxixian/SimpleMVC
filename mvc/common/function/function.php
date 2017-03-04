<?php

use core\Request;
use core\Response;
use core\Context;
use core\Session;
use core\Cookie;
use lib\db\Db;
use lib\util\HashTable;

function request(){
    return Request::instance();
}

function context(){
    return Context::instance();
}

function response(){
    return Response::instance();
}

function session(){
    return Session::instance();
}

function cookie(){
    return Cookie::instance();
}

function db($table){
    return Db::table($table);
}

function db_union($column,Db $db1,Db $db2,$all=false){
    return Db::union($column, $db1, $db2, $all);
}

function arr_create(array $array){
    return new HashTable($array);
}

function load_app($class){
    $file=APP_PATH.str_replace('\\','/', $class).'.class.php';
    if (is_file($file)) {
        require($file);
    }
}

function small_to_hump($char,$ucfirst = true){
    $char = ucwords(str_replace('_', ' ', $char));
    $char = str_replace(' ','',$char);
    return $ucfirst?$char:lcfirst($char);
}

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

function view($file='index.php',$data=array()){
    if(strpos($file,'@')){
        $array=explode('@', $file);
        $dir=$array[0];
        $view=$array[1];
        $path=APP_PATH.'modules/'.request()->module().'/view/'.$dir.'/';
    }else{
        $view=$file;
        $path=APP_PATH.'modules/'.request()->module().'/view/'.hump_to_small(request()->control()).'/';
    }
    context()->set('view_path',$path);
    extract($data);
    include $path.$view;
}

function import($view,$param=''){
    $path=context()->get('view_path','');
    include $path.$view;
}

function public_import($view='index.php',$param=''){
    $path=APP_PATH.'/common/';
    include $path.$view;
}

function str_decode($str){
    $pattern=array(
        '#javascript[\S\s]*?:#i'
    );
    $replacement=array(
        ''
    );
    return preg_replace($pattern, $replacement,htmlspecialchars(escape_str(trim($str)),ENT_QUOTES));
}

function str_encode($str){
    return preg_replace('#/\*([\S\s]*?)\*/#','$1',$str);
}

function escape_str($str) {
    return (get_magic_quotes_gpc()) ? stripslashes($str):$str;
}

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

function intval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = intvar_array($value);
        }else{
            $array[$key] = intval($value);
        }
    }
}

function floatval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = floatval_array($value);
        }else{
            $array[$key] = floatval($value);
        }
    }
}

function doubleval_array(array $array){
    foreach ($array as $key=>$value){
        if(is_array($value)){
            $array[$key] = doubleval_array($value);
        }else{
            $array[$key] = doubleval($value);
        }
    }
}


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

function voeach(array $array,callable $output,callable $empty=null){
    if(empty($array)&&$empty!=null){
        $empty();
        return;
    }
    foreach($array as $k=>$v){
        $output($v,$k);
    }
}
