<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

/*
	@author boctulus
*/

class Cache
{
    const FOREVER = -1;
    const NEVER   =  0;

    static function expired(int $cached_at, int $expiration_time) : bool {
        switch ($expiration_time){
            // nunca expira
            case -1:
                $expired = false;
            break;
            // nunca se cachea
            case 0:
                $expired = true;
            break;    
            default:
                $expired = time() > $cached_at + $expiration_time;
        }

        return $expired;
    }

    static function expiredFile(string $cache_path, int $expiration_time) : bool {
        $exists = file_exists($cache_path);

        if (!$exists){
            return true;
        }

        $updated_at = filemtime($cache_path);

        if (static::expired($updated_at, $expiration_time)){
            return true;
        }

        return false;
    }

}
