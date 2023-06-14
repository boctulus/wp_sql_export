<?php

use boctulus\SW\core\libs\Files;
use boctulus\SW\core\libs\Logger;

/*
    Requiere que este habilitado el modo debug
*/
function logger($data, ?string $path = null, $append = true){
    if (!config()['debug']){
        return;
    }

    return Logger::log($data, $path, $append);
}

/*
    Requiere que este habilitado el modo debug
*/
function dump($object, ?string $path = null, $append = false){
    if (!config()['debug']){
        return;
    }

    return Logger::dump($object, $path, $append);
}

/*
    Requiere que este habilitado el modo debug
*/
function log_error($error){
    if (!config()['debug']){
        return;
    }

    return Logger::logError($error);
}

/*
    Requiere que este habilitado el modo debug y log_sql
*/
function log_sql(string $sql_str){
    if (!config()['debug'] || !config()['log_sql']){
        return;
    }

    return Logger::logSQL($sql_str);
}