<?php

function config(){
    $keys=func_get_args();
    static $config=null;
    if($config==null){
        $config=array();
        $configFileArray=require(__dir__.'/config_list.php');
        foreach($configFileArray as $file){
            $configArray=require($file);
            $config=array_merge($config,$configArray);
        }
    }
    $out=$config;
    foreach ($keys as $key){
        $out=$out[$key];
    }
    return $out;
}

