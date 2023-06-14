<?php

use boctulus\SW\core\libs\ApiClient;

function get_api_client(string $url){
    $config        = config();
    $proxy_url     = $config['proxy_url'] ?? null;
    $proxy_api_key = $config['proxy_api_key'] ?? null;

    if (empty($proxy_url)){
        throw new \Exception("Proxy url is required");
    }

    if (empty($proxy_api_key)){
        throw new \Exception("Proxy api key is required");
    }

    return (new ApiClient($proxy_url))
    ->setHeaders([
        "Proxy-Auth: $proxy_api_key",
        "Proxy-Target-URL: $url"
    ]);
}