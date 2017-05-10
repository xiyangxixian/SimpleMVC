<?php

namespace core;
use ReflectionClass;
use lib\net\IP;

class Validate {
    
    private $rules;  //验证规则
    private $msg;  //验证消息
    private $error;  //错误消息
    private $scene=null;  //验证场景

    /**
     * 构造方法
     * @param \core\ValidateRule $rules  可为数组或ValidateRule类
     * @param array $msg
     */
    public function __construct($rules,array $msg=array()) {
        if($rules instanceof ValidateRule){
            $this->rules=$rules->getRule();
            $this->msg=$rules->getMsg();
        }else{
            $this->rules=$rules;
            $this->msg=$msg;
        }
    }
    
    /**
     * 对数组进行验证
     * @param array $data
     * @param bool $isRescursion 是否进行批量验证，false时，其中一个字段验证不同过，则返回false，错误消息为字符串；
     * true时，其中一个验证不通过，将继续验证，错误消息未数组；
     * @return boolean
     */
    public final function check(array $data,$isRescursion=false){
        //验证结果
        $checkResult=true;
        if($isRescursion){
            $this->error=array();
            //对验证规则进行遍历
            foreach ($this->rules as $key=>$rule){
                //非验证字段则跳过
                if($this->scene!==null&&!in_array($key,$this->scene)){
                    continue;
                }
                if(!isset($data[$key])){
                    $data[$key]=null;
                }
                //逐个验证
                $result=$this->checkOneForResult($key,$rule,$data[$key]);
                if($result!==true){
                    $this->error[$key]=$result;
                    $checkResult=false;
                }
            }
        }else{
            $this->error=null;
            //对验证规则进行遍历
            foreach ($this->rules as $key=>$rule){
                //非验证字段则跳过
                if($this->scene!==null&&!in_array($key,$this->scene)){
                    $result=$this->checkOneForResult($key,$rule,null);
                    continue;
                }
                if(!isset($data[$key])){
                    $data[$key]=null;
                }
                //逐个验证
                $result=$this->checkOneForResult($key,$rule,$data[$key]);
                if($result!==true){
                    $this->error=$result;
                    return false;
                }
            }
        }
        return $checkResult;
    }
    
    /**
     * 设置验证场景
     * @param array $arr 设置验证的字段
     */
    public function scene($arr){
        $this->scene=$arr;
        return $this;
    }
    
    
    /**
     * 设置不需要验证的字段
     * @param array $arr 设置验证的字段
     */
    public function unScene($arr){
        if(!is_array($arr)){
            return $this;
        }
        foreach ($arr as $value){
            unset($this->rules[$value]);
        }
        return $this;
    }
    
    /**
     * 设置规则
     * @param 字段 $key
     * @param 规则 $rule
     * @return \core\Validate
     */
    public function rule($key,$rule){
        $this->rules[$key]=$rule;
        return $this;
    }
    
    /**
     * 验证其中一项
     * @param string $key  验证的字段
     * @param string $rule  单条验证规则
     * @param string $value  验证值
     * @return bool
     */
    private final function checkOneForResult($key,$rule,$value){
        //如果验证规则为空，则表示不验证
        if(empty($rule)){
           $rule='emptyValidate';
        }
        $msg=isset($this->msg[$key])?$this->msg[$key]:'';
        $result=self::checkItem($value,$rule,$msg);
        return $result;
    }
    
    /**
     * 获取错误消息
     * @return mixed
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 空验证
     * @return bool
     */
    protected final function emptyValidate(){
        return true;
    }
    
    /**
     * 获取当前的类名称
     * @return string
     */
    public static function getClassName(){
        return __CLASS__;
    }
    
    /**
     * 通过反射，来执行验证方法
     * @param string $data  需要验证的数据
     * @param string $rules  验证规则
     * @param string $msg  验证消息
     * @return bool
     */
    public final static function checkItem($data,$rules,$msg=''){
        $reflection=new ReflectionClass(static::getClassName());
        $result=self::staticInvoke($reflection,trim($data),$rules);
        return self::getMsg($msg,$result);
    }
    
    /**
     * 获取验证方法数组
     * @param mixed $rules
     * @return array
     */
    private static function getMethods($rules){
        return is_array($rules)?$rules:explode('|', $rules);
    }
    
    /**
     * 执行验证方法
     * @param ReflectionClass $reflection  验证方法的反射类
     * @param string $data   需要验证的数据
     * @param mixed $rules   验证规则，数组或者字符串
     * @return boolean|int  验证通过返回true，不通过返回方法所在的索引
     */
    private final static function staticInvoke(ReflectionClass $reflection,$data,$rules){
        $methods=self::getMethods($rules);
        $reqIndex=array_search('required',$methods);
        $index=0;
        //是否为空
        if(!self::required($data)){  //为空
            if($reqIndex===false){   //如果验证规则中不要求字段必须，则直接返回true
                return true;
            }else{
                return $reqIndex;  //否则返回required所在的方法位置，从而返回对应的消息
            }
        }
        //遍历验证规则中的方法，逐个验证
        foreach ($methods as $rule){
            $result=self::execute($reflection,$data,$rule);
            if(!$result){
                return $index;
            }
            $index++;
        }
        return true; 
    }
    
    /**
     * 执行验证方法
     * @param ReflectionClass $reflection  验证方法的反射类
     * @param string $data   需要验证的数据
     * @param mixed $rule  验证规则，数组或者字符串
     * @return bool
     */
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
    
    /**
     * 获取消息
     * @param string $msg  验证消息
     * @param mixed $result  验证成功，则返回true，不成功返回错误消息
     * @return bool
     */
    private final static function getMsg($msg,$result){
        if($result===true){
            return true;
        }else{
            $msgs=is_array($msg)?$msg:explode('|', $msg);
            $currentMsg=empty($msgs)?'':isset($msgs[$result])?$msgs[$result]:array_pop($msgs);
            return $currentMsg;
        }
    }
    
    /**
     * 获取验证规则中的参数信息，并传换为数组
     * @param type $args
     * @return type
     */
    protected final static function getArgs($args){
        return explode(',', $args);
    }
    
    /**
     * 验证是否不能为空，空则返回false  'value'=>'required'
     * @param string $value
     * @return bool
     */
    public final static function required($value){
        return !empty($value)||'0' == $value;
    }
    
    /**
     * 验证是否为邮件格式  'value'=>'email'
     * @param string $value
     * @return bool
     */
    public final static function email($value){
        return !(filter_var($value,FILTER_VALIDATE_EMAIL)===false);
    }
    
    /**
     * 验证是否为姓名格式，包括中文，字母大写或者小写   'value'=>'name'
     * @param string $value
     * @return bool
     */
    public final static function name($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}a-zA-Z]+$#u');
    }
    
    /**
     * 验证是否为QQ号格式   'value'=>'qq'
     * @param string $value
     * @return bool
     */
    public final static function qq($value){
        return self::regex($value,'#^[1-9]\d{4,10}$#');
    }
    
    /**
     * 验证是否为电话格式   'value'=>'phone'
     * @param string $value
     * @return bool
     */
    public final static function phone($value){
        return self::regex($value,'#^13[0-9]{9}$|14[0-9]{9}|15[0-9]{9}$|18[0-9]{9}$#');
    }
    
    /**
     * 验证是否为域名格式   'value'=>'domain'
     * @param string $value
     * @return bool
     */
    public final static function domain($value){
        return self::regex($value,'#^[0-9a-zA-Z\u4e00-\u9fa5]+[0-9a-zA-Z\u4e00-\u9fa5\.-]*\.[a-zA-Z]{2,4}$#');
    }
    
    /**
     * 验证是否为身份证号格式  'value'=>'idCard'
     * @param string $value
     * @return bool
     */
    public final static function idCard($value){
        return self::regex($value,'#^(^\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$#');
    }
    
    /**
     * 验证是否只包含字母和数字  'value'=>'enAndNum'
     * @param string $value
     * @return bool
     */
    public final static function enAndNum($value){
        return self::regex($value,'#^[A-Za-z0-9]+$#');
    }
    
    /**
     * 验证是否只包含字母和数字和中文  'value'=>'enAndNum'
     * @param string $value
     * @return bool
     */
    public final static function enAndNumAndChs($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$#u');
    }
    
    /**
     * 验证是否只包含字母  'value'=>'en'
     * @param string $value
     * @return bool
     */
    public final static function en($value){
        return self::regex($value,'#^[A-Za-z]+$#');
    }
    
    /**
     * 验证是否只包含中文  'value'=>'chs'
     * @param string $value
     * @return bool
     */
    public final static function chs($value){
        return self::regex($value,'#^[\x{4e00}-\x{9fa5}]+$#u');
    }
    
    /**
     * 验证是否只包含数字  'value'=>'num'
     * @param string $value
     * @return bool
     */
    public final static function num($value){
        return self::regex($value,'#^[0-9]+$#');
    }
    
    /**
     * 验证是否为端口号  'value'=>'num'
     * @param string $value
     * @return bool
     */
    public final static function port($value){
        $port=intval($value);
        return strpos($value,'.')===false&&$port>=1&&$port<=65535;
    }
    
    /**
     * 验证是否为IP地址  'value'=>'num'
     * @param string $value
     * @return bool
     */
    public final static function ip($value){
        return !(filter_var($value,FILTER_VALIDATE_IP)===false);
    }
    
    /**
     * 验证是否为url  'value'=>'url'
     * @param string $value
     * @return bool
     */
    public final static function url($value){
        return !(filter_var($value,FILTER_VALIDATE_URL)===false);
    }
    
    /**
     * 验证是否为日期类型  'value'=>'date'
     * @param string $value
     * @return bool
     */
    public final static function date($value){
        return !(strtotime($value)===false);
    }
    
    /**
     * 验证符合指定的正则表达式  'value'=>'regex:/^\w+$/' 如果参数中带有|，则rule应该用数组表示 'value'=>array('regex:/^\w+$/')
     * @param string $value
     * @return bool
     */
    public final static function regex($value,$arg){
        return 1===preg_match($arg,(string)$value);
    }
    
    /**
     * 验证是否包含某个字符，包含2个参数，第二个参数为true时，表示不区分大小写 'value'=>'contain:al'；'value'=>'contain:al，true'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return bool
     */
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
    
    /**
     * 验证是否等于某个字符串  'value'=>'equals:value'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
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
    
    /**
     * 验证是否在某些值里面 'value'=>'in:1,2,3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function in($value,$arg){
        $arr=self::getArgs($arg);
        return in_array($value,$arr);
    }
    
    /**
     * 验证是否不在某些值里面 'value'=>'notIn:1,2,3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function notIn($value,$arg){
        $arr=self::getArgs($arg);
        return !in_array($value,$arr);
    }
    
    /**
     * 验证是否大于某个数字 'value'=>'notIn:1,2,3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function gt($value,$arg){
        return $value>$arg;
    }
    
    /**
     * 验证是否等大于等于某个数字 'value'=>'egt:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function egt($value,$arg){
        return $value>=$arg;
    }
    
    /**
     * 验证是否等大于小于某个数字 'value'=>'egt:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function lt($value,$arg){
        return $value<$arg;
    }
    
    /**
     * 验证是否等小于小于某个数字 'value'=>'elt:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function elt($value,$arg){
        return $value<=$arg;
    }
    
    /**
     * 验证是否在某个范围内 'value'=>'elt:1,3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function range($value,$arg){
        $arr=self::getArgs($arg);
        return $value>=$arr[0]&&$value<=$arr[1];
    }
    
    /**
     * 验证字符串长度是否在某个范围内，如果参数为一个，则表示验证字符串长度是否等于某个值 'length'=>'elt:1,3'；'length'=>'elt:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function length($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        if(isset($arr[1])){
            return $len>=$arr[0]&&$len<=$arr[1];
        }
        return $len==$arr[0];
    }
    
    /**
     * 验证数字最大值是否在指定数值内 'value'=>'max:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function max($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        return $len<=$arr;
    }
    
    /**
     * 验证数字最小值是否在指定数值内 'value'=>'min:3'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function min($value,$arg){
        $arr=self::getArgs($arg);
        $len=strlen($value);
        return $len>=$arr;
    }
    
    /**
     * 验证日期是否在指定日期前 'value'=>'before:2017-4-10'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function before($value,$arg){
        return strtotime($value)<=strtotime($arg);
    }
    
    /**
     * 验证日期是否在指定日期后 'value'=>'after:2017-4-10'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function after($value,$arg){
        return strtotime($value)<=strtotime($arg);
    }
    
    /**
     * 验证日期是否在指定日期范围内'value'=>'range:2017-4-10,2017-4-15'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function dateRange($value,$arg){
        $arr=self::getArgs($arg);
        $date=strtotime($value);
        return $date>=strtotime($arr[0])&&$date<=strtotime($arr[1]);
    }
    
    /**
     * 验证日期是否在指定IP范围内value'=>'ipRange:192.168.1.1,192.168.1.100'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
    public final static function ipRnage($value,$arg){
        $arr=self::getArgs($arg);
        $ip=IP::ipToInt($value);
        return $ip>=IP::ipToInt($arr[0])&&$ip<=IP::ipToInt($arr[1]);
    }
    
    /**
     * 验证日期是否在指定IP范围内value'=>'ipIn:192.168.1.0/24'
     * @param string $value
     * @param string $arg 冒号后的参数值
     * @return type
     */
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
