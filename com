#!/usr/bin/env php
<?php

use boctulus\SW\core\FrontController;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (php_sapi_name() != "cli"){
	return; 
}

define( 'ABSPATH', realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR);

require_once ABSPATH . '/wp-config.php';
require_once ABSPATH . '/wp-load.php';


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
    
add_action('wp_loaded', function(){
    if (defined('WC_ABSPATH') && !is_admin())
	{
		/*
			Front controller
		*/

		if ($cfg['front_controller'] ?? false){        
			FrontController::resolve();
		} 
    }    
});


if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE ^E_WARNING);
}

 	

