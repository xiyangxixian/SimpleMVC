<?php

function load_lib($class){
    $file=__dir__.'/'.str_replace('\\','/', $class).'.class.php';
    if (is_file($file)) {
        require($file);
    }
}
spl_autoload_register('load_lib');


