<?php

namespace core;
use SplFileObject;

class UploadFile extends SplFileObject{
    
    private $name;
    private $type;
    private $tempName;
    private $size;
    private $suffix;
    private $rule=null;

    public function __construct(array $file) {
        parent::__construct($file['tmp_name'],'r+',false,null);
        $this->name=$file['name'];
        $this->type=$file['type'];
        $this->tempName=$file['tmp_name'];
        $this->size=$file['size'];
        $this->suffix=strrchr($this->name,'.');
    }
    
    public function name(){
        return $this->name;
    }
    
    public function type(){
        return $this->type;
    }
    
    public function tempName(){
        return $this->tempName;
    }
    
    public function size(){
        return $this->size;
    }
    
    public function suffix(){
        return $this->suffix;
    }
    
    public function rule($rule){
        $this->rule=$rule;
    }

    public function save($name=null){
        $path=config('UPLOAD_FILE','PATH').'/';
        if(preg_match('#/$#',$name)){
            $path=$path.$name;
            $name=null;
        }else if(($index=strrpos($name,'/'))){
            $path=$path.substr($name,0,$index+1);
            $name=substr($name,$index+1);
        }
        if($name==null){
            $rule=$this->rule==null?config('UPLOAD_FILE','NAME_RULE'):$this->rule;
            if($rule=='md5'){
                $name=md5_file($this->tempName);
            }else{
                $name=microtime(true)*10000;
            }
        }
        if(!is_dir($path)){
            mkdir($path,0777,true);
            chmod($path,0777);
        }
        $name=$path.$name.$this->suffix;
        move_uploaded_file($this->tempName,$name);
        return $name;
    }
    
}
