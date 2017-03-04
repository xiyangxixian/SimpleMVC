<?php

namespace lib\util;

interface ArrayHelper {
    function get($key,$default);
    function has($key);
    function set($key,$value);
    function remove($key);
}
