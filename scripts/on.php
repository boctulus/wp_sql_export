<?php

/*
    Plugin activation script

    /wp-content/plugins/{nombre_plugin}/scripts/on.php

    o

    php .\scripts\on.php
*/

define('ROOT_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

$_pth = explode(DIRECTORY_SEPARATOR, ROOT_PATH);
$name = $_pth[count($_pth)-2];


if (file_exists(ROOT_PATH . "$name.ph_")){
    rename(ROOT_PATH. "$name.ph_", ROOT_PATH."$name.php");
    print_r("Plugin $name activado");
} else {
    print_r("Plugin $name ya estaba activo");
}