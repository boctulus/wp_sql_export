<?php

/*
    @author Pablo Bozzolo < boctulus@gmail.com >
*/

namespace boctulus\SW\core\libs;

use boctulus\WooEzequiel\libs\Strings;

class Utils
{
    static function firstNotEmpty($default_value = null, ...$args){
        foreach ($args as $val){
            if ($val !== null && $val !== ''){
                return $val;
            }
        }

        return $default_value;
    }

}