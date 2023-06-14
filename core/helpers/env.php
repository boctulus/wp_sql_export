<?php

use boctulus\SW\core\libs\Env;

require_once __DIR__ . '/../../core/libs/Env.php';

if (!function_exists('env')){
    function env(string $key){
        return Env::get($key);
    }
}
