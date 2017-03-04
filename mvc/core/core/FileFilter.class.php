<?php

namespace core;
use core\UploadFile;

class FileFilter{
    
    private $maxSize;
    private $allowType;
    private $file;
    
    public function __construct(){
        $this->file=$_FILES;
        $this->allowType=$this->setType();
        $this->maxSize=$this->setSize();
    }

    public function file($key){
        if(!isset($this->file[$key])){
            return null;
        }
        return $this->doFilter($this->file[$key]);
    }
    
    public function files($key){
        if(!isset($this->file[$key])){
            $this->file[$key]=array();
            $len=0;
        }else{
            $file=$this->file[$key];
            $len=count($file['name']);
        }
        $out=array();
        for($i=0;$i<$len;$i++){
            $out[]=$this->doFilter(array(
                'name'=>$file['name'][$i],
                'type'=>$file['type'][$i],
                'tmp_name'=>$file['tmp_name'][$i],
                'size'=>$file['size'][$i],
                'error'=>$file['error'][$i]
            ));
        }
        return $out;
    }
    
    protected function setType(){
        return config('UPLOAD_FILE','TYPE');
    }
    
    protected function setSize(){
        return config('UPLOAD_FILE','MAX_SIZE');
    }

    public function changeType(array $type) {
        $this->allowType=$type;
        return $this;
    }
    
    public function addType($type) {
        if(is_array($type)){
           $this->allowType=array_merge($this->allowType,$type);
        }else{
            $this->allowType[]=$type;
        }
        return $this;
    }
    
    public function removeType($type) {
        if(is_array($type)){
           $this->allowType=array_diff($this->allowType,$type);
        }else{
            unset($this->allowType[array_search($type,$this->allowType)]);
        }
        return $this;
    }

    public function maxSize($size) {
        $this->maxSize=$size;
        return $this;
    }
    
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
