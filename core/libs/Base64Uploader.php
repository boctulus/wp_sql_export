<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Files;


class Base64Uploader
{
	protected $filenames  = [];
	protected $location = __DIR__ . '/../../../../../uploads'; // usar contante
	protected $erroneous = [];
	protected $renamerFn = null;
	protected const WILDCARD = '*';
	
	
	public function __construct(){
        if (!file_exists($this->location)){
            Files::mkDirOrFail($this->location);
        }
	}	
	
	// @param string path (sin / al final)
	public function setLocation($path){
		$this->location = $path;

        if (!file_exists($this->location)){
            Files::mkDirOrFail($this->location);
        }

		return $this;
	}	

	public function getLocation(){
		return $this->location;
	}
	
	/*
		Renamer
	*/
	public function setFileHandler($fn, ...$params){
		$this->renamer = [$fn, $params];
		return $this;
	}
	
	/* 
		Retorna un array con el nombre original y el nombre con el que se almacenó
	*/
	public function getFileNames(){
		return $this->filenames;
	}
	
	/**
	* Los archivos que presentaron error quedan aqui	
	*/
	public function getErrors(){
		return $this->erroneous;
	}
			
	public function doUpload($input_name = NULL)
	{				
		$renamer = $this->renamer[0];
		$subfijo = $this->renamer[1][0];	

		// reset	
		$this->filenames  = [];	
		$this->erroneous = [];
			
		Files::mkDirOrFail($this->location);
		Files::writableOrFail($this->location);
		
		$raw  = file_get_contents("php://input");

		$ext = null;
		if (Strings::startsWith('data:image', $raw)){
			$ext = Strings::match($raw, '~image/([a-z]+)~');
			$raw = Strings::after($raw, ';base64,');
		}

		$file = base64_decode($raw);

		// cambiado 
		$new_filename = $renamer($subfijo) . (!empty($ext) ? '.'.$ext : '');

		$f = $this->location. DIRECTORY_SEPARATOR. $new_filename;

		$bytes = file_put_contents($f, $file);

		if ($bytes != 0){
			$this->filenames[] = $new_filename;
		} else {
			$this->erroneous[] = $new_filename;
		}

		return $this;
    }	
	
}	