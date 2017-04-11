<?php

namespace core;
use ReflectionClass;
use lib\net\IP;

class Validate {
    
    private $rules;
    private $msg;
    private $error;
    private $scene=null;

    public function __construct($rules,array $msg=array()) {
        if($rules instanceof ValidateRule){
            $this->rules=$rules->getRule();
            $this->msg=$rules->getMsg();
        }else{
            $this->rules=$rules;
            $this->msg=$msg;
        }
    }
    
    public final function check(array $data,$isRescursion=false){
        $checkResult=true;
        if($isRescursion){
            $this->error=array();
            foreach ($this->rules as $key=>$rule){
                if($this->scene!==null&&!in_array($key,$this->scene)){
                    continue;
                }
                if(!isset($data[$key])){
                    $data[$key]=null;
                }
                $result=$this->checkOneForResult($key,$rule,$data[$key]);
                if($result!==true){
                    $this->error[$key]=$result;
                    $checkResult=false;
                }
            }
        }else{
            $this->error=null;
            foreach ($this->rules as $key=>$rule){
                if($this->scene!==null&&!in_array($key,$this->scene)){
                    $result=$this->checkOneForResult($key,$rule,null);
                    continue;
                }
                if(!isset($data[$key])){
                    $data[$key]=null;
                }
                $result=$this->checkOneForResult($key,$rule,$data[$key]);
                if($result!==true){
                    $this->error=$result;
                    return false;
                }
            }
        }
        return $checkResult;
    }
    
    public function scene ($arr){
        $this->scene=$arr;
    }
    
    private final function checkOneForResult($key,$rule,$value){
        if(empty($rule)){
           $rule='emptyValidate';
        }
        $msg=isset($this->msg[$key])?$this->msg[$key]:'';
        $result=self::checkItem($value,$rule,$msg);
        return $result;
    }
    
    public function getError(){
        return $this->error;
    }

    protected final function emptyValidate(){
        return true;
    }
    
    public static function getClassName(){
        return __CLASS__;
    }
    
    public final static function checkItem($data,$rules,$msg=''){
        $reflection=new ReflectionClass(static::getClassName());
        $result=self::staticInvoke($reflection,trim($data),$rules);
        return self::getMsg($msg,$result);
    }
    
    private static function getMethods($rules){
        return is_array($rules)?$rules:explode('|', $rules);
    }
    
    private final static function staticInvoke(ReflectionClass $reflection,$data,$rules){
        $methods=self::getMethods($rules);
        $reqIndex=array_search('required',$methods);
        $index=0;
        if(!self::required($data)){
            if($reqIndex===false){ 
                return true;
            }else{
                return $reqIndex;
            }
        }
        foreach ($methods as $rule){
            $result=self::execute($reflection,$data,$rule);
            if(!$result){
                return $index;
            }
            $index++;
        }
        return true; 
    }
    
    private final static function execute(ReflectionClass $reflection,$data,$rule){
        $arr=explode(':',$rule,2);
        $method=$arr[0];
        $reflectionMethod=$reflection->getMethod($method);
        if(isset($arr[1])){
            $arg=$arr[1];
            $result=$reflectionMethod->invokeArgs(null,array($data,$arg));
        }else{
            $result=$reflectionMethod->invokeArgs(null,array($data));
        }
        return $result;
    }
    
    
    private final static function getMsg($msg,$result){
        if($result===true){
            return true;
        }else{
            $msgs=is_array($msg)?$msg:explode('|', $msg);
            $currentMsg=empty($msgs)?'':isset($msgs[$result])?$msgs[$result]:array_pop($msg);
            return $currentMsg;
        }
    }
    
    protected final static function getArgs($args){
        return explode(',', $args);
    }
    
    public final static function required($value){
        return !empty($value)||'0' == $value;
    }
    
    public final static function email($value){
        return !(filter_var($value,FILTER_VALIDATE_EMAIL)===false);
    }
    
    public final static function name($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}a-zA-Z]+$#u');
    }
    
    public final static function qq($value){
        return self::regex($value,'#^[1-9]\d{4,10}$#');
    }
    
    public final static function phone($value){
        return self::regex($value,'#^13[0-9]{9}$|14[0-9]{9}|15[0-9]{9}$|18[0-9]{9}$#');
    }
    
    public final static function domain($value){
        return self::regex($value,'#^[0-9a-zA-Z\u4e00-\u9fa5]+[0-9a-zA-Z\u4e00-\u9fa5\.-]*\.[a-zA-Z]{2,4}$#');
    }
    
    public final static function idCard($value){
        return self::regex($value,'#^(^\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$#');
    }
    
    public final static function enAndNum($value){
        return self::regex($value,'#^[A-Za-z0-9]+$#');
    }
    
    public final static function enAndNumAndChs($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$#u');
    }
    
    public final static function en($value){
        return self::regex($value,'#^[A-Za-z]+$#');
    }
    
    public final static function chs($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}]+$#u');
    }
    
    public final static function num($value){
        return self::regex($value,'#^[0-9]+$#');
    }
    
    public final static function port($value){
        $port=intval($value);
        return strpos($value,'.')===false&&$port>=1&&$port<=65535;
    }
    
    public final static function ip($value){
        return !(filter_var($value,FILTER_VALIDATE_IP)===false);
    }
    
    public final static function url($value){
        return !(filter_var($value,FILTER_VALIDATE_URL)===false);
    }
    
    public final static function date($value){
        return !(strtotime($value)===false);
    }
    
    public final static function regex($value,$arg){
        return 1===preg_match($arg,(string)$value);
    }
    
    public final static function contain($value,$arg){
        $sensitive=true;
        $arr=self::getArgs($arg);
        if(isset($arr[1])){
            $sensitive=$arr[1]=='true';
        }
        if($sensitive){
            return self::regex($value,'#'.$arr[0].'#');
        }
        return self::regex($value,'#'.$arr[0].'#i');
    }
    
     public final static function equals($value,$arg){
        $sensitive=true;
        $arr=self::getArgs($arg);
        if(isset($arr[1])){
            $sensitive=$arr[1]=='true';
        }
        if($sensitive){
            return self::regex($value,'#^'.$arr[0].'$#');
        }
        return self::regex($value,'#^'.$arr[0].'$#i');
    }
    
    public final static function in($value,$arg){
        $arr=self::getArgs($arg);
        return in_array($value,$arr);
    }
    
    public final static function notIn($value,$arg){
        $arr=self::getArgs($arg);
        return !in_array($value,$arr);
    }
    
    public final static function gt($value,$arg){
        return $value>$arg;
    }
    
    public final static function egt($value,$arg){
        return $value>=$arg;
    }
    
    public final static function lt($value,$arg){
        return $value<$arg;
    }
    
    public final static function elt($value,$arg){
        return $value<=$arg;
    }
    
    public final static function range($value,$arg){
        $arr=self::getArgs($arg);
        return $value>=$arr[0]&&$value<=$arr[1];
    }
    
    public final static function length($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        if(isset($arr[1])){
            return $len>=$arr[0]&&$len<=$arr[1];
        }
        return $len==$arr[0];
    }
    
    public final static function max($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        return $len<=$arr;
    }
    
    public final static function min($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        return $len>=$arr;
    }
    
    public final static function before($value,$arg){
        return strtotime($value)<=strtotime($arg);
    }
    
    public final static function after($value,$arg){
        return strtotime($value)<=strtotime($arg);
    }
    
    public final static function dateRange($value,$arg){
        $arr=self::getArgs($arg);
        $date=strtotime($value);
        return $date>=strtotime($arr[0])&&$date<=strtotime($arr[1]);
    }
    
    public final static function ipRnage($value,$arg){
        $arr=self::getArgs($arg);
        $ip=IP::ipToInt($value);
        return $ip>=IP::ipToInt($arr[0])&&$ip<=IP::ipToInt($arr[1]);
    }
    
    public final static function ipIn($value,$arg){
        $ip=IP::ipToInt($value);
        $ipArr=explode('/',$arg);
        if(!isset($ipArr[1])){
            $ipArr[1]=32;
        }
        $min=IP::ipToInt($ipArr[0]);
        $max=$min+pow(2,32-$ipArr[1])-1;
        return $ip>=$min&&$ip<=$max;
    }
    
}
