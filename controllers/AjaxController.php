<?php

namespace boctulus\SW\controllers;

use boctulus\SW\core\libs\DB;
use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Files;
use boctulus\SW\core\libs\Model;
use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\Request;
use boctulus\SW\core\libs\Products;
use boctulus\SW\core\libs\System;

class AjaxController
{
  function make_backup()
  {
    // $req  = Request::getInstance();
    
    try {
      global $wpdb;

      $command = wp_sql_export_database();

      $output = System::exec($command);

      if ($output !== null && is_array($output)){
        $output = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      }       
  
      if (System::resultCode() != 0){
        return error("Failed with result code " . System::resultCode(), 500, "$command ~ output: $output");
      }

      $sql_file    = $wpdb->dbname . '.sql  ';
      $backup_file = ETC_PATH . "$sql_file";

      // Exporta las imágenes de la galería
      // wp_sql_export_gallery_images();

      /*
        Podria zipear las imagenes y dejar otro enlace al ZIP
      */

      $output = [
        // podria zipearse el .sql
        // podria leer el contenido ... y devolverlo en el body
        'sql_file'  => Url::getBaseUrl() .  "/etc/$sql_file"
      ];

      return response()->sendJson($output, 200);

    } catch (\Exception $e){
        $err = "Error: " . $e->getMessage();
        Files::logger($err);

        return error($err);
    }    
  }    

   
}
