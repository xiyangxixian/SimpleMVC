<?php

function load_core($class){
   $file=__dir__.'/'.str_replace('\\','/', $class).'.class.php';
    if (is_file($file)) {
        require($file);
    } 
}
spl_autoload_register('load_core');


