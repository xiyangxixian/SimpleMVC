<?php

function config($key1=null,$key2=null,$key3=null){
    static $config=null;
    if($config==null){
        $config=array();
        $configFileArray=require(__dir__.'/config_list.php');
        foreach($configFileArray as $file){
            $configArray=require($file);
            $config=array_merge($config,$configArray);
        }
    }
    if($key1===null){
        return $config;
    }
    if($key2===null){
        return $config[$key1];
    }
    if($key3==null){
        return $config[$key1][$key2];
    }
    return $config[$key1][$key2][$key3];
}

