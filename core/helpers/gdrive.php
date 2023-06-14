<?php

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\GoogleDrive;

function gdrive_file_updated_at($link){
	if (empty($link)){
		return false;
	}

	if (Strings::contains('/folders/', $link)){            
		return false;
	}

	$link = trim($link);

	$link = str_replace(
		'&amp;', '&', $link
	);

	if (!Strings::startsWith('https://docs.google.com', $link)){
		return false;
	}

	$id = Url::getQueryParam($link, 'id');
	
	if ($id === null){
		return false;
	}

	$key     = 'gdrive_'. $id .'_updated_at';

	$updated = get_transient($key);

	if (!empty($updated)){
		// dd("Obteniendo '$key' de TRANSIENT");
		return $updated;
	}

	try {
		
		$updated = (new GoogleDrive())
		->getUpdateDate($link);

		// Para no saturar a Google Drive con peticiones las distribuyo en el tiempo
		set_transient($key, $updated, 3600 * 24 + rand(3600, 3600 *  48));
		
		return $updated;

	} catch (\Exception $ex){
		$ex_msg  = $ex->getMessage();

		if (Strings::isJSON($ex_msg)){
			$message = json_decode($ex_msg, true);
			$message = $message['error']['message'] ?? null;

		} else {
			$message  = $ex_msg;
		}

		// dd($message);
	   throw new \Exception("Google Drive: $message");
	}   
}
