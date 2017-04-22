<?php
use lib\db\Db;

//应用目录
defined('APP_PATH') or define("APP_PATH", __dir__.'/app/');
// 定义应用目录
defined('MODULE') or define("MODULE",'home');
//定义改入口所包含的模块
defined('DEBUG') or define("DEBUG",true);
//定义框架目录
defined('MVC_PATH') or define("MVC_PATH",__dir__.'/');
//加载全部类库、函数库、配置文件
foreach (require(__dir__.'/require_list.php') as $path) {
    require(__dir__.'/'.$path);
}
//注册应用的自动加载
spl_autoload_register('load_app');
//生成应用目录
if (DEBUG) {
    require(__dir__.'/util/make_file.php');
    make_file();
}
//开启session
session()->start();
//设置响应头
response()->header('Content-type:text/html;charset=utf-8');
//加载数据库配置
Db::loadConfig(config('DB_CONFIG'));
//加载应用初始化文件
!file_exists(APP_PATH.'init.php') or require(APP_PATH.'init.php');
//加载慕课应用初始化文件
if(file_exists(($filename=APP_PATH.'modules/'.request()->module().'/init.php'))){
    require($filename);
}
//运行应用
context()->run();

