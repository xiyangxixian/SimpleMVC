<?php
use lib\db\Db;
defined('APP_PATH') or define("APP_PATH", __dir__.'/app/');
defined('MOUDLE') or define("MOUDLE",'home');
defined('DEBUG') or define("DEBUG",true);
defined('ROOT_PATH') or define("ROOT_PATH",__dir__.'/');

foreach (require(__dir__.'/require_list.php') as $path) {
    require(__dir__.'/'.$path);
}
spl_autoload_register('load_app');

if (DEBUG) {
    require(__dir__.'/util/make_file.php');
    make_file();
}
session()->start();
response()->header('Content-type:text/html;charset=utf-8');
Db::loadConfig(config('DB_CONFIG'));
require(APP_PATH.'init.php');
if(file_exists(($filename=APP_PATH.'modules/'.request()->module().'/init.php'))){
    require($filename);
}
context()->run();

