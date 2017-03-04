<?php

namespace lib\net;

class BGP {
    
    public static $ROUTER_IP='192.168.1.1';
    public static $PORT=179;
    private $conn;
    private $socket;
    
    public static $MARK=0xffffffffffffffffffffffffffffffff;
    public static $OPEN_MSG=0x01;
    public static $UPDATE_MSG=0x02;
    public static $NOTIFICATION_MSG=0x03;
    public static $KEEPALIVE_MSG=0x04;
    
    public static $VERSION=0x04;//BGP版本4；
    public static $AS=200;//AS号为1000；
    public static $HOLD_TIME=0x00b4;//hold time
    public static $BGP_ID='192.168.1.30';
    public static $NEXT_HOP='192.168.1.20';
    
    public function __construct($localIP,$AS,$remoteIP,$nextHop) {
        self::$BGP_ID=$localIP;
        self::$AS=$AS;
        self::$ROUTER_IP=$remoteIP;
        self::$NEXT_HOP=$nextHop;
        $this->initSocket();
    }
    
    public function initSocket(){
        $this->socket=socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->conn=socket_connect($this->socket,  self::$ROUTER_IP,self::$PORT);
        if(!$this->conn){
            echo "网络连接错误\n";
            return;
        }
    }
    
    public function open(){
        $idArray=$this->getIPArray(self::$BGP_ID);
        $asArray=$this->getBits(self::$AS);
        $data=pack("C29",0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,//标识16字节
                0,29,self::$OPEN_MSG,//数据包长度2字节and消息类型1字节
                self::$VERSION,//BGP版本号1字节
                $asArray[0],$asArray[1],//AS号2字节
                0x00,0xb4,//生存时间
                $idArray[0],$idArray[1],$idArray[2],$idArray[3],//BGP ID4字节
                0//可选参数长度1字节
                );
        return socket_write($this->socket, $data);
    }
    public function keepALive(){
        $data=pack("C19",0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,
                0,19,self::$KEEPALIVE_MSG//数据包长度2字节and消息类型1字节
                );
        return socket_write($this->socket, $data);
    }
    
    public function addRouter($ipaddr){
        $ipArray=$this->getIPArray($ipaddr);
//        $asArray=$this->getBits(self::$AS);
        $nextHop=$this->getIPArray(self::$NEXT_HOP);
        $data=pack("C56",0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,
                0,60,self::$UPDATE_MSG,//数据包长度2字节and消息类型1字节
                0,0,//撤回路由消息长度2字节
                0,32,//路由属性2字节
                0x40,0x01,0x01,0x00,//ORIGIN：IGP 4字节
                0x40,0x02,0x00,
                //0x40,0x02,0x04,0x02,0x01,$asArray[0],$asArray[1],//AS_PATH 4字节
                0x40,0x03,0x04,$nextHop[0],$nextHop[1],$nextHop[2],$nextHop[3],//下一条地址 7字节
                0x80,0x04,0x04,0,0,0,0,//MED 7字节
                0x40,0x05,0x04,0,0,0,100,//Local_Pref 100
                32,//IP地址子网长度
                $ipArray[0],$ipArray[1],$ipArray[2],$ipArray[3]//IP地址
            );
        return socket_write($this->socket, $data);
    }
    
    public function addAllRouter(array $ipaddr){
        $temparr=[];
        $i=0;
        foreach($ipaddr as $value){
            $temparr[]=$value;
            $i++;
            if($i==500){
                $this->addList($temparr);
                $temparr=[];
                $i=0;
            }
        }
        if(!empty($temparr)){
            return $this->addList($temparr);
        }
        return true;
    }


    private function addList(array $ipaddr){
//        $asArray=$this->getBits(self::$AS);
        $nextHop=$this->getIPArray(self::$NEXT_HOP);
        
        $router=[];
        foreach ($ipaddr as $value){
            $iparr=explode('/',$value);
            if(!isset($iparr[1])){
                $iparr[1]=32;
            }
            $router[]=$iparr[1];
            $bitLen=ceil($iparr[1]/8);
            $bitarr=$this->getIPArray($iparr[0]);
            for($i=0;$i<$bitLen;$i++){
                $router[]=$bitarr[$i];
            }
        }
        $length=51+count($router);
        $lengtharr=$this->getBits($length);
        
        $data=pack("C".$length,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,
                $lengtharr[0],$lengtharr[1],self::$UPDATE_MSG,//数据包长度2字节and消息类型1字节
                0,0,//撤回路由消息长度2字节
                0,28,//路由属性2字节
                0x40,0x01,0x01,0x00,//ORIGIN：IGP 4字节
                0x40,0x02,0x00,
                //0x40,0x02,0x04,0x02,0x01,$asArray[0],$asArray[1],//AS_PATH 4字节
                0x40,0x03,0x04,$nextHop[0],$nextHop[1],$nextHop[2],$nextHop[3],//下一条地址 7字节
                0x80,0x04,0x04,0,0,0,20,//MED 7字节
                0x40,0x05,0x04,0,0,0,100,//Local_Pref 100
                ...$router
            );
        return socket_write($this->socket, $data);
    }

    
    public function deleteAllRouter(array $ipaddr){
        $temparr=[];
        $i=0;
        foreach($ipaddr as $value){
            $temparr[]=$value;
            $i++;
            if($i==500){
                $this->deleteList($temparr);
                $temparr=[];
                $i=0;
            }
        }
        if(!empty($temparr)){
            return $this->deleteList($temparr);
        }
        return true;
    }

    
    public function deleteList($ipaddr){
//        $ipArray=$this->getIPArray($ipaddr);
        $router=[];
        foreach ($ipaddr as $value){
            $iparr=explode('/',$value);
            if(!isset($iparr[1])){
                $iparr[1]=32;
            }
            $router[]=$iparr[1];
            $bitLen=ceil($iparr[1]/8);
            $bitarr=$this->getIPArray($iparr[0]);
            for($i=0;$i<$bitLen;$i++){
                $router[]=$bitarr[$i];
            }
        }
        
        $length=23+count($router);
        $lengtharr=$this->getBits($length);
        $roulenarr=$this->getBits($length-23);
        
        $router[]=0;
        $router[]=0;
        $data=pack("C".$length,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,0xff,
                $lengtharr[0],$lengtharr[1],self::$UPDATE_MSG,
                $roulenarr[0],$roulenarr[1],//撤回路由消息长度2字节
                ...$router
//                32,//撤回路由IP地址子网长度
//                $ipArray[0],$ipArray[1],$ipArray[2],$ipArray[3],//撤回路由IP地址
//                0,0//路由属性长度
            );
        return socket_write($this->socket, $data);
    }
    
    public function getBits($num){
        $bitArray=array();
        $bitArray[0]=floor($num/256);
        $bitArray[1]=$num-$bitArray[0]*256;
        return $bitArray;
    }
    
    public function  getIPArray($ip){
        $array=array();
        $ipArray=explode('.', $ip);
        $first=(int)$ipArray[0];
        $second=(int)$ipArray[1];
        $third=(int)$ipArray[2];
        $forth=(int)$ipArray[3];
        $array[]=$first;
        $array[]=$second;
        $array[]=$third;
        $array[]=$forth;
        return $array;
    }
}
