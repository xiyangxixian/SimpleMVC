<?php

namespace core;
use SplFileObject;

class UploadFile extends SplFileObject{
    
    private $name;  //文件名称
    private $type;  //文件类型
    private $tempName;  //临时文件名称
    private $size;   //文件大小
    private $suffix;  //文件后缀
    private $rule=null;  //
    
    /**
     * 文件变量
     * @param array $file
     */
    public function __construct(array $file) {
        parent::__construct($file['tmp_name'],'r+',false,null);
        $this->name=$file['name'];
        $this->type=$file['type'];
        $this->tempName=$file['tmp_name'];
        $this->size=$file['size'];
        $this->suffix=strrchr($this->name,'.');
    }
    
    /**
     * 获取上传时文件的名字
     * @return string
     */
    public function name(){
        return $this->name;
    }
    
    /**
     * 获取上传时文件的类型
     * @return string
     */
    public function type(){
        return $this->type;
    }
    
    /**
     * 获取上传后临时文件的名字
     * @return string
     */
    public function tempName(){
        return $this->tempName;
    }
    
    /**
     * 文件的大小
     * @return string
     */
    public function size(){
        return $this->size;
    }
    
    /**
     * 文件的后缀
     * @return string
     */
    public function suffix(){
        return $this->suffix;
    }
    
    /**
     * 保存文件时的命名规则
     * @return string
     */
    public function rule($rule){
        $this->rule=$rule;
    }

    /**
     * 保存文件
     * @param string $name
     * @return string  返回保存后的文件路径
     */
    public function save($name=null){
        $path=config('UPLOAD_FILE','PATH').'/';
        //如果名字已/结尾，则视为目录
        if(preg_match('#/$#',$name)){
            $path=$path.$name;
            $name=null;
        }else if(($index=strrpos($name,'/')!==false)){  //如果存在/则表示为  目录/文件名（手动命名）
            $path=$path.substr($name,0,$index+1);
            $name=substr($name,$index+1);
        }
        //自动命名
        if($name==null){
            $rule=$this->rule==null?config('UPLOAD_FILE','NAME_RULE'):$this->rule;
            //md5命名规则
            if($rule=='md5'){
                $name=md5_file($this->tempName);
            }else{  //默认为时间命名规则
                $name=microtime(true)*10000;
            }
        }
        if(!is_dir($path)){
            mkdir($path,0777,true);
            chmod($path,0777);
        }
        $name=$path.$name.$this->suffix;
        //保存文件，并返回路径
        move_uploaded_file($this->tempName,$name);
        return $name;
    }
    
}
