<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

/*
	@author boctulus
*/

if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

class Plugins
{
    static function currentName(){
       $path = realpath(__DIR__ . '/../..');
       $_pth = explode(DIRECTORY_SEPARATOR, $path);
       $name = $_pth[count($_pth)-1];

       return $name;
    }

    /*
        https://wordpress.stackexchange.com/a/286761/99153
    */
    static function list(bool $active = true){
        if (!$active){
            return get_plugins();
        }   
        else {           
            $active_plugins = get_option('active_plugins');
            $all_plugins = get_plugins();
            $activated_plugins = [];

            foreach ($active_plugins as $plugin){           
                if(isset($all_plugins[$plugin])){
                    array_push($activated_plugins, $all_plugins[$plugin]);
                }           
            }

            return $activated_plugins;
        }
    }

    static function isActive(string $name){
        $name = strtolower($name);

        return is_plugin_active("$name/$name.php");
    }

    /*
        $path Array|string

        https://stackoverflow.com/a/13548821/980631
    */
    static function deactivate($path){
        deactivate_plugins($path);
    }

    /*
        $path Array|string

        https://stackoverflow.com/a/13548821/980631
    */
    static function activate($path){
        activate_plugins($path);
    }


}