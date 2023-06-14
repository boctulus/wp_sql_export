<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\traits\ExceptionHandler;

class Errors
{	
	function __construct()
	{
		set_exception_handler(function(\Throwable $exception) {
			echo "ERROR: " , $exception->getMessage(), "\n";
			exit;
		});
	}
}