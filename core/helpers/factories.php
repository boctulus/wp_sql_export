<?php


use boctulus\SW\core\libs\Factory;
use boctulus\SW\core\libs\Request;
use boctulus\SW\libs\HtmlBuilder\Tag;

function tag(string $name) : Tag {
    return new Tag($name);
}

function request() : Request {
    return Factory::request();
}

function response($data = null, ?int $http_code = 200){
    return Factory::response($data, $http_code);
}

/*
    "Alias"
*/

function error($error = null, ?int $http_code = null, $detail = null){
    return Factory::response()->error($error, $http_code, $detail);
}

function notice($error = null, $detail = null){
    return Factory::response()->error($error, 200, $detail);
}

