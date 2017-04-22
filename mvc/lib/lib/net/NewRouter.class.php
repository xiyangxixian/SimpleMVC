<?php

namespace lib\net;

class NewRouter {

    private $inputArray;
    private $calculation;
    

    public function __construct(array $ipArray) {
        $tempArray=array();
        foreach ($ipArray as $value){
            $iparr=explode('/',$value);
            $ip=$iparr[0];
            $prefix=32;
            if(isset($iparr[1])){
                $prefix=$iparr[1];
            }
            $tempArray[]=array(IP::ipToInt($ip),$prefix);
        }
        $this->inputArray=$this->removeContainer($tempArray);
        $this->setCalculation(new CalculationImpl());
    }
    
    private function removeContainer($array){
        usort($array,function($a,$b){
            if($a[0]==$b[0]){
                return $a[1]<$b[1]?-1:1;
            }else{
                return $a[0]<$b[0]?-1:1;
            }
        });
        $preIpRange=array(0,0);
        $tempArray = array();
        for($i=0,$len=count($array);$i<$len;$i++){
            $ip=$array[$i][0];
            $prefix=$array[$i][1];
            if($ip<$preIpRange[0]||$ip>=$preIpRange[1]){
                $tempArray[] = $array[$i];
                $min=$ip;
                $max=$prefix<2?$ip+pow(2,32-$prefix):$ip+(1<<(32-$prefix));
                $preIpRange=array($min,$max);
            }
        }
        return $tempArray;
    }
    
    public function setCalculation(Calculation $calculation){
        $this->calculation=$calculation;
    }
    
    public function getResult(){
        $this->calculation->setData($this->inputArray);
        $result=$this->calculation->getResult();
        return $this->formatIntToIp($result);
    }
    
    private function formatIntToIp($ipArray){
        $tempArray=array();
        foreach ($ipArray as $ip){
            if($ip[1]=='32'){
                $tempArray[]=IP::intToIp($ip[0]);
            }else{
                $tempArray[]=IP::intToIp($ip[0]). '/'.$ip[1];
            }
        }
        return $tempArray;
    }
}
