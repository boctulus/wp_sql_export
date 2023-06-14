<?php

use boctulus\SW\core\libs\ApacheWebServer;

/*
    @author Pablo Bozzolo
*/

/*
    Puede incluirse al final del index.php de WP

    if (function_exists('set_upload_limits')){
        set_upload_limits();    
    }
*/
function set_upload_limits($upload_max_filesize = '1024M', $post_max_size = '1024M', $memory_limit = '768M', $max_exec_time = '600'){
    $config = config();

    /*
        Si no funciona, debe modificarse el php.ini
    */

    ApacheWebServer::updateHtaccessFile([
        'upload_max_filesize' => $upload_max_filesize,
        'post_max_size'       => $post_max_size,
    ], WP_ROOT_PATH);

    @ini_set("memory_limit", $memory_limit ?? $config["memory_limit"] ?? "768M");
    @ini_set("max_execution_time", $max_exec_time ?? $config["max_execution_time"] ?? "600");
}

function get_upload_limits(){
    return [
        "upload_max_filesize"   => ini_get("upload_max_filesize"),
        "post_max_size"         => ini_get("post_max_size"),
        "memory_limit"          => ini_get("memory_limit"),
        "max_execution_time"    => ini_get("max_execution_time"),
    ];
}