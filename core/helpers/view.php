<?php

use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Files;

function plugin_path(){
    return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
}

function plugin_name(){
    static $plugin_name ;

    if ($plugin_name !== null){
        return $plugin_name;
    }

    $plugin_name = Strings::before(
        Strings::after(__DIR__,  DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR), 
        DIRECTORY_SEPARATOR
    );

    return $plugin_name;
}

function assets_url(?string $resource = null){
    $url = plugin_url() . '/';    

    if (!Strings::startsWith('assets/', $resource)){
        $url .= 'assets/';
    }

    return $url . (!$resource === null ? '' : $resource);
}

function asset(string $resource){
    return assets_url($resource);
}

function css_file(string $src, $dependencies = [], $version = false, $media = 'all'){
	$src    = ltrim($src, '/\\');
	$handle = $src;

	if (!Strings::startsWith('http', $src)){
		$src = asset($src);
	}

	wp_register_style($handle, $src, $dependencies, $version, $media);
	wp_enqueue_style($handle);
}

function js_file(string $src, bool $in_head = false, $dependencies = [], $version = false){
	$src    = ltrim($src, '/\\');
	$handle = $src;

	if (!Strings::startsWith('http', $src)){
		$src = asset($src);
	}

	wp_register_script($handle, $src, $dependencies, $version, !$in_head);
	wp_enqueue_script($handle);
}

function enqueue(string $fn_name, $priority = 10, $accepted_args =1){
    add_action('wp_enqueue_scripts', $fn_name, $priority, $accepted_args);
}

function enqueue_admin(string $fn_name, $priority = 10, $accepted_args =1){
    add_action('admin_enqueue_scripts', $fn_name, $priority, $accepted_args);
}

function include_no_render(string $path, ?Array $vars = null){
    if (!empty($vars)){
        extract($vars);
    }      
    
    ob_start();
    include $path;
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

function get_view_src(string $filename){
    $ext = Files::fileExtension($filename);

    if ($ext != 'php' && $ext != 'htm' && $ext != 'html'){
        // asumo es .php
        $filename .= '.php';
    }

    return plugin_path() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $filename;
}

function get_view(string $view_path, ?Array $vars = null){
    return include_no_render(get_view_src($view_path), $vars);
}

function view(string $view_path, array $vars  = null){
    if (!empty($vars)){
        extract($vars);
    }      

    include get_view_src($view_path);
}


/*
    Antes llamada encodeProp()
*/
function var_encode($name, $value){
    $encoded = base64_encode(is_array($value) ? '--array--' . json_encode($value) : $value);

    return "<input type=\"hidden\" name=\"$name-encoded\" id=\"comunas-encoded\" value=\"$encoded\">";
}
