<?php

function make_file() {
    
    $moduleArr=explode(',',MOUDLE);
    $module=$moduleArr[0];
    
    //主文件夹
    $classDir = APP_PATH;
    if (!is_dir($classDir)) {
        mkdir($classDir, 755, true);
    }
    
    //配置文件
    $configFile = APP_PATH.'config.php';
    if (!file_exists($configFile)) {
        $file = fopen($configFile, "x+");
        $txt = '<?php
    return array();';
        fwrite($file, $txt);
        fclose($file);
    }
    
    //初始化文件
    $requireFile = APP_PATH . 'init.php';
    if (!file_exists($requireFile)) {
        $file = fopen($requireFile, "x+");
        fclose($file);
    }
    
    //初始化文件
    $moduleDir=APP_PATH .'modules/'.$module;
    if (!is_dir($moduleDir)) {
        mkdir($moduleDir, 755, true);
    }
    
    //初始化文件
    $requireFile = APP_PATH .'modules/'.$module.'/init.php';
    if (!file_exists($requireFile)) {
        $file = fopen($requireFile, "x+");
        fclose($file);
    }

    //公共文件夹
    $commonDir = APP_PATH . 'common';
    if (!is_dir($commonDir)) {
        mkdir($commonDir, 755, true);
    }
    
    //模型文件夹
    $modelDir = APP_PATH . 'model';
    if (!is_dir($modelDir)) {
        mkdir($modelDir, 755, true);
    }
    
    $extendDir = APP_PATH . 'extend';
    if (!is_dir($extendDir)) {
        mkdir($extendDir, 755, true);
    }
    
    //视图文件夹
    $viewDir = APP_PATH .'modules/'.$module.'/view/index';
    if (!is_dir($viewDir)) {
        mkdir($viewDir, 755, true);
    }

    //视图
    $viewFile = APP_PATH.'modules/'.$module.'/view/index/index.php';
    if (!file_exists($viewFile)) {
        $file = fopen($viewFile, "x+");
        $txt = '<h1 style="margin-top:15%;font-family:微软雅黑;font-size:100px;font-weight:normal;text-align:center;">:)  &nbsp<span style="font-size:50px;">Hello World</span></h1>';
        fwrite($file, $txt);
        fclose($file);
    }

    //控制器文件夹
    $controlDir = APP_PATH.'modules/'.$module.'/controller/';
    if (!is_dir($controlDir)) {
        mkdir($controlDir, 755, true);
    }
    
    //控制器文件
    $controlFile = APP_PATH.'modules/'.$module.'/controller/Index.class.php';
    if (!file_exists($controlFile)) {
        $file = fopen($controlFile, "x+");
        $txt = '<?php
    namespace modules\\'.$module.'\\controller;
    class Index{
        public function index(){
            view();
        }
    }';
        fwrite($file, $txt);
        fclose($file);
    }
    
}
