<?php

/*
    Plugin deactivation script

    /wp-content/plugins/{nombre_plugin}/scripts/off.php

    o

    php .\scripts\off.php
*/

define('ROOT_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

$_pth = explode(DIRECTORY_SEPARATOR, ROOT_PATH);
$name = $_pth[count($_pth)-2];

if (file_exists(ROOT_PATH . "$name.php")){

    if (!file_exists(ROOT_PATH . "$name.ph_")){
        rename(ROOT_PATH . "$name.php", ROOT_PATH . "$name.ph_");
    } else {
        unlink(ROOT_PATH . "$name.ph_");
        rename(ROOT_PATH . "$name.php", ROOT_PATH . "$name.ph_");
    }

    print_r("Plugin $name desactivado");
} else {
    print_r("Plugin $name ya estaba desactivado");
}


