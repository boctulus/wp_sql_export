<?php

/*
    Installer
*/

use boctulus\SW\core\libs\Files;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (php_sapi_name() != "cli"){
	return; 
}

require_once __DIR__ . '/app.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR);

	require_once ABSPATH . '/wp-config.php';
	require_once ABSPATH .'/wp-load.php';
}

$config = include __DIR__ . '/config/config.php';

/////////////////////////////////////////////////


$replace_if_exist = false;
$seed             = false;

if ($argc >1){
    switch ($argv[1]){
        case '1':
        case 'on':
        case 'true':
            $replace_if_exist = true;
        break;
    } 
}

if ($argc >2){
    switch ($argv[2]){
        case '1':
        case 'on':
        case 'true':
            $seed = true;
        break;
    } 
}

require __DIR__ . '/scripts/installer.php';

_dd('---x---');