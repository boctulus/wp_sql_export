<?php

if (!defined('WP_ROOT_PATH'))
    define('WP_ROOT_PATH', realpath(__DIR__ . '/../../../..').  DIRECTORY_SEPARATOR);  

if (!defined('ROOT_PATH'))
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

if (!defined('PLUGIN_PATH')){
    define('PLUGIN_PATH', realpath(__DIR__  . '/..') . DIRECTORY_SEPARATOR);
}

if (!defined('ETC_PATH')){
    define('ETC_PATH',  PLUGIN_PATH . 'etc' . DIRECTORY_SEPARATOR);
}