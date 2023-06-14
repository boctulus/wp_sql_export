<?php 

namespace boctulus\SW\core\libs;

class Device
{
    /*
        Screen
    */

    static function isMobile(){
        return wp_is_mobile();
    }
}