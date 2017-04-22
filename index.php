<?php

//入口文件

//定义应用目录
define('APP_PATH',__dir__.'/app/');
//定义改入口所包含的模块，可定义多个。define('MODULE','home,admin');
define('MODULE','home');
//开启调试模式
define('DEBUG',true);
//引入框架入口文件
require(__dir__.'/mvc/init.php');
	