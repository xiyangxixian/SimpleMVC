<?php

namespace lib\net;

class Curl {
    
    private $ch=null;  //curl句柄
    private $options;  //curl参数项
    
    /**
     * 构造
     * @param string $url url地址，允许为空，为空时，get，post，code方法时必须传入url
     */
    public function __construct($url=''){
        $this->options=array();  //初始化参数项
        $this->saveUrl($url);
        $this->saveOption(CURLOPT_RETURNTRANSFER,true);
        $this->saveTimeout(120);
    }
    
    /**
     * 实例化curl类
     * @param string $url url地址，允许为空，为空时，get，post，code方法时必须传入url
     * @return \lib\net\Curl
     */
    public static function create($url=''){
        return new Curl($url);
    }
    
    /**
     * 初始化curl参数
     * @return \lib\net\Curl
     */
    private function initOptions(){
        $this->curlReset();
        $this->setOptions($this->options);
         return $this;
    }

    /**
     * 设置curl参数项，$key，$value详见curl参考手册
     * @param string $key
     * @param type $value
     * @return \lib\net\Curl
     */
    private function setOption($key,$value){
        curl_setopt($this->ch,$key,$value);
        return $this;
    }
    
    /**
     * 批量设置curl参数项
     * @param array $options
     * @return \lib\net\Curl
     */
    private function  setOptions(array $options){
        curl_setopt_array($this->ch,$options);
        return $this;
    }
    
    /**
     * 将curl参数项保存至options中，执行会话的时候会传入，$key，$value详见curl参考手册
     * @param type $key
     * @param type $value
     * @return \lib\net\Curl
     */
    public function saveOption($key,$value){
        $this->options[$key]=$value;
         return $this;
    }
    
    /**
     * 批量将curl参数项保存至options中，执行会话的时候会传入
     * @param type $key
     * @param type $value
     * @return \lib\net\Curl
     */
    public function saveOptions(array $options){
        foreach ($options as $key=>$value){
            $this->saveOption($key,$value);
        }
         return $this;
    }
    
    /**
     * 将curl参数项保存至options中，执行会话的时候会传入
     * @param type $key
     * @param type $value
     * @return \lib\net\Curl
     */
    public function getOptons(){
        return $this->options;
    }
    
    /**
     * 设置超时时间
     * @param int $second 秒
     * @return \lib\net\Curl
     */
    public function saveTimeout($second){
        $this->saveOption(CURLOPT_TIMEOUT, $second);
         return $this;
    }

    /**
     * 将curl头参数保存至options数组中，执行回话时会传入
     * @param array $header 超时的描述
     * @return \lib\net\Curl
     */
    public function saveHeader(array $header){
        $this->saveOption(CURLOPT_HTTPHEADER,$header);
        return $this;
    }
    
    /**
     * 将curl url参数保存至options数组中，执行回话时会传入
     * @param string $url url地址
     * @return \lib\net\Curl
     */
    public function saveUrl($url){
        if(strpos($url,'https://')!==false){
            $this->saveOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->saveOption(CURLOPT_SSL_VERIFYHOST, 0);
        }
        $this->saveOption(CURLOPT_URL,$url); 
         return $this;
    }
    
    /**
     * 设置超时时间
     * @param int $second 秒
     * @return \lib\net\Curl
     */
    private function setTimeout($second){
        $this->setOption(CURLOPT_TIMEOUT, $second);
         return $this;
    }

    /**
     * 设置头信息
     * @param array $header
     * @return \lib\net\Curl
     */
    private function setHeader(array $header){
        $this->setOption(CURLOPT_HTTPHEADER,$header);
        return $this;
    }
    
    /**
     * 设置url信息
     * @param array $header
     * @return \lib\net\Curl
     */
    private function setUrl($url){
        if($url!=null){
            if(strpos($url,'https://')!==false){
                $this->setOption(CURLOPT_SSL_VERIFYPEER, false);
                $this->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            }
            $this->setOption(CURLOPT_URL,$url); 
        }
         return $this;
    }

    /**
     * 获取url句柄
     * @return curl句柄
     */
    public function getHandle(){
        $this->initOptions();
        return $this->ch;
    }

    /**
     * 拷贝一个curl句柄
     * @return curl句柄
     */
    public function copyHandle(){
        $this->initOptions();
        return curl_copy_handle($this->ch);
    }
    
    /**
     * 重置curl句柄
     * @return \lib\net\Curl
     */
    private function curlReset(){
        $this->close();
        $this->ch=curl_init();
         return $this;
    }

    /**
     * 重置Curl类的全部参数项
     */
    public function reset(){
        $this->options=array();
        $this->curlReset();
    }
    
    /**
     * 设置url与头信息
     * @param string $url url地址
     * @param array $header curl头信息
     * @return \lib\net\Curl
     */
    private function excuseOption($url,$header){
        $this->initOptions();
        $this->setUrl($url);
        if($header!==null){
            $this->setHeader($header); 
        }
         return $this;
    }

    /**
     * 执行一个get请求
     * @param mixed $urlORheader 如果此参数为url则两个参数分别为：url，header，如果不为url，则视为设置curl头信息，参数仅为1个
     * @param array $header
     * @return type
     */
    public function get($urlORheader=null,array $header=array()){
        if(strpos($urlORheader,'http')===false){
            $this->excuseOption(null,$urlORheader);
        }else{
            $this->excuseOption($urlORheader,$header);
        }
        $this->setOption(CURLOPT_HTTPGET,true);
        return $this->excuse();
    }
    
    /**
     * 执行一个post请求 如果第一个参数为url则三个个参数分别为：url，param，header，如果不为url，则视为2个参数，分别为param，header
     * @param mixed $urlORparam 
     * @param mixed $paramORheader
     * @param array $header
     * @return mixed 成功返回string，失败返回false
     */
    public function post($urlORparam=null,$paramORheader=null,array $header=array()){
        if(strpos($urlORparam,'http')===false){
            $this->excuseOption(null,$paramORheader);
            $this->setOption(CURLOPT_POSTFIELDS,$urlORparam);
        }else{
            $this->excuseOption($urlORparam,$header);
            $this->setOption(CURLOPT_POSTFIELDS,$paramORheader);
        }
        $this->setOption(CURLOPT_POST,true);
        return $this->excuse();
    }
    
    /**
     * 获取状态码信息 如果第一个参数为url则三个个参数分别为：url，param，header，如果不为url，则视为2个参数，分别为param，header
     * @param mixed $urlORparam 
     * @param mixed $paramORheader
     * @param array $header
     * @return mixed 成功返回string，失败返回false
     */
    public function code($urlORparam=null,$paramORheader=null,array $header=array()){
        if(strpos($urlORparam,'http')===false){
            $this->excuseOption(null,$paramORheader);
            $this->setOption(CURLOPT_POSTFIELDS,$urlORparam);
        }else{
            $this->excuseOption($urlORparam,$header);
            $this->setOption(CURLOPT_POSTFIELDS,$paramORheader);
        }
        $this->setOption(CURLOPT_HEADER,true);
        $this->setOption(CURLOPT_NOBODY,true);
        $this->excuse();
        return $this->getInfo(CURLINFO_HTTP_CODE);
    }
    
    /**
     * 执行一个回合
     * @return miexed 成功返回string，失败返回false
     */
    private function excuse(){
        return curl_exec($this->ch);
    }
    
    /**
     * 获取回话信息
     * @param string $key
     * @return mixed
     */
    private function getInfo($key){
        return curl_getinfo($this->ch,$key);
    }

    /**
     * 关闭一个会话
     */
    public function close(){
        if($this->ch!=null){
            curl_close($this->ch);
        }
    }
    
    public function __destruct(){
        if($this->ch!=null){
            curl_close($this->ch);
        }
    }
            
}
