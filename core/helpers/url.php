<?php

use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\ApiClient;

/*
    Returns BASE_URL to be used in the FrontEnd
*/
function base_url(){
    static $base_url;

    if ($base_url !== null){
        return $base_url;
    }

    $base_url = Url::getBaseUrl();

    return $base_url;
}

function plugin_url(){
    return base_url() . '/wp-content/plugins/' . plugin_name();
}

function plugin_assets_url($file = null){
    $url = base_url() . '/wp-content/plugins/' . plugin_name() . '/assets';

    return $url;
}

/*
    Ej:

    wp_enqueue_script( 'main-js', asset_url('/js/main.js') , array( 'jquery' ), '1.0', true );
*/
function asset_url($file){
    $url = base_url() . '/wp-content/plugins/' . plugin_name() . '/assets';

    if (!empty($file)){
        $file = Strings::removeFirstSlash($file);
        $url .= '/' . $file;
    }

    return $url;
}

function consume_api(string $url, string $http_verb = 'GET', $body = null, $headers = null, $options = null, $decode = true, $encode_body = true){
    $cli = (new ApiClient($url))
    ->withoutStrictSSL();

    $cli->setMethod($http_verb);
    $cli->setBody($body, $encode_body);
    $cli->setHeaders($headers ?? []);
    
    if (!empty($options)){
        $cli->setOptions($options);
    }

    $cli->send();

    $res = $cli->data();

    if ($decode && Strings::isJSON($res)){
        $res = json_decode($res, true);
    }

    return $res;
}