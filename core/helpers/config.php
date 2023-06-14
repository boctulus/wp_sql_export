<?php

function config(){
    $cfg = include __DIR__ . '/../../config/config.php';
    $db  = include __DIR__ . '/../../config/databases.php';

    return array_merge($cfg, $db);
}
