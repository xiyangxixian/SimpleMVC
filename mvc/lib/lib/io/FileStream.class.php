<?php

namespace lib\io;
use lib\io\File;

class FileStream {
    
    public $fileStream;
    public $file;
    
    public function __construct($file,$mode='r+'){
        $this->Open($file,$mode);
    }
    
    private function Open($file,$mode){
        if($file instanceof File){
            $this->fileStream=fopen($file->getPath());
            $this->file=$file;
        }else{
            $this->fileStream=fopen($file,$mode);
            $this->file=new File($file);
        }
    }
    
    public function isEnd(){
        return feof($this->fileStream);
    }
    
    public function read($length=null){
        if($length==null){
            $length=$this->file->getSize();
        }
        $data=fread($this->fileStream, $length);
        return File::charset($data);
    }
    
    public function readLine($length=null){
        if($length==null){
            $data=fgets($this->fileStream);
        }else{
            $data=fgets($this->fileStream,$length);
        }
        return File::charset($data);
    }
    
    public function write($data,$length=null){
        $str=File::charset($data);
        if($length==null){
            fwrite($this->fileStream,$str);
        }else{
            fwrite($this->fileStream,$str,$length);
        }
    }
    
    public function flush(){
        fflush($this->fileStream);
    }

    public function close(){
        fclose($this->fileStream);
    }
    
}
