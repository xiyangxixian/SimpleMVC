<?php

namespace lib\net;

class Router {

    private $flag = 1;
    private $oldArray;
    private $outputArray = array();
    private $quotient= -1;
    public static $MODE_NUM=1;
    public static $MODE_IP=2;
    public static $MODE_NET=3;
    
    public static $MODE_ARRAY=4;
    

    public function __construct($ipArray,$mode=0) {
        $ipArray=array_unique($ipArray);
        switch ($mode){
            case self::$MODE_NUM:
                sort($ipArray);
                $this->oldArray = $ipArray;
                break;
            case self::$MODE_IP:
                $this->oldArray=$this->formatIpToInt($ipArray);
                break;
            case self::$MODE_NET:
                $this->oldArray = $ipArray;
                break;
            default:
                $item=$ipArray[0];
                if(is_string($item)&&!strstr($item,'/')){
                    $this->oldArray=$this->formatIpToInt($ipArray);
                }else{
                    $this->oldArray = $ipArray;
                }
                if(!is_string($this->oldArray[0])){
                    sort($this->oldArray);
                }
        }
    }
    
    private function formatIpToInt($ipArray){
        $tempArray=array();
        foreach ($ipArray as $ip){
            $tempArray[]=IP::ipToInt($ip);
        }
        sort($tempArray);
        return $tempArray;
    }
    
    private function formatIntToIp($ipArray){
        $tempArray=array();
        foreach ($ipArray as $ip){
            $tempArray[]=IP::intToIp($ip[0]). '/'.$ip[1];
        }
        return $tempArray;
    }
    
    private function formatResult($ipArray){
        $tempArray=array();
        foreach ($ipArray as $ip){
            if($ip[1]==32){
                $tempArray[]=IP::intToIp($ip[0]);
            }else{
                $tempArray[]=IP::intToIp($ip[0]). '/'.$ip[1];
            }
        }
        return $tempArray;
    }


    public function setFlag($num){
        $this->flag=33-$num;
    }

    private function yeid(){
        $tempArray=$this->getTempArray();
        if (!empty($tempArray)) {
            $this->oldArray =$tempArray;
            $this->flag++;
            $this->quotient = -1;
            $this->yeid();
        }
    }
    
    private function getTempArray(){
        $tempArray = array();
        for ($i = 0, $len = count($this->oldArray); $i < $len; $i++) {
            $quotient=$this->oldArray[$i]>PHP_INT_MAX?floor($this->oldArray[$i]/pow(2,$this->flag)):$this->oldArray[$i]>>$this->flag;
            if ($quotient == $this->quotient) {
                array_pop($this->outputArray);
                $tempArray[] = $this->oldArray[$i - 1];
            } else {
                $this->quotient = $quotient;
                $netmask = 33 - $this->flag;
                $this->outputArray[]=array($this->oldArray[$i],$netmask);
            }
        }
        return $tempArray;
    }

    public function getResult($mode=0){
        $this->yeid();
        if($mode==self::$MODE_ARRAY){
            return $this->outputArray;
        }
        return $this->formatIntToIp($this->outputArray);
    }
    
    public function getResultByNetwork(){
        $this->yeidPrefix(true);
        return $this->formatIntToIp($this->outputArray);
    }
    
    public function getResultDefault(){
        $this->yeidPrefix(true);
        return $this->formatResult($this->outputArray);
    }

    private $allArray;
    private $ipPrefixArray;
    private $prefixQuotient;
    private $preIpRange;
    
    private function initArray(){
        $this->allArray=array();
        $this->ipPrefixArray=array();
        $this->prefixQuotient=-1;
        $this->preIpRange=array(0,0);
    }
    
    private function yeidPrefix($isRemoveContainer=true){
        $this->initArray();
        $this->formatArray($isRemoveContainer);
        if($isRemoveContainer){
            $this->removeContainer();
        }
        $this->sortIPbyPrefix();
        $tempArray=array();
        foreach ($this->ipPrefixArray as $key=>$itemArray){
            $router=new Router($itemArray,self::$MODE_NUM);
            $router->setFlag($key);
            $resultArray=$router->getResult(self::$MODE_ARRAY);
            $tempArray=array_merge($tempArray,$resultArray);
        }
        if(count($tempArray)==count($this->allArray)){
            $this->outputArray=$tempArray;
        }else{
            $this->oldArray=$tempArray;
            $this->yeidPrefix(false);
        }
    }
    
    private function formatArray($isFormat=true){
        if($isFormat){
            foreach ($this->oldArray as $value){
                $ipTotle=explode('/', $value);
                if(!isset($ipTotle[1])){
                    $ipTotle[1]=32;
                }
                $ip=$ipTotle[0];
                $prefix=$ipTotle[1];
                $this->allArray[]=array(IP::ipToInt($ip),$prefix);     
            }
        }else{
            $this->allArray=$this->oldArray;
        }
        usort($this->allArray,function($a,$b){
            if($a[0]==$b[0]){
                return $a[1]<$b[1]?-1:1;
            }else{
                return $a[0]<$b[0]?-1:1;
            }
        });
    }


    private function removeContainer(){       
        $this->allArray=$this->getWithPrefixArray();
    }
    
    private function sortIPbyPrefix(){
        foreach ($this->allArray as $ipWithPrefix){
            $prefix=$ipWithPrefix[1];
            if(!isset($this->ipPrefixArray[$prefix])){
                $this->ipPrefixArray[$prefix]=array();
            }
            $this->ipPrefixArray[$prefix][]=$ipWithPrefix[0];
        }
    }
    
    private function getWithPrefixArray(){
        $tempArray = array();
        for($i=0,$len=count($this->allArray);$i<$len;$i++){
            $ip=$this->allArray[$i][0];
            $prefix=$this->allArray[$i][1];
            if($ip<$this->preIpRange[0]||$ip>=$this->preIpRange[1]){
                $tempArray[] = $this->allArray[$i];
                $min=$ip;
                $max=$prefix<2?$ip+pow(2,32-$prefix):$ip+(1<<(32-$prefix));
                $this->preIpRange=array($min,$max);
            }
        }
        return $tempArray;
    }
}
