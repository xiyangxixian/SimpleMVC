<?php

namespace lib\io;

class File {
    
    private $path;
    public static $WRITE_MODE_DEFAULT=FILE_USE_INCLUDE_PATH;
    public static $WRITE_MODE_APPEND=FILE_APPEND;
    public static $WRITE_MODE_LOCK=LOCK_EX;
    
    public function __construct($path) {
        $this->path=$path;
    }
    
    public function getPath(){
        return $this->path;
    }
    
    public function copyToFile($path){
        copy($this->path,$path);
    }
    
    public function moveToFile($path){
        move_uploaded_file($this->path,$path);
        $this->path=$path;
    }
    
    public function delete(){
        return unlink($this->path);
    }
    
    public function isExits(){
        return file_exists($this->path);
    }
    
    public function readAll(){
        if(!$this->isExits()){
            return '';
        }
        $data=file_get_contents($this->path);
        return self::charset($data);
    }
    
    public function writeAll($data,$mode=null){
        $str=self::charset($data);
        if($mode==null){
            file_put_contents($this->path,$str);
        }else{
            file_put_contents($this->path,$str,$mode);
        }
        
    }
    
    public function getSize(){
        if(!$this->isExits()){
            return 0;
        }
        return filesize($this->path);
    }
    
    public function rename($newname){
        rename($this->path, $newname);
        $this->path=$newname;
    }

    public function isDir(){
        return is_dir($this->path);
    }
    
    public function isFile(){
        return is_file($this->path);
    }
    
    public function isReadable(){
        return is_readable($this->path);
    }
    
    public function isWriteable(){
        return is_writeable($this->path);
    }
    
    public function mkdir($mode=null){
        if(is_null($mode)){
            return mkdir($this->path);
        }else{
            return mkdir($this->path,$mode);
        }
    }
    
    public function rmDir(){
        return rmdir($this->path);
    }
    
    public static function charset($str){
        if( !empty($str) ){
            $fileType = mb_detect_encoding($str,array('UTF-8','GBK','LATIN1','BIG5','GB2312')) ;
            if( $fileType != 'UTF-8'){
              $str = mb_convert_encoding($str ,'utf-8',$fileType);
            }
          }
          return $str;
    }
    
}
