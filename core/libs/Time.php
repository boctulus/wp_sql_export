<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\SW\core\libs;

class Time {
    static $unit = 'MILI';   
    static $capture_buffer = false;

    static $NANO  = 1000000000;
    static $MICRO = 1000000;
    static $MILI  = 1000;

    static function setUnit($u){
        if (!in_array($u, ['NANO', 'MICRO', 'MILI'])){
            throw new \InvalidArgumentException("Unit should be one of 'NANO', 'MICRO', 'MILI'");
        }

        static::$unit = $u;
    }

    static function noOutput(){
        ob_start();
        static::$capture_buffer = true;
    }

	static function exec(callable $callback, int $iterations = 1){
        $start = microtime(true);
    
        for ($i=0; $i<$iterations; $i++){
            call_user_func($callback);	
        }
    
        $t = (microtime(true) - $start) / $iterations;
        
        if (static::$unit == 'MILI'){
            $t = $t * static::$MILI;
        }

        if (static::$unit == 'MICRO'){
           $t = $t * static::$MICRO;
        }

        if (static::$unit == 'NANO'){
            $t = $t * static::$NANO;
        }

        if (static::$capture_buffer){
            ob_end_clean(); 
        }

        return $t;
    }

}


