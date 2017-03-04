<?php

namespace lib\net;

class IP{
    
    public static function ipToInt($ipaddr){
        $iparray=explode('.', $ipaddr);
        $n1=(int)$iparray[0];
        if($n1>127){
            return (((int)$iparray[3]) | ((int)$iparray[2]<<8) | ((int)$iparray[1]<<16)) + $n1*16777216;
        }else{
            return  ((int)$iparray[3]) | ((int)$iparray[2]<<8) | ((int)$iparray[1]<<16) | $n1<<24;
       }
    }

    public static function intToIp($intip){
        if($intip>PHP_INT_MAX){
            $first = floor($intip/(1<<24));
            $rest = (int)($intip-($first*(1<<24)));
        }else{
            $first=$intip>>24;
            $rest=$intip^($first<<24);
        }
        $second = $rest>>16;
        $rest ^= $second<<16;
        $third = $rest>>8;
        $fourth = $rest ^ ($third<<8); 
        return "$first.$second.$third.$fourth";
    }
    
}


