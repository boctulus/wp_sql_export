<?php

namespace boctulus\SW\libs;

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\Reactor;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Products;
use boctulus\SW\core\libs\ProductMetabox;

/*
    By boctulus
*/

class MyReactor extends Reactor 
{  
    private function notify($pid, $sku, $action){
        Logger::log("PID: $pid | SKU: $sku | ACTION: $action");

        try {
            Logger::log(__FILE__ . ':'. __FUNCTION__ . ':' . __LINE__);
        } catch (\Exception $e){
            Logger::log("Problema al crear notificacion de $action sobre PID '$pid'. Detalle: " . $e->getMessage());
        }
    } 

    private function updateRow($pid, $sku, $action){
        // Logger::log(__FUNCTION__);

        Logger::log("PID: $pid | SKU: $sku | ACTION: $action");
        try {
            // Logger::log(__FILE__ . ':'. __FUNCTION__ . ':' . __LINE__);
                     
            $model = table('mayorista_prod_attrs');

            $rows = [];

            if (!empty($sku)){
                $rows = $model->where(['sku' => $sku])
                ->orderBy([
                    'id' => 'DESC'
                ])
                ->get();

                if (empty($pid)){
                    $pid = Products::getProductIdBySKU($sku);
                }
            } else {
                // sino tiene SKU ni PID,... nada que actualizar
                if (empty($pid)){
                    //dd("NO PID");	// <-------
                    return;
                }

                $rows = $model->where(['pid' => $pid])
                ->orderBy([
                    'id' => 'DESC'
                ])
                ->get();
            }                       

            // Logger::log($model->dd());
            // Logger::dd($rows, '$rows');
            // Logger::dd(gettype($rows), '$rows type');

            if (empty($pid)){
                Logger::logError("Unexpected lack of PID for SKU = '$sku' and action '$action'");
            }

            // Espero solo una row pero igualmente ....
            foreach ($rows as $row){
                $attrs = $row['attrs'] ?? null;

                if (empty($attrs)){
                    continue;
                }

                $attrs = json_decode($attrs, true);

                foreach ($attrs as $at => $val){		
                    if (Strings::startsWith('MAYORISTA', $at)){
                        $at = trim($at);
                        // dd($val, $at);

                        // $pattern = '/^MAYORISTA-([A-Z]+)-(\d+)$/';

                        // if (!preg_match($pattern, $at)) {
                        //     Logger::logError("Unexpected format for '$at'. Expecting '$pattern'");	
                        //     continue;
                        // }

                        ProductMetabox::set($pid, $at, $val ?? '');
                    }
                }
            }

            $model->delete(false);
            
        } catch (\Exception $e){
           Logger::log("Problema al procesar '$action' sobre PID '$pid'. Detalle: " . $e->getMessage());
        }
    } 

	function onCreate($pid, $sku, $product){
        // $this->notify($pid, $sku, __FUNCTION__, $product);
        $this->updateRow($pid, $sku, __FUNCTION__, $product);
    }

	function onUpdate($pid, $sku, $product){        
        // $this->notify($pid, $sku, __FUNCTION__, $product);
        $this->updateRow($pid, $sku, __FUNCTION__, $product);
    }
	
    function onDelete($pid, $sku, $product){
        // $this->notify($pid, $sku, __FUNCTION__, $product);
    }

	function onRestore($pid, $sku, $product){
        // $this->notify($pid, $sku, __FUNCTION__, $product);
        $this->updateRow($pid, $sku, __FUNCTION__, $product);
    }   
}