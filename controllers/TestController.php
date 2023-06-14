<?php

namespace boctulus\SW\controllers;

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Files;
use boctulus\SW\core\libs\Users;
use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Products;


class TestController
{
   function purge(){
    Products::deleteAllProducts();
  }

  function test()
  {  
      // dd(
      //   Users::getCurrentUserRoles()
      // );

      // exit;
      // // ////////////


      $sku  = 'BL-J3861-2';

      /*
        Este codigo iria dentro de actualizar_precio_carrito() en el main.php
      */

      $pid      = Products::getProductIdBySKU($sku);

      $atts     = Products::getAttrByID($pid);

      $wp_roles = array_keys(Users::getRoleNames());
      $u_roles  = Users::getCurrentUserRoles();

  
      // dd($atts, 'ATTS');

      $rules = [];
      foreach ($atts as $at => $val)
      {
        if (Strings::startsWith('_MAYORISTA', $at)){
          $pattern = '/^MAYORISTA-([A-Z]+)-(\d+)$/';

          // descarto el "_" del comienzo
          $at = substr($at, 1);

          if (!preg_match($pattern, $at)) {
            Logger::logError("Unexpected format for '$at'. Expecting '$pattern'");	
            continue;
          }

          // dd($val, $at);

          list($tmp, $role, $min_pieces) = explode('-', $at);

          $price = $val[0] ?? '';
          $role  = strtolower($role);

          // Si ese usuario no tiene el rol de la regla ... ni seguir procesandola
          if (!in_array($role, $u_roles)){
            continue; //
          }

          /*
            Array
            (
              [role] => customer
              [min pieces] => 10
              [price] => 900
            )
          */

          // dd([
          // 	'role'       => $role,
          // 	'min pieces' => $min_pieces,
          // 	'price'	     => $price
          // ]);
          
          if (!isset($rules[$role])){
            $rules[$role] = [];
          }

          $rules[$role][] = [
            'min pieces' => $min_pieces,
            'price'	     => $price
          ];		

          if (!in_array($role, $wp_roles)){
            Users::createRole($role);
          }		
        }
      }

      /*
        Array
      (
          [customer] => Array
              (
                  [0] => Array
                      (
                          [min pieces] => 10
                          [price] => 900
                      )

                  [1] => Array
                      (
                          [min pieces] => 50
                          [price] => 850
                      )

              )

      )
      */

      dd($rules, 'RULES');

  }

  function get_file(){
    $url = 'http://woo4.lan/wp-admin/edit.php?post_type=product&page=product_importer&step=mapping&file=D%3A%2Fwww%2Fwoo4%2Fwp-content%2Fuploads%2F2023%2F06%2F1-14.csv&delimiter=%2C&update_existing=1&character_encoding&_wpnonce=5a536412a9';

    $file = Url::getQueryParam($url, 'file');

    dd($file);

  }

  function import(){
    require_once 'D:\www\woo4\wp-content\plugins\woocommerce\includes\import\class-wc-product-csv-importer.php';

    // Ruta de archivo o URL del archivo CSV
    $file_path = 'D:\Desktop\PRECIOS MAYOREO - JUANITA\CSVs\1.csv';

    $params = array(
      'file_path' => $file_path,
      'mapping' => array(
        'from' => array(
          'SKU' => 'sku',
          'Nombre' => 'name',
          'Precio' => 'regular_price',
          'Categoría' => 'category_ids',
        ),
        'to' => array(
          'sku' => 'SKU',
          'name' => 'Nombre',
          'regular_price' => 'Precio',
          'category_ids' => 'Categoría',
        ),
      ),
    );

    // Crea una instancia de WC_Product_CSV_Importer
    $importer = new \WC_Product_CSV_Importer($file_path, $params);

    // Importa los productos desde el archivo CSV
    $results  = $importer->import();

    // Logger::log($results);
    dd($results, 'RESULTS');

    // Verifica los resultados de la importación
    if ($results['result'] === 'success') {
        // La importación se realizó con éxito
        $imported_products = $results['imported'];
        $skipped_products = $results['skipped'];
        // Realiza las acciones necesarias con los productos importados y omitidos
    } else {
        // La importación falló
        $error_message = $results['message'];
        // Maneja el error de importación
    }
}

   
}
