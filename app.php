<?php

/*
    @author Pablo Bozzolo < boctulus@gmail.com >
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (defined('ROOT_PATH')){
    return;
}

require_once __DIR__   . '/core/helpers/env.php';
require_once __DIR__   . '/config/constants.php';

$cfg = require __DIR__ . '/config/config.php';

if ($cfg["use_composer"] ?? true){
    /*
        En vez de sleep() deberia usar algun paquete async
    */
    
    if (!file_exists(ROOT_PATH .'composer.json')){
        throw new \Exception("Falta composer.json");
    }       
    
     if (!file_exists(ROOT_PATH . 'vendor'. DIRECTORY_SEPARATOR .'autoload.php')){
        chdir(__DIR__);
        exec("composer install --no-interaction");
        sleep(10);
    }

    require_once APP_PATH . 'vendor/autoload.php';
}

/*
    TimeZone adjust

    Lo ideal seria que esto este dentro de un package y que se pueda desconectar
*/

if (isset($cfg['DateTimeZone'])){
    $ok = date_default_timezone_set($cfg['DateTimeZone']);
    
    if (!$ok){
        dd("FALLO AL INTENTAR CAMBIAR TIMEZONE");
    }
}

/* Helpers */

$helper_dirs = [
    __DIR__ . '/core/helpers', 
    __DIR__ . '/helpers'
];

$excluded    = [
    'cli.php'
];

foreach ($helper_dirs as $dir){
    if (!file_exists($dir) || !is_dir($dir)){
        throw new \Exception("Directory '$dir' is missing");
    }

    foreach (new \DirectoryIterator($dir) as $fileInfo) {
        if($fileInfo->isDot()) continue;
        
        $path     = $fileInfo->getPathName();
        $filename = $fileInfo->getFilename();

        if (in_array($filename, $excluded)){
            continue;
        }

        if(pathinfo($path, PATHINFO_EXTENSION) == 'php'){
            require_once $path;
        }
    }    
}
    
require_once __DIR__ . '/core/scripts/admin.php';


if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE ^E_WARNING);
}
