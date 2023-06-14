<?php 

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Files;

class Logger
{
    static $logFile = 'log.txt';

    static function getLogFilename(bool $full_path = false)
    {
        if (static::$logFile == null){
            static::$logFile = config()['log_file'];
        }

        return ($full_path ? LOGS_PATH : '') . static::$logFile;
    }
    
    static function truncate(){
        file_put_contents(LOGS_PATH . static::getLogFilename(), '');
    }
    
    static function getContent(?string $file = null){
        if ($file == null){
	        $file = static::getLogFilename();
        }

        $path = LOGS_PATH . $file;

        return file_get_contents($path);
    }


	/*
		Resultado:

		<?php 

		$arr = array (
		'x' => 'Z',
		);
	*/
	static function varExport($data, $path = null, $variable = null){
		if ($path === null){
			$path = ETC_PATH . 'exported.php';
		} else {
			if (!Strings::contains('/', $path) && !Strings::contains(DIRECTORY_SEPARATOR, $path)){
				$path = ETC_PATH . $path;
			}
		}

		if ($variable === null){
			$bytes = file_put_contents($path, '<?php '. "\r\n\r\n" . 'return ' . var_export($data, true). ';');
		} else {
			if (!Strings::startsWith('$', $variable)){
				$variable = '$'. $variable;
			}
			
			$bytes = file_put_contents($path, '<?php '. "\r\n\r\n" . $variable . ' = ' . var_export($data, true). ';');
		}

		return ($bytes > 0);
	}

	static function JSONExport($data, ?string $path = null){
		if ($path === null){
			$path = ETC_PATH . 'exported.json';
		} else {
			if (!Strings::contains('/', $path) && !Strings::contains(DIRECTORY_SEPARATOR, $path)){
				$path = ETC_PATH . $path;
			}
		}

		$bytes = file_put_contents($path, json_encode($data));
		return ($bytes > 0);
	}

	static function log($data, ?string $path = null, $append = true){	
		if ($path === null){
			$path = LOGS_PATH . static::getLogFilename();
		} else {
			if (!Strings::contains('/', $path) && !Strings::contains(DIRECTORY_SEPARATOR, $path)){
				$path = LOGS_PATH . $path;
			}
		}

		if (is_array($data) || is_object($data))
			$data = json_encode($data);
		
		$data = date("Y-m-d H:i:s"). "\t" .$data;

		return Files::writeOrFail($path, $data. "\n",  $append ? FILE_APPEND : 0);
	}

	static function dump($object, ?string $path = null, $append = false){
		if ($path === null){
			$path = LOGS_PATH . static::getLogFilename();
		} else {
			if (!Strings::contains('/', $path) && !Strings::contains(DIRECTORY_SEPARATOR, $path)){
				$path = LOGS_PATH . $path;
			}
		}

		if ($append){
			Files::writeOrFail($path, var_export($object,  true) . "\n", FILE_APPEND);
		} else {
			Files::writeOrFail($path, var_export($object,  true) . "\n");
		}		
	}

	static function dd($data, $msg = null, bool $append = true){
		static::log(!empty($msg) ? [ $msg => $data ] : $data, null, $append);
	}

	static function logError($error){
		if ($error instanceof \Exception){
			$error = $error->getMessage();
		}

		static::log($error, 'errors.txt');
	}

	static function logSQL(string $sql_str){
		$config = config();

		static::log($sql_str, 'sql_log.txt');
	}

}

