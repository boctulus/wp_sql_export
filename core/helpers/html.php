<?php

function pre(?string $str = null, ?string $id = null){
    if ($id !== null){
        return "<pre id=\"$id\">$str</str>";
    }

    return '<pre>' . $str . '</str>';
}

function p(?string $str = null){
    return '<p>'.$str.'</p>';
}

function br(){
    return '<br/>';
}


