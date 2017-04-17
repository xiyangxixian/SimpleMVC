<?php

namespace core;
use core\UploadFile;

class FileFilter{
    
    private $maxSize;  //文件上传的大小限制
    private $allowType;  //文件上传的类型限制
    private $file;  //上传的文件变量
    
    public function __construct(){
        $this->file=$_FILES;
        $this->allowType=$this->getType();
        $this->maxSize=$this->getSize();
    }

    /**
     * 单文件上传获取
     * @param string $key  //文件的key
     * @return UploadFile
     */
    public function file($key){
        if(!isset($this->file[$key])){
            return null;
        }
        return $this->doFilter($this->file[$key]);
    }
    
    /**
     * 多文件上传获取
     * @param string $key
     * @param bool $all true表示全部文件有一个不通过则返回null，false则仍返回数组
     * @return array UploadFile数组
     */
    public function files($key,$all=true){
        if(!isset($this->file[$key])){
            $this->file[$key]=array();
            $len=0;
        }else{
            $file=$this->file[$key];
            $len=count($file['name']);
        }
        $out=array();
        for($i=0;$i<$len;$i++){
            $file=$this->doFilter(array(
                'name'=>$file['name'][$i],
                'type'=>$file['type'][$i],
                'tmp_name'=>$file['tmp_name'][$i],
                'size'=>$file['size'][$i],
                'error'=>$file['error'][$i]
            ));
            if($file!=null){
                $out[]=$file;
            }else if($all){
                return null;
            }
        }
        return $out;
    }
    
    /**
     * 获取文件类型配置参数
     * @return array
     */
    protected function getType(){
        return config('UPLOAD_FILE','TYPE');
    }
    
    /**
     * 获取文件上传
     * @return int
     */
    protected function getSize(){
        return config('UPLOAD_FILE','MAX_SIZE');
    }

    /**
     * 改变类型限制配置
     * @param array $type
     * @return \core\FileFilter
     */
    public function changeType(array $type) {
        $this->allowType=$type;
        return $this;
    }
    
    /**
     * 增加类型限制
     * @param mixed $type
     * @return \core\FileFilter
     */
    public function addType($type) {
        if(is_array($type)){
           $this->allowType=array_merge($this->allowType,$type);
        }else{
            $this->allowType[]=$type;
        }
        return $this;
    }
    
    /**
     * 移除类型限制
     * @param mixed $type
     * @return \core\FileFilter
     */
    public function removeType($type) {
        if(is_array($type)){
           $this->allowType=array_diff($this->allowType,$type);
        }else{
            unset($this->allowType[array_search($type,$this->allowType)]);
        }
        return $this;
    }

    /**
     * 改变文件大小限制
     * @param int $size
     * @return \core\FileFilter
     */
    public function maxSize($size) {
        $this->maxSize=$size;
        return $this;
    }
    
    /**
     * 对文件进行过滤
     * @param array 文件变量
     * @return UploadFile
     */
    private function doFilter(array $file){
        if($file['error']>0){
            return null;
        }
        $suffix=strrchr($file['name'],'.');
        if(!in_array($suffix,$this->allowType)){
            return null;
        }
        if($file['size']>$this->maxSize){
            return null;
        }
        return new UploadFile($file);
    }

}
