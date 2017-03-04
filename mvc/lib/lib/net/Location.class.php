<?php

namespace lib\net;

class Location {
    
    private static $fp=null;
    private static $startoffest;
    private static $endoffest;
    
    public static function load($file){
        self::$fp=fopen($file,'r');
        self::$startoffest=self::getLong(0);
        self::$endoffest=self::getLong(4);
    }
    
    private static function getLong($offest){
        fseek(self::$fp,$offest);
        $result = unpack('Nlong', fread(self::$fp, 4));
        return $result['long'];
    }
    
    public static function find($ip){
        if(self::$fp==null){
            self::load(__DIR__.'/data/ip.dat');
        }
        $str=self::getstring(self::getRecordOffest(intval(ip2long($ip))));
        return explode("\t",$str);
    }
    
    private static function getstring($offest) {
        fseek(self::$fp,$offest);
        $char = fread(self::$fp, 1);
        $data='';
        while (ord($char) > 0) {        
            $data.=$char;             
            $char=fread(self::$fp,1);
        }
        return $data;
    }
    
    private static function lt($a,$b){
        if(($a^$b)<0){
            return $a>0;
        }else{
            return $a<$b;
        }
    }
    
    private static function getRecordOffest($ipint){
        return self::getOffest($ipint,self::$startoffest,self::$endoffest);
    }
    
    private static function getOffest($ipint,$start,$end){
        $tmpstart=($start-self::$startoffest)/8;
        $tmpend=($end-self::$startoffest)/8;
        $m=floor(($tmpstart+$tmpend)/2);
        $fileipint=self::getLong($m*8+self::$startoffest);
        if(self::lt($ipint,$fileipint)){
            $nextend=$m*8+self::$startoffest;
            return self::getOffest($ipint,$start,$nextend);
        }else{
            $nextstart=($m+1)*8+self::$startoffest;
            if(self::lt($ipint,self::getLong($nextstart))){
                return self::getLong($nextstart-4);
            }
            return self::getOffest($ipint,$nextstart,$end);
        }
    }
    
    public static function close(){
        fclose(self::$fp);
    }
    
}
