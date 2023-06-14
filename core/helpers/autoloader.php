<?php

spl_autoload_register('wp_namespace_autoload');

/*
    @author Pablo Bozzolo < boctulus@gmail.com >
*/

function wp_namespace_autoload( $class ) {
    $config    = include __DIR__ . '/../../config/config.php';
    $namespace = $config['namespace'];
 
	if (strpos($class, $namespace) !== 0) {
		return;
	}
 
	$class = str_replace($namespace, '', $class);
	$class = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

	$directory = realpath(ROOT_PATH);
	$path      = $directory . $class;

    if ( file_exists( $path ) ) {
        include_once( $path );
    } else {
        //throw new \Exception("The file attempting to be loaded at '$path' does not exist." );
    }
}