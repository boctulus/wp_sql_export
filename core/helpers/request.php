<?php

use boctulus\SW\core\libs\Arrays;

function get(string $key_name, $allowed_values = null){
    return Arrays::getOrFail($_GET, $key_name, $allowed_values);
}