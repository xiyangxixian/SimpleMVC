<?php

namespace lib\net;

class Curl {
    
    private $ch;
    private $options;
    
    public function __construct($url=''){
        $this->ch=curl_init();
        $this->options=array();
        $this->saveUrl($url);
        $this->saveOption(CURLOPT_RETURNTRANSFER,true);
        $this->saveTimeout(120);
    }
    
    public static function create($url=''){
        return new Curl($url);
    }
    
    private function initOptions(){
        $this->curlReset();
        $this->setOptions($this->options);
         return $this;
    }

    private function setOption($key,$value){
        curl_setopt($this->ch,$key,$value);
        return $this;
    }
    
    private function  setOptions(array $options){
        curl_setopt_array($this->ch,$options);
        return $this;
    }
    
    public function saveOption($key,$value){
        $this->options[$key]=$value;
         return $this;
    }
    
    public function saveOptions(array $options){
        foreach ($options as $key=>$value){
            $this->saveOption($key,$value);
        }
         return $this;
    }
    
    public function getOptons(){
        return $this->options;
    }
    
    public function saveTimeout($second){
        $this->saveOption(CURLOPT_TIMEOUT, $second);
         return $this;
    }

    public function saveHeader(array $header){
        $this->saveOption(CURLOPT_HTTPHEADER,$header);
        return $this;
    }
    
    public function saveUrl($url){
        $this->saveOption(CURLOPT_URL,$url); 
         return $this;
    }
    
    private function setTimeout($second){
        $this->setOption(CURLOPT_TIMEOUT, $second);
         return $this;
    }

    private function setHeader(array $header){
        $this->setOption(CURLOPT_HTTPHEADER,$header);
        return $this;
    }
    
    private function setUrl($url){
        if($url!=null){
            $this->setOption(CURLOPT_URL,$url); 
        }
         return $this;
    }

    public function getHandle(){
        $this->initOptions();
        return $this->ch;
    }

    public function copyHandle(){
        $this->initOptions();
        return curl_copy_handle($this->ch);
    }
    
    private function curlReset(){
        curl_reset($this->ch);
         return $this;
    }

    public function reset(){
        $this->options=array();
        $this->curlReset();
    }
    
    private function excuseOption($url,$header){
        $this->initOptions();
        $this->setUrl($url);
        $this->setHeader($header);
         return $this;
    }

    public function get($url=null,array $header=array()){
        $this->excuseOption($url,$header);
        $this->setOption(CURLOPT_HTTPGET,true);
        return $this->excuse();
    }
    
    public function post($url=null,array $param=array(),array $header=array()){
        $this->setOption(CURLOPT_POST,true);
        if(is_array($url)){
            $this->excuseOption(null,$header);
            $this->setOption(CURLOPT_POSTFIELDS,$url);
        }else{
            $this->excuseOption($url,$header);
            $this->setOption(CURLOPT_POSTFIELDS,$param);
        }
        return $this->excuse();
    }
    
    public function code($url=null,array $header=array()){
        $this->excuseOption($url,$header);
        $this->setOption(CURLOPT_HEADER,true);
        $this->setOption(CURLOPT_NOBODY,true);
        $this->excuse();
        return $this->getInfo(CURLINFO_HTTP_CODE);
    }
    
    public function excuse(){
        return curl_exec($this->ch);
    }
    
    public function getInfo($key){
        return curl_getinfo($this->ch,$key);
    }

    public function close(){
        curl_close($this->ch);
    }
    
    public function __destruct(){
        curl_close($this->ch);
    }
            
}
