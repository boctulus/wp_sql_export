<?php

function wp_core(){
	if ( php_sapi_name() == 'cli' && !defined('ABSPATH')) {
		define( 'ABSPATH', realpath(__DIR__ . '/../../../../..') . DIRECTORY_SEPARATOR);
		
		require_once ABSPATH . '/wp-config.php';
		require_once ABSPATH .'/wp-load.php';
	}
}

wp_core();


