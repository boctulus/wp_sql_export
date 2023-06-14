<?php

namespace boctulus\SW\core\libs;

/*
    Wrapper sobre el SDK de Google Drive

    @author Pablo Bozzolo < boctulus >
*/
class GoogleDrive
{
	protected $client;

    function __construct($api_key = null) { 
        $this->__getClient($api_key); 
    }

    /*
        Google API client

        Podria trabajar tambien on OAuth
    */
	protected function __getClient($api_key = null){
		$cfg = config();

        $google_console_api_key = $api_key ?? $cfg['google_console_api_key'];

        $class  = 'Google\Client';
        $client = new $class();

        // Disable SSL check in local
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'debug' || env('DEGUG') == 'true'){  
            $guzzleClient = new \GuzzleHttp\Client(["curl" => [
                CURLOPT_SSL_VERIFYPEER => false
            ]]);        
        }

        $client->setHttpClient($guzzleClient);

        $client->setApplicationName($cfg['app_name']);
        $client->setDeveloperKey($google_console_api_key);

		$this->client = $client;
	}

    protected function __getDriveService(){
        $class = 'Google_Service_Drive';
        return new $class($this->client);
    }

    /*
        Obtiene info (con los permisos correctos) sobre un "drive" o "folder" 
        o sea... primero hace un "list" de archivos

        Ej:
    
        $googleDrive->getInfo('1oUqLiey81m0keXAo1ZtOsGYfd5c1VTeT')

        o

        $googleDrive->getInfo('1oUqLiey81m0keXAo1ZtOsGYfd5c1VTeT', null, 'createdTime', 'modifiedTime')

        o

        googleDrive->getInfo('1oUqLiey81m0keXAo1ZtOsGYfd5c1VTeT', null,  'createdTime, modifiedTime')

        o incluyendo paginacion

        $googleDrive->getInfo('1oUqLiey81m0keXAo1ZtOsGYfd5c1VTeT', [
            'pageSize' => 10
        ], 'id, name, createdTime, modifiedTime');


        Para saber que atributos ('createdTime', 'modifiedTime', etc) se pueden solicitar, ver la lista:

        https://developers.google.com/resources/api-libraries/documentation/drive/v3/php/latest/class-Google_Service_Drive_DriveFile.html
    */
    function getFolderInfo(?string $folder_id = null, ?array $options = null, ...$file_fields)
    {
        $service = $this->__getDriveService();

        $query = "trashed = false";
        if ($folder_id) {
            // Add the folder ID to the query
            $query .= " and '{$folder_id}' in parents";
        }

        $file_fields_str = '';
        if (!empty($file_fields)){
            $file_fields_str = implode(',', $file_fields); 
        }  

        $_options = [
            'q' => $query,
            'fields' => "files($file_fields_str)",
        ];

        /*
            Podria incluir offset ("page"?),... y hacer un merge entre $_options y $options
        */
        if (isset($options['pageSize'])){
            $_options['pageSize'] =  $options['pageSize'];
            $_options['fields']   = 'nextPageToken, '. $_options['fields'];
        }

        $files = $service->files->listFiles($_options);

        if (is_array($file_fields) && count($file_fields) == 1 && Strings::contains(',', $file_fields[0])){
            $file_fields = explode(',',$file_fields[0]);
            $file_fields = array_map('trim', $file_fields);
        }

        $ret = [];
        foreach ($files->getFiles() as $file) {
            $row = [];

            foreach ($file_fields as $field){
                $getter = "get". ucfirst($field);
                $row[$field] = $file->{$getter}();
            }

            $ret[] = $row;
        }

        return $ret;
    }

    /*
        Obtiene INFO sobre un ARCHIVO en particular

        $googleDrive  = new GoogleDrive();
        $modifiedTime = $googleDrive->getInfo($id, 'modifiedTime')['modifiedTime'];

        Nota:

        Solo usar $format si todos los campos pasados detro de $fields son de tipo timestamp
        porque se aplica $format a todos los campos (por diseño)

    */
    function getInfo(string $link_or_id, $fields, ?string $format = null): array
    {
        if (Strings::startsWith('https://docs.google.com/', $link_or_id)){
            $id = Url::getQueryParam($link_or_id, 'id');
        } else {
            $id = $link_or_id;
        }

        $service = $this->__getDriveService();

        if (is_array($fields)){
            $fields_str = implode(',', $fields);
        } else {
            $fields_str = $fields;
            $fields     = explode(',', $fields);
        }

        // Retrieve the file metadata based on the link or ID
        $file = $service->files->get($id, [
            'fields' => $fields_str
        ]);

        $ret = [];    

        foreach ($fields as $field){
            $getter = "get". ucfirst($field);
            $ret[$field] = $file->{$getter}();

            // Se aplica a cada campo !
            if (!empty($format)){
                $ret[$field] = date($format, strtotime($ret[$field]));
            }
        };
    
        return $ret;
    }

    /*
        Obtiene "Update date" sobre un ARCHIVO en particular

        $googleDrive = new GoogleDrive();
        $updateDate  = $googleDrive->getUpdateDate($gd_link, 'd-m-Y');
    */
    function getUpdateDate(string $link_or_id, string $format = 'Y-m-d H:i:s')
    {
        if (Strings::startsWith('https://docs.google.com/', $link_or_id)){
            $id = Url::getQueryParam($link_or_id, 'id');

            // var_dump($link_or_id);
            // dd($id, 'link or id = '. $link_or_id);
        } else {
            $id = $link_or_id;
        }

        // if (is_null($id)){
        //     dd($link_or_id, 'link or id');
        // }

        if (empty($id)){
            return null;
        }

        // Extract the modified time from the file metadata
        $modifiedTime = $this->getInfo($id, 'modifiedTime', $format)['modifiedTime'];

        return $modifiedTime;
    }

}

