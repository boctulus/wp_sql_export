<?php

namespace boctulus\SW\core\libs;

/*
	@author boctulus
*/

class SyncProducts
{
    protected static $errors    = 0;
    protected static $processed = 0;

    static function delete(Array $skus){
        foreach($skus as $sku){
            StdOut::pprint("Borrando producto con '$sku'");
            Products::deleteProductBySKU($sku);
        }
    }

    static function restore(Array $skus){
        foreach($skus as $sku){
            StdOut::pprint("Restaurando producto con SKU '$sku'");
            Products::restoreBySKU($sku);
        }
    }

    static function import(Array $products, Array $simple_product_attrs = null)
    {
        static::$errors    = [];
        static::$processed = 0;

        StdOut::pprint("Procesando ". count($products) . " productos");

        foreach($products as $p)
        {
            static::$processed++;

            StdOut::pprint("Procesando el producto #".static::$processed);

            $sku = $p['sku'] ?? null;

            if ($sku == null){
                StdOut::pprint("Producto sin SKU *no* pudo ser procesado.");
                continue;
            }

            StdOut::pprint("...");

            try {
                $pid = Products::getProductIdBySKU($sku);

                if (!empty($pid)){
                    /*
                        SI existe, actualizo
                    */   

                    StdOut::pprint("Actualizando producto existente con SKU '$sku' (pid = $pid)");
                
                    products::updateProductBySku($p);             
                } else {
                    /*
                        Sino existe, lo creo
                    */

                    StdOut::pprint("Creando producto para SKU '$sku'");

                    $pid = Products::createProduct($p);   
                }

                /*
                    Agrego atributos si los hubiera
                */

                $simple_product_attrs = $p['attributes'] ?? null;

                if ($p['type'] == 'simple' && !empty($simple_product_attrs)){
                    Products::setProductAttributesForSimpleProducts($pid, $simple_product_attrs); 
                }

            } catch (\Exception $e){
                $msg              = $e->getMessage();
                static::$errors[] = $msg;
                debug($msg);
            }    
        }

        return (static::$errors == 0);
    }

    static function getErrors(){
        return static::$errors;
    }

    static function getProcessed(){
        return static::$processed;
    }
}
