<?php

namespace lib\net;

class CalculationImpl implements Calculation{
    
    private $inputArray;
    private $allArray;
    private $outputArray;
    
    public function setData(array $array){
        $this->inputArray=$array;
        $this->outputArray=array();
    }
    
    public function getResult(){
        $this->sortIPbyPrefix();
        $this->recursion();
        return $this->outputArray;
    }
    
    private function sortIPbyPrefix(){
        foreach ($this->inputArray as $ip){
            $prefix=$ip[1];
            if(!isset($this->allArray[$prefix])){
                $this->allArray[$prefix]=array();
            }
            $this->allArray[$prefix][]=$ip[0];
        }
    }
    
    public function recursion(){
        for($i=32;$i>=0;$i--){
            if(isset($this->allArray[$i])&&!empty($this->allArray[$i])){
                $array=$this->allArray[$i];
                sort($array);
                $result=$this->getTempArray($array,$i);
                if($i==0){
                    break; 
                }
                if(!isset($this->allArray[$i-1])){
                    $this->allArray[$i-1]=array();
                }
                $this->allArray[$i-1]=array_merge($this->allArray[$i-1],$result);
            }
        }
    }
    
    private function getTempArray($oldArray,$prefix){
        $tempArray = array();
        $index=33-$prefix;
        $oldQuotient=-1;
        for ($i = 0, $len = count($oldArray); $i < $len; $i++) {
            $quotient=$oldArray[$i]>PHP_INT_MAX?floor($oldArray[$i]/pow(2,$index)):$oldArray[$i]>>$index;
            if ($quotient == $oldQuotient) {
                array_pop($this->outputArray);
                $tempArray[] = $oldArray[$i - 1];
            } else {
                $oldQuotient=$quotient;
                $this->outputArray[]=[$oldArray[$i],$prefix];
            }
        }
        return $tempArray;
    }
    
}
