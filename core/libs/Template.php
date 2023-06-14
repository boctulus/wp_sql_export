<?php 

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Page;

class Template
{
    /*
        Retorna tema actual
    */
    static function get(){
        return get_option('template');
    }

    /*
        Cambia temporalmente el "theme" de WordPress 

        Ejemplo de uso:

        Template::set('kadence');

        @param string $template
    */  
    static function set(string $template)
    {
        require_once (ABSPATH . WPINC . '/pluggable.php');

        add_filter( 'template', function() use ($template) {
            return $template;
        });

        add_filter( 'stylesheet', function() use ($template) {
            return $template;
        });
    }

    static function getDirectory(){
        return get_template_directory_uri();
    }

    static function printName(){
        add_action('wp_head', function(){
            echo get_template();
        });      
    }
}