<?php

use boctulus\SW\core\libs\Date;


function datetime(string $format = 'Y-m-d H:i:s', $timezone = null){
    return Date::datetime($format, $timezone);
}

// alias for datetime()
function at(bool $cached = false){
    static $date;
    
    if ($cached){
        if ($date === null){
            $date = datetime('Y-m-d H:i:s');
        }

        return $date;
    }
    
    return datetime('Y-m-d H:i:s');
}

// alias for at()
function now(bool $cached = false){
    return at($cached);
}