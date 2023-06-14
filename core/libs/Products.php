<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Strings;

if ( ! function_exists( 'wp_crop_image' ) ) {
    include_once ( __DIR__ . '/../../../../../wp-admin/includes/image.php' );
}

/*
    Product utility class

    Investigar '_transient_wc_attribute_taxonomies' como option_name en wp_options
*/
class Products extends Posts
{
    static $post_type   = 'product';
    static $cat_metakey = 'cat_product';

    static function productExists($sku){
        global $wpdb;

        $product_id = $wpdb->get_var(
            $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s'", $sku)
        );

        return is_numeric($product_id) && $product_id !== 0;
    }

    static function productIDExists($prod_id){
        $p = \wc_get_product($prod_id);

        return !empty($p);
    }

    static function isSimple($product){
        $product = static::getProduct($product);

        return $product->get_type() === 'simple';
    }

    static function isExternal($product){
        $product = static::getProduct($product);

        if ($product instanceof \WC_Product_External){
            // extra-check

            if (empty($product->get_product_url())){
                throw new \Exception("External producto without url");
            }

            return true;
        } 

        return false;
    }

    static function getExternalProductData(){
        $prods = static::getAllProducts();

        $arr = [];
        foreach ($prods as $prod){
            if ($prod instanceof \WC_Product_External)
            {
                $prod_url = $prod->get_product_url();

                if (empty($prod_url)){
                    break;
                }

                $arr[] = [
                    'id'         => $prod->get_id(),
                    'prod_url'   => $prod_url,
                    'price'      => $prod->get_price(),
                    'sale_price' => $prod->get_sale_price(),
                    'status'     => $prod->get_status()
                ];
            } 
        }

        return $arr;
    }
        
    static function getProduct($product){
        $p =  is_object($product) ? $product : \wc_get_product($product);
        return $p;
    }

    /*
        A futuro podria reemplazar a dumpProduct() 
        pero implicaria modificar createProduct() para que pueda leer este otro formato.
    */
    static function getAllProducts($status = false, bool $as_array = false){
        $cond = array( 'limit' => -1 );
        
        if ($status === null) {
            $cond['status'] = 'publish';
        }elseif ($status !== false){
            $cond['status'] = $status;
        }

        $prods = wc_get_products($cond);

        if (!$as_array){
            return $prods;
        }

        $prods_ay = [];
        foreach ($prods as $product){
            $p_ay = $product->get_data();
        
            $p_ay['date_created']  = $p_ay['date_created']->__toString();
            $p_ay['date_modified'] = $p_ay['date_modified']->__toString();

            $p_ay['type']          = $product->get_type();
            $p_ay['product_url']   = ($product instanceof \WC_Product_External) ? $product->get_product_url() : null; 

            foreach($p_ay['attributes'] as $at_key => $at_data){
                $p_ay['attributes'][$at_key] = $at_data->get_data();
            }

            $prods_ay[] = $p_ay;
        }

        return $prods_ay;
    }

    static function getProductPropertiesBySKU($sku){
        $result_ay = static::getByMeta('SKU', $sku);

        if (empty($result_ay)){
            return;
        }

        return $result_ay[0];
    }

    static function getProductIdBySKU($sku, $post_status = null){
        $pid = wc_get_product_id_by_sku($sku);
    
        if ($pid != null){
            return $pid;
        }
    
        $result_ay = static::getByMeta('SKU', $sku, 'product', $post_status);
    
        if (empty($result_ay)){
            return;
        }
    
        return $result_ay[0]['ID'];
    }    

    static function getIdBySKU($sku, $post_status = null){
        $pid = wc_get_product_id_by_sku($sku);

        if ($pid != null){
            return $pid;
        }

        $result_ay = static::getByMeta('SKU', $sku, 'product', $post_status);

        if (empty($result_ay)){
            return;
        }

        return $result_ay[0]['ID'];
    }

    static function getPrice($p){
        $p = static::getProduct($p);

        return $p->get_price();
    }

    /*
        Basado en 

        https://stackoverflow.com/a/51940564/980631
    */
    static function setStock($product_id, $qty)
    {
        $stock_staus = $qty > 0 ? 'instock' : 'outofstock';

        // 1. Updating the stock quantity
        update_post_meta($product_id, '_stock', $qty);

        // 2. Updating the stock quantity
        update_post_meta( $product_id, '_stock_status', wc_clean( $stock_staus ) );

        // 3. Updating post term relationship
        wp_set_post_terms( $product_id, $stock_staus, 'product_visibility', true );

        // And finally (optionally if needed)
        wc_delete_product_transients( $product_id ); // Clear/refresh the variation cache
    }

    /*
        Setea cantidades de 9999 para todos los productos a fines de poder hacer pruebas
    */
    static function setHighAvailability($pid = null){
        if ($pid == null){
            $pids = Products::getIDs();
        } else {
            $pids = [ $pid ];
        }

        foreach($pids as $pid){
            Products::setStock($pid, 9999);
        }   
    }

    static function setProductStatus($product, $status){
        $product = static::getProduct($product);

        // Status ('publish', 'pending', 'draft' or 'trash')
        if (in_array($status, ['publish', 'pending', 'draft', 'trash'])){
            $product->set_status($status);
            $product->save();
        } else {
            throw new \InvalidArgumentException("Estado '$status' invalido.");
        }
    }

    static function setAsDraft($pid){
        static::setProductStatus($pid, 'draft');
    }

    static function setAsPublish($pid){
        static::setProductStatus($pid, 'publish');
    }

    static function restoreBySKU($sku){
        $pid = static::getProductIdBySKU($sku);
        return static::setStatus($pid, 'publish');
    }

    static function trashBySKU($sku){
        $pid = static::getProductIdBySKU($sku);
        return static::setStatus($pid, 'trash');
    }

    static function getAttributeValuesByProduct($product, $attr_name){
        $product = Products::getProduct($product);

        if ($product === null){
            throw new \Exception("Producto no puede ser nulo");
        }

        if ($product === false){
            throw new \Exception("Producto no encontrado");
        }

        return $product->get_attribute($attr_name);
    }

    /*
        Generalizada del filtro Ajax de tallas para cliente peruano
        con productos variables
    */
    function getAttributeValuesByCategory($catego, $attr_name){
        global $config;
        
        if (empty($catego)){
            throw new \InvalidArgumentException("Category can not be avoided");
        }

        if (isset($config['cache_expires_in'])){
            $cached = get_transient("$attr_name-$catego");
            
            if ($cached != null){
                return $cached;
            }
        }
    
        $valores = [];
    
        // WC_Product_Variable[]
        $products = static::getProductsByCategoryName($catego);
    
        foreach ($products as $p){
            // id, slug, name
            $p_valores = static::getAttributeValuesByProduct($p, $attr_name);
            
            foreach ($p_valores as $pt){
                $id = $pt['id'];
    
                if (!isset($valores[$id])){
                    $valores[$id] = [
                        'slug' => $pt['slug'],
                        'name' => $pt['name']
                    ];
                }
            }
        }
    
        if (isset($config['cache_expires_in'])){
            set_transient("{$attr_name}-$catego", $valores, $config['cache_expires_in']);
        }        
    
        return $valores;
    }


    /*
        Get Metadata by Product Id (pid)

        Ej:

        Products::getMetaByPid($pid, static::$cat_metakey)

        Salida:

        array (
            0 =>
            (object) array(
                'term_id' => '20',
                'name' => 'Medicamentos',
                'slug' => 'medicamentos',
                'term_group' => '0',
                'term_taxonomy_id' => '20',
                'taxonomy' => static::$cat_metakey,
                'description' => '',
                'parent' => '0',
                'count' => '1389',
            ),
            1 =>
            (object) array(
                'term_id' => '34',
                'name' => 'Antialérgicos antihistamínicos',
                'slug' => 'antialergicos-antihistaminicos',
                'term_group' => '0',
                'term_taxonomy_id' => '34',
                'taxonomy' => static::$cat_metakey,
                'description' => '',
                'parent' => '0',
                'count' => '93',
            ),
            // ...
        )
    */
    static function getMetaByPid($pid, $taxonomy = null){
		global $wpdb;

		$pid = (int) $pid;

        if ($taxonomy != null){
            $and_taxonomy = "AND taxonomy = '$taxonomy'";
        }

		$sql = "SELECT T.*, TT.* FROM {$wpdb->prefix}term_relationships as TR 
		INNER JOIN `{$wpdb->prefix}term_taxonomy` as TT ON TR.term_taxonomy_id = TT.term_id  
		INNER JOIN `{$wpdb->prefix}terms` as T ON  TT.term_taxonomy_id = T.term_id
		WHERE 1=1 $and_taxonomy AND TR.object_id='$pid'";

		return $wpdb->get_results($sql);
	}
    
    // gets featured image
    static function getImage($product, $size = 'woocommerce_thumbnail', $attr = [], $placeholder = true){
        $p =  is_object($product) ? $product : static::getProduct($product);

        $image = $p->get_image($size, $attr, $placeholder);

        $src = Strings::match($image, '/< *img[^>]*src *= *["\']?([^"\']*)/i');
        return $src;
    }

    static function hasFeatureImage($product){
        $src = static::getImage($product);

        return !Strings::endsWith('/placeholder.png', $src);
    }

    static function getTagsByPid($pid){
		global $wpdb;

		$pid = (int) $pid;

		$sql = "SELECT T.name, T.slug FROM {$wpdb->prefix}term_relationships as TR 
		INNER JOIN `{$wpdb->prefix}term_taxonomy` as TT ON TR.term_taxonomy_id = TT.term_id  
		INNER JOIN `{$wpdb->prefix}terms` as T ON  TT.term_taxonomy_id = T.term_id
		WHERE taxonomy = 'product_tag' AND TR.object_id='$pid'";

		return $wpdb->get_results($sql);
	}
    
    // ok
    static function updateProductTypeByProductId($pid, $new_type){
        $types = ['simple', 'variable', 'grouped', 'external'];
    
        if (!in_array($new_type, $types)){
            throw new \Exception("Invalid product type $new_type");
        }
    
        // Get the correct product classname from the new product type
        $product_classname = \WC_Product_Factory::get_product_classname( $pid, $new_type );
    
        // Get the new product object from the correct classname
        $new_product       = new $product_classname( $pid );
    
        // Save product to database and sync caches
        $new_product->save();
    
        return $new_product;
    }
    

    /**
     * Method to delete Woo Product
     * 
     * $force true to permanently delete product, false to move to trash.
     * 
     */
    static function deleteProduct($id, $force = false)
    {
        $product = wc_get_product($id);

        if(empty($product))
            return new \WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

        // If we're forcing, then delete permanently.
        if ($force)
        {
            if ($product->is_type('variable'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->delete(true);
                }
            }
            elseif ($product->is_type('grouped'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->set_parent_id(0);
                    $child->save();
                }
            }

            $product->delete(true);
            $result = $product->get_id() > 0 ? false : true;
        }
        else
        {
            $product->delete();
            $result = 'trash' === $product->get_status();
        }

        if (!$result)
        {
            return new \WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
        }

        // Delete parent product transients.
        if ($parent_id = wp_get_post_parent_id($id))
        {
            wc_delete_product_transients($parent_id);
        }
        return true;
    }

    static function deleteProductBySKU($sku, bool $permanent = false){
        $pid = static::getProductIdBySKU($sku);
		static::deleteProduct($pid, $permanent);
    }

    static function deleteLastProduct($force = false){
        $pid = static::getLastID();
        static::deleteProduct($pid, $force);
    }

    static function deleteAllProducts(){
        global $wpdb;

        $prefix = $wpdb->prefix;

        $wpdb->query("DELETE FROM {$prefix}terms WHERE term_id IN (SELECT term_id FROM {$prefix}term_taxonomy WHERE taxonomy LIKE 'pa_%')");
        $wpdb->query("DELETE FROM {$prefix}term_taxonomy WHERE taxonomy LIKE 'pa_%'");
        $wpdb->query("DELETE FROM {$prefix}term_relationships WHERE term_taxonomy_id not IN (SELECT term_taxonomy_id FROM {$prefix}term_taxonomy)");
        $wpdb->query("DELETE FROM {$prefix}term_relationships WHERE object_id IN (SELECT ID FROM {$prefix}posts WHERE post_type IN ('product','product_variation'))");
        $wpdb->query("DELETE FROM {$prefix}postmeta WHERE post_id IN (SELECT ID FROM {$prefix}posts WHERE post_type IN ('product','product_variation'))");
        $wpdb->query("DELETE FROM {$prefix}posts WHERE post_type IN ('product','product_variation')");
        $wpdb->query("DELETE pm FROM {$prefix}postmeta pm LEFT JOIN {$prefix}posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");
    } 

    static function getFirst($qty = 1, $type = 'product'){
        global $wpdb;

        if (empty($qty) || $qty < 0){
            throw new \InvalidArgumentException("Quantity can not be 0 or null or negative");
        }

        $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type IN ('$type') ORDER BY ID DESC LIMIT $qty";

        $res     = $wpdb->get_results($sql, ARRAY_A);
        $res_ids = array_column($res, 'ID');

        return ($qty == 1) ? ($res_ids[0] ?? false) : $res_ids;
    }


    static function getRandomProductIds($qty = 1, $type = 'product'){
        global $wpdb;

        if (empty($qty) || $qty < 0){
            throw new \InvalidArgumentException("Quantity can not be 0 or null or negative");
        }

        $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type IN ('$type') ORDER BY RAND() LIMIT $qty";

        $res = $wpdb->get_results($sql, ARRAY_A);
        return array_column($res, 'ID');
    }

    static function getAttachmentIdFromSrc ($image_src) {
        global $wpdb;

        $query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
        $id = $wpdb->get_var($query);
        return $id;    
    }

    static function getFeaturedImageId($product){
        if (!is_object($product) && is_numeric($product)){
            $product = wc_get_product($product);    
        }

        if (empty($product)){
            return;
        }

        $image_id  = $product->get_image_id();

        if ($image_id == ''){
            $image_id = null;
        }    

        return $image_id;
    }

    static function getFeaturedImage($product, $size = 'thumbnail'){
        if ($size != 'thumbnail' && $size != 'full'){
            throw new \InvalidArgumentException("Size parameter value is incorrect");
        }

        $image_id = static::getFeaturedImageId($product);
        
        if (empty($image_id)){
            return;
        }

        return wp_get_attachment_image_url( $image_id, $size);
    }

    /*
        Otra implementación:

        https://wpsimplehacks.com/how-to-automatically-delete-woocommerce-images/
    */
    static function deleteGaleryImages($pid)
    {
        // Delete Attachments from Post ID $pid
        $attachments = get_posts(
            array(
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'post_parent'    => $pid,
            )
        );

        foreach ($attachments as $attachment) {
            wp_delete_attachment($attachment->ID, true);
        }        
    }

    static function deleteAllGaleryImages()
    {
        global $wpdb;

        $wpdb->query("DELETE FROM `{$wpdb->prefix}posts` WHERE `post_type` = \"attachment\";");
        $wpdb->query("DELETE FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` = \"_wp_attached_file\";");
        $wpdb->query("DELETE FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` = \"_wp_attachment_metadata\";");
    }       


    /*
        Otra implentación:

        https://wordpress.stackexchange.com/questions/64313/add-image-to-media-library-from-url-in-uploads-directory
    */
    static function uploadImage($imageurl, $title = '', $alt = '', $caption = '')
    {
        if (empty($imageurl)){
            return;
        }

        if (strlen($imageurl) < 10 || !Strings::startsWith('http', $imageurl)){
            throw new \InvalidArgumentException("Image url '$imageurl' is not valid");
        }

        $att_id = static::getAttachmentIdFromSrc($imageurl);
        if ( $att_id !== null){
            return $att_id;
        }

        $size = getimagesize($imageurl)['mime'];
        $f_sz = explode('/', $size);
        $imagetype = end($f_sz);
        $uniq_name = date('dmY').''.(int) microtime(true); 
        $filename = $uniq_name.'.'.$imagetype;

        $uploaddir  = wp_upload_dir();
        $uploadfile = $uploaddir['path'] . '/' . $filename;

        // mejor,
        // Files::file_get_contents_curl($imageurl)
        $contents   = file_get_contents($imageurl);

        if (empty($contents)){
            return;
        }

        $savefile = fopen($uploadfile, 'w');
        $bytes    = fwrite($savefile, $contents);
        fclose($savefile);

        if (empty($bytes)){
            return;
        }

        $wp_filetype = wp_check_filetype(basename($filename), null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => $filename,
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $imageurl,
            'title'          => $title,
            'alt'            => $alt,
            'caption'        => $caption
        );

        $att_id = wp_insert_attachment( $attachment, $uploadfile );

        if (empty($att_id)){
            return;
        }

        $imagenew = get_post( $att_id );
        $fullsizepath = get_attached_file( $imagenew->ID );
        $attach_data = wp_generate_attachment_metadata( $att_id, $fullsizepath );
        wp_update_attachment_metadata( $att_id, $attach_data ); 

        return $att_id;
    }

    /*
        Todos los atributos "nativos" parecen comenzar con "_". Ej:
        
        para tax_class es _tax_class
        para thumbnail_id es _thumbnail_id
        etc
    */  
    static function setPostAttribute($pid, $key, $value){
        update_post_meta($pid, $key, $value);
    }
    
    static function setDefaultImage($pid, $image_id){
        //dd("Updating default image for post with PID $pid");
        update_post_meta( $pid, '_thumbnail_id', $image_id );
    }

    static function setImagesForPost($pid, Array $image_ids){
        //dd("Updating images for post with PID $pid");
        $image_ids = implode(",", $image_ids);
        update_post_meta($pid, '_product_image_gallery', $image_ids);
    }

    static function getProductCategoryNames($pid){
        return wp_get_post_terms( $pid, static::$cat_metakey, array('fields' => 'names') );
    }

    /*
        Sobre-escribe cualquier categoria previa
    */
    static function setProductCategoryNames($pid, Array $categos){
        if (count($categos) >0 && is_array($categos[0])){
            throw new \InvalidArgumentException("Categorias no pueden ser array de array");
        }

        wp_set_object_terms($pid, $categos, static::$cat_metakey);
    }

    /*
        Agrega nuevas categorias
    */
    static function addProductCategoryNames($pid, Array $categos){
        $current_categos = static::getProductCategoryNames($pid);

        if (!empty($categos)){
            $current_categos = array_diff($current_categos, ['Uncategorized']);
        }

        $categos = array_merge($current_categos, $categos);

        static::setProductCategoryNames($pid, $categos);
    }
    
    static function setProductTagNames($pid, Array $names){
        if (count($names) >0 && is_array($names[0])){
            //dd($names, 'TAGS');
            throw new \InvalidArgumentException("Categorias no pueden ser array de array");
        }

        wp_set_object_terms($pid, $names, 'product_tag');
    }

    static function updatePrice($pid, $price){
        update_post_meta($pid, '_price', $price);
        update_post_meta($pid, '_regular_price', $price );
    }

    static function updateSalePrice($pid, $sale_price){
        update_post_meta($pid, '_sale_price', $sale_price );
    }

    static function updateStock($product, $qty){      
        $product = static::getProduct($product);  

        if ($product === null){
            return;
        }

        $product->set_stock_quantity($qty);

        if ($qty < 1){
            $status = 'outofstock';
        } else {
            $status = 'instock';
        }

        $product->set_stock_status($status);
        $product->save();
    }


    static function updateProductBySku( $args, $update_images_even_it_has_featured_image = true )
    {
        if (!isset($args['sku']) || empty($args['sku'])){
            throw new \InvalidArgumentException("SKU es requerido");
        }

        $pid = static::getProductIdBySKU($args['sku']);

        if (empty($pid)){
            throw new \InvalidArgumentException("SKU {$args['sku']} no encontrado");
        }

        $product = wc_get_product($pid);

        // Si hay cambio de tipo de producto, lo actualizo
        if ($product->get_type() != $args['type']){
            self::updateProductTypeByProductId($pid, $args['type']);
        }

        // Product name (Title) and slug
        if (isset($args['name'])){
            $product->set_name( $args['name'] ); 
        }
           
        // Description and short description:
        if (isset($args['description'])){
            $product->set_description($args['description']);
        }

        if (isset($args['short_description'])){
            $product->set_short_description( $args['short_description'] ?? '');
        }

        // Status ('publish', 'pending', 'draft' or 'trash')
        if (isset($args['status'])){
            $product->set_status($args['status']);
        }

        // Featured (boolean)
        if (isset($args['featured'])){
            $product->set_featured($args['featured']);
        }        

        // Visibility ('hidden', 'visible', 'search' or 'catalog')
        if (isset($args['visibility'])){
            $product->set_catalog_visibility($args['visibility']);
        }

        // Virtual (boolean)
        if (isset($args['virtual'])){
            $product->set_virtual($args['virtual']);
        }        

        // Prices

        $price = $args['regular_price'] ?? $args['price'] ?? null;

        if ($price != null){
            $product->set_regular_price($price);
        }

        if (isset($args['sale_price'])){
            $product->set_sale_price($args['sale_price']);
        }
        
        if( isset($args['sale_from'])){
            $product->set_date_on_sale_from($args['sale_from']);
        }

        if( isset($args['sale_to'])){
            $product->set_date_on_sale_to($args['sale_to']);
        }
        
        // Downloadable (boolean)
        $product->set_downloadable(  isset($args['downloadable']) ? $args['downloadable'] : false );
        if( isset($args['downloadable']) && $args['downloadable'] ) {
            $product->set_downloads(  isset($args['downloads']) ? $args['downloads'] : array() );
            $product->set_download_limit(  isset($args['download_limit']) ? $args['download_limit'] : '-1' );
            $product->set_download_expiry(  isset($args['download_expiry']) ? $args['download_expiry'] : '-1' );
        }

        // Taxes
        if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
            if (isset($args['tax_status'])){
                $product->set_tax_status($args['tax_status']);
            }
            
            if (isset($args['tax_class'])){
                $product->set_tax_class($args['tax_class']);
            }            
        }

        $args['virtual'] = $args['virtual'] ?? false;

        // SKU and Stock (Not a virtual product)
        if( ! $args['virtual'] ) {

            // SKU
            if (isset($args['sku'])){
                $product->set_sku($args['sku']);
            }        

            $product->set_manage_stock( isset( $args['manage_stock'] ) ? $args['manage_stock'] : false );

            if (isset($args['stock_status'])){
                $product->set_stock_status($args['stock_status']);
            } elseif (isset($args['is_in_stock'])){
                $product->set_stock_status($args['is_in_stock']);
            } else {
                $product->set_stock_status('instock');        
            }
            
            if( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
                $product->set_stock_quantity( $args['stock_quantity'] );
                $product->set_backorders( isset( $args['backorders'] ) ? $args['backorders'] : 'no' ); // 'yes', 'no' or 'notify'
            }
        }

        // Sold Individually
        if (isset($args['sold_individually'])){
            $product->set_sold_individually($args['is_sold_individually'] != 'no');
        }

        // Weight, dimensions and shipping class
        if (isset($args['weight'])){
            $product->set_weight($args['weight']);
        }
        
        if (isset($args['length'])){
            $product->set_length($args['length']);
        }
        
        if (isset($args['width'])){
            $product->set_width($args['width']);
        }
        
        if (isset( $args['height'])){
            $product->set_height($args['height']);
        }        

        /*
        if( isset( $args['shipping_class_id'] ) ){
            $product->set_shipping_class_id( $args['shipping_class_id'] );
        }
        */        

        // Upsell and Cross sell (IDs)
        //$product->set_upsell_ids( isset( $args['upsells'] ) ? $args['upsells'] : '' );
        //$product->set_cross_sell_ids( isset( $args['cross_sells'] ) ? $args['upsells'] : '' );


        // Attributes et default attributes
        
        if( isset( $args['attributes'] ) ){
            $attr = static::insertAttTerms($args['attributes'], ($args['type'] == 'variable'));
            $product->set_attributes($attr);
        }
            
        /*
            'default_attributes' => [
                'pa_talla' => 'l',
            ]
        */
        if( isset($args['default_attributes']) && !empty($args['default_attributes'])){   
            $product->set_default_attributes( $args['default_attributes'] );
        }

        // Reviews, purchase note and menu order
        $product->set_reviews_allowed( isset( $args['reviews'] ) ? $args['reviews'] : false );
        $product->set_purchase_note( isset( $args['note'] ) ? $args['note'] : '' );
        
        if( isset( $args['menu_order'] ) )
            $product->set_menu_order( $args['menu_order'] );

            
        ## --- SAVE PRODUCT --- ##
        $pid = $product->save();

        if (isset($args['stock_status'])){
            update_post_meta( $pid, '_stock_status', wc_clean( $args['stock_status'] ) );
        } 


        // Product categories and Tags
        if( isset( $args['categories'] ) ){
            $names = isset($args['categories'][0]['name']) ? array_column($args['categories'], 'name') : $args['categories'];
            static::setProductCategoryNames($pid, $names);
        }        

        if( isset( $args['tags'] ) ){
            $names = isset($args['tags'][0]['name']) ? array_column($args['tags'], 'name') : $args['tags'];
            static::setProductTagNames($pid, $names);
        }
            

        // Images and Gallery
        
        $current_featured_image = Products::getFeaturedImageId($pid);

        if (empty($current_featured_image) || $update_images_even_it_has_featured_image){
            $images = $args['gallery_images'] ?? $args['images'] ?? [];
            
            if ($images >0){
                $att_ids = [];
                foreach ($images as $img){
                    $img_url = is_array($img) ? $img[0] : $img;
                    $att_id  = static::uploadImage($img_url);

                    if (!empty($att_id)){
                        $att_ids[] = $att_id;
                    }                
                }

                static::setImagesForPost($pid, $att_ids); 
                static::setDefaultImage($pid, $att_ids[0]);        
            } elseif (isset($args['image'])) {
                $img = is_array($args['image']) ? $args['image'][0] : $args['image'];

                if (!empty($img)){
                    $att_id = static::uploadImage($img);
                    if (!empty($att_id)){
                        static::setImagesForPost($pid, [$att_id]); 
                        static::setDefaultImage($pid, $att_id);
                    }     
                }
            }
        }


        if ($args['type'] == 'variable'){
            $variation_ids = $product->get_children();
            //dd($variation_ids, 'V_IDS');
            
            // elimino variaciones para volver a crearlas
            foreach ($variation_ids as $vid){
                wp_delete_post($vid, true);
            }

            if (isset($args['variations'])){
                foreach ($args['variations'] as $variation){
                    $var_id = static::addVariation($pid, $variation);                    
                }      
            }  
        }
    }

    /*
        Use *after* post creation

        @param $pid product id
        @param array[] $attributes - This needs to be an array containing *ALL* your attributes so it can insert them in one go

        Ex.

        array (
            'Laboratorio' => 'Mintlab',
            'Enfermedades' => '',
            'Bioequivalente' => '',
            'Principio activo' => 'Cafeína|Clorfenamina|Ergotamina|Metamizol',
            'Forma farmacéutica' => 'Comprimidos',
            'Control de Stock' => 'Disponible',
            'Otros medicamentos' => 'Fredol|Migragesic|Ultrimin|Migratan|Cefalmin|Cinabel|Migranol|Migra-Nefersil|Tapsin m|Sevedol',
            'Dosis' => '100/4/1/300 mg',
            'Código ISP' => 'F-9932/16',
            'Es medicamento' => 'Si',
            'Mostrar descripción' => 'No',
            'Precio por fracción' => '99',
            'Precio por 100 ml o 100 G' => '',
            'Requiere receta' => 'Si',
        )

        Creo estos son los atributos no-reusables

        Viejo nombre: createProductAttributesForSimpleProducs
    */
    static function setProductAttributesForSimpleProducts($pid, Array $attributes){
        $i = 0;

        if (empty($attributes)){
            return;
        }

        // Loop through the attributes array
        foreach ($attributes as $name => $value) {
            $product_attributes[$i] = array (
                'name' => htmlspecialchars( stripslashes( $name ) ), // set attribute name
                'value' => $value, // set attribute value
                'position' => 1,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 0
            );

            $i++;
        }

        if (empty($product_attributes)){
            return;
        }

        // Now update the post with its new attributes
        update_post_meta($pid, '_product_attributes', $product_attributes);
    }

    /*
        Forma de uso:

        Products::addAttributeForSimpleProducts($pid, 'vel', '80');
    */
    static function addAttributeForSimpleProducts($pid, $key, $val){
        /*
            array (
                0 =>
                array (
                    'name' => 'stock',
                    'value' => 'out of stock',
                    'position' => 1,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 0,
                ),
            ), ..
        */
        
        $_attrs = Products::getCustomAttr($pid);
        $attrs  = [];
    
        foreach($_attrs as $att){
            $attrs[ $att['name'] ] = $att['value'];
        }
    
        $attrs[ $key ] = $val;
    
        Products::setProductAttributesForSimpleProducts($pid, $attrs);
    }
    
    /*
        Forma de uso:

        Products::addAttributesForSimpleProducts($pid, [
            'fuerza' => 45,
            'edad' => 29
        ]);
    */
    static function addAttributesForSimpleProducts($pid, Array $attributes)
    {
        if (!Arrays::is_assoc($attributes)){
            throw new \InvalidArgumentException("El Array de atributos debe ser asociativo");
        }

        $_attrs = Products::getCustomAttr($pid);
        $attrs  = [];
    
        foreach($_attrs as $att){
            $attrs[ $att['name'] ] = $att['value'];
        }

        /*
            Nuevos atributos
        */
        foreach ($attributes as $key => $val){
            $attrs[ $key ] = $val;
        }
    
        Products::setProductAttributesForSimpleProducts($pid, $attrs);
    }

    // alias
    static function updateProductAttributesForSimpleProducts($pid, Array $att){
        static::addAttributesForSimpleProducts($pid, $att);
    }   

    static function removeAllAttributesForSimpleProducts($pid){
        update_post_meta($pid, '_product_attributes', []);
    }

    /*`
        Inserta terminos en atributos re-utilizables

        Pre-cond: los atributos deben existir.

        Nota: antes se llamaba createProductAttributes()

        https://gist.github.com/alphasider/b9916b51083c48466f330ab0006328e6

        array(
            // Taxonomy and term name values
            'pa_color' => array(
                'term_names' => array('Red', 'Blue'),
                'is_visible' => true,
                'for_variation' => false,
            ),
            'pa_size' =>  array(
                'term_names' => array('X Large'),
                'is_visible' => true,
                'for_variation' => false,
            ),
        )

        It works even for Simple products althought them they can not be used


        Los terminos son insertados en la tabla `wp_terms` 
    */
    static function insertAttTerms(Array $attributes, bool $for_variation){

        $data = array();
        $position = 0;

        foreach( $attributes as $_taxonomy => $values ){
            $taxonomy = str_replace('pa_', '', $_taxonomy);
            $taxonomy = 'pa_'. $taxonomy;

            if( ! taxonomy_exists( $taxonomy ) )
                continue;

            // Get an instance of the WC_Product_Attribute Object
            $attribute = new \WC_Product_Attribute();

            $term_ids = array();

            if (isset($values['is_visible'])){
                $visibility = $values['is_visible'];
            }

            if (isset($values['term_names'])){
                $values = $values['term_names'];
            }

            // Loop through the term names
            foreach( $values as $term_name ){
                if ($term_name == ''){
                    continue; //*
                }

                if (is_array($term_name)){
                    //dd($term_name, '$term_name');
                    throw new \Exception("\$term_name debe ser un string");
                }

                if( term_exists( $term_name, $taxonomy ) ){
                    // Get and set the term ID in the array from the term name
                    $term_ids[] = get_term_by( 'name', $term_name, $taxonomy )->term_id;
                }else{
                    $term_data = wp_insert_term( $term_name, $taxonomy );
                    $term_ids[]   = $term_data['term_id'];
                    //continue;
                }    
            }

            $taxonomy_id = wc_attribute_taxonomy_id_by_name( $taxonomy ); // Get taxonomy ID

            $attribute->set_id( $taxonomy_id );
            $attribute->set_name( $taxonomy );
            $attribute->set_options( $term_ids );
            $attribute->set_position( $position );

            if (isset($visibility)){
                $attribute->set_visible( $visibility );
            }            
            
            $attribute->set_variation($for_variation);

            $data[$taxonomy] = $attribute; // Set in an array

            $position++; // Increase position
        }

        return $data;
    }

    /*
        Para cada atributo no-reusable extrae la diferencia

        Ej: 

        $a = array (
            array (
            'name' => 'Laboratorio',
            'value' => 'UnLab2',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            ),

            array (
            'name' => 'Enfermedades',
            'value' => 'Pestesssss',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            ),
            array (
            'name' => 'Bioequivalente',
            'value' => 'Otrox',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            )
        );

        $b = array (
            array (
            'name' => 'Laboratorio',
            'value' => 'UnLab2',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            ),

            array (
            'name' => 'Enfermedades',
            'value' => 'SIDA|Herpes',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            ),
            array (
            'name' => 'Bioequivalente',
            'value' => 'Otrox|NuevoMed',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
            )
        );

        Salida:

        Array
        (
            [Enfermedades] => Array
            (
                    [prev] => Pestesssss
                    [current] => SIDA|Herpes
            )

            [Bioequivalente] => Array
            (
                    [prev] => Otrox
                    [current] => Otrox|NuevoMed
            )
        )

    */
    function termDiff(Array $prev, Array $current){
        $dif = [];
        foreach ($prev as $ix => $at) {
            $name  = $at['name'];
            $val_p = $prev[$ix]['value'];
            $val_c = $current[$ix]['value'];

            if ($val_p !== $val_c){
                $dif[$name] = [
                    'prev'    => $val_p,
                    'current' => $val_c 
                ];
            }
        }

        return $dif;
    }


    // Custom function for product creation (For Woocommerce 3+ only)
    static function createProduct($args, $create_attributes_for_simple_products = false)
    {
        if (isset($args['sku']) && !empty($args['sku']) && !empty(static::getProductIdBySKU($args['sku']))){
            throw new \InvalidArgumentException("SKU {$args['sku']} ya está en uso.");
        }

        // Get an empty instance of the product object (defining it's type)
        $product = static::createProductByObjectType( $args['type'] );
        if( ! $product )
            return false;

        if (isset($args['product_url'])){
            $product->set_product_url($args['product_url']);
        }

        // Product name (Title) and slug
        $product->set_name( $args['name'] ); // Name (title).
    
        // Description and short description:
        $product->set_description( $args['description'] ?? '' );
        $product->set_short_description( $args['short_description'] ?? '');

        // Status ('publish', 'pending', 'draft' or 'trash')
        $product->set_status( isset($args['status']) ? $args['status'] : 'publish' );

        // Featured (boolean)
        $product->set_featured(  isset($args['featured']) ? $args['featured'] : false );

        // Visibility ('hidden', 'visible', 'search' or 'catalog')
        $product->set_catalog_visibility( isset($args['visibility']) ? $args['visibility'] : 'visible' );

        // Virtual (boolean)
        $product->set_virtual( isset($args['virtual']) ? $args['virtual'] : false );

        // Prices

        $price = $args['regular_price'] ?? $args['price'] ?? null;

        if ($price !== null){
            $product->set_regular_price($price);
        }

        if (isset($args['sale_price'])){
            $product->set_sale_price($args['sale_price']);
        }
        
        if( isset($args['sale_from'])){
            $product->set_date_on_sale_from($args['sale_from']);
        }

        if( isset($args['sale_to'])){
            $product->set_date_on_sale_to($args['sale_to']);
        }
        
        // Downloadable (boolean)
        $product->set_downloadable(  isset($args['downloadable']) ? $args['downloadable'] : false );
        if( isset($args['downloadable']) && $args['downloadable'] ) {
            $product->set_downloads(  isset($args['downloads']) ? $args['downloads'] : array() );
            $product->set_download_limit(  isset($args['download_limit']) ? $args['download_limit'] : '-1' );
            $product->set_download_expiry(  isset($args['download_expiry']) ? $args['download_expiry'] : '-1' );
        }

        // Taxes
        if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
            $product->set_tax_status(  isset($args['tax_status']) ? $args['tax_status'] : 'taxable' );
            $product->set_tax_class(  isset($args['tax_class']) ? $args['tax_class'] : '' );
        }

        $args['virtual'] = $args['virtual'] ?? false;

        // SKU and Stock (Not a virtual product)
        if( ! $args['virtual'] ) {

            // SKU
            if (isset($args['sku'])){
                $product->set_sku($args['sku']);
            }        

            $product->set_manage_stock( isset( $args['manage_stock'] ) ? $args['manage_stock'] : false );

            if (isset($args['stock_status'])){
                $product->set_stock_status($args['stock_status']);
            } elseif (isset($args['is_in_stock'])){
                $product->set_stock_status($args['is_in_stock']);
            } else {
                $product->set_stock_status('instock');        
            }
            
            if( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
                $product->set_stock_quantity( $args['stock_quantity'] );
                $product->set_backorders( isset( $args['backorders'] ) ? $args['backorders'] : 'no' ); // 'yes', 'no' or 'notify'
            }
        }

        // Sold Individually
        if (isset($args['sold_individually'])){
            $product->set_sold_individually($args['is_sold_individually'] != 'no');
        }

        // Weight, dimensions and shipping class
        $product->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );
        $product->set_length( isset( $args['length'] ) ? $args['length'] : '' );
        $product->set_width( isset(  $args['width'] ) ?  $args['width']  : '' );
        $product->set_height( isset( $args['height'] ) ? $args['height'] : '' );

        /*
        if( isset( $args['shipping_class_id'] ) ){
            $product->set_shipping_class_id( $args['shipping_class_id'] );
        }
        */        

        // Upsell and Cross sell (IDs)
        //$product->set_upsell_ids( isset( $args['upsells'] ) ? $args['upsells'] : '' );
        //$product->set_cross_sell_ids( isset( $args['cross_sells'] ) ? $args['upsells'] : '' );


        /*
            Attributes et default attributes

            Crear los atributos primero por eficiencia y no para cada producto
        */
        if( isset( $args['attributes'] ) ){     

            if ($args['type'] == 'variable'){
                $attr = static::insertAttTerms($args['attributes'], ($args['type'] == 'variable'));
                $product->set_attributes($attr);
            } elseif($args['type'] == 'simple'){
                if ($create_attributes_for_simple_products){
                    $attr = static::insertAttTerms($args['attributes'], ($args['type'] == 'variable'));
                    $product->set_attributes($attr);
                }
            } 
            
        }
            
        if (isset($args['default_attributes'])){
            $product->set_default_attributes( $args['default_attributes'] ); 
        }

        // Reviews, purchase note and menu order
        $product->set_reviews_allowed( isset( $args['reviews'] ) ? $args['reviews'] : false );
        $product->set_purchase_note( isset( $args['note'] ) ? $args['note'] : '' );
        if( isset( $args['menu_order'] ) )
            $product->set_menu_order( $args['menu_order'] );

            
        ## --- SAVE PRODUCT --- ##
        $pid = $product->save();

        if (isset($args['stock_status'])){
            update_post_meta( $pid, '_stock_status', wc_clean( $args['stock_status'] ) );
        } 

        if (isset($args['category'])){
            $args['categories'] = [ $args['category'] ];
        }

        // Product categories and Tags
        if( isset( $args['categories'] ) ){
            $names = isset($args['categories'][0]['name']) ? array_column($args['categories'], 'name') : $args['categories'];
            static::setProductCategoryNames($pid, $names);
        }        

        if( isset( $args['tags'] ) ){
            $names = isset($args['tags'][0]['name']) ? array_column($args['tags'], 'name') : $args['tags'];
            static::setProductTagNames($pid, $names);
        }


        // if( isset( $args['category_slugs'] ) ){
        //     static::setProductCategorySlugs($pid, $args['category_slugs']);
        // }        

        // if( isset( $args['tag_slugs'] ) ){
        //     static::setProductTagSlugs($pid, $names);
        // }
            

        // Images and Gallery
    
        $att_ids = [];
    
        if (isset($args['image'])) {
            $img = is_array($args['image']) ? $args['image'][0] : $args['image'];
            $att_id1 = static::uploadImage($img);

            if (!empty($att_id1)){
                $att_ids[] = $att_id1;
            }
        }

        $images = $args['gallery_images'] ?? $args['images'] ?? [];
    
        if (count($images) > 0){
            foreach ($images as $img){
                $img_url = is_array($img) ? $img[0] : $img;

                $att_id = static::uploadImage($img_url);

                if (!empty($att_id)){
                    $att_ids[] = $att_id;
                }  
            }        
        } 

        if (count($att_ids) >0){
            static::setImagesForPost($pid, $att_ids); 
            static::setDefaultImage($pid, $att_ids[0]);
        }

        if ($args['type'] == 'variable' && isset($args['variations'])){
            foreach ($args['variations'] as $variation){
                static::addVariation($pid, $variation);
            }     
            
            //@$product->variable_product_sync();
        }

        return $product; //
    }

    static function dumpProduct($product){
        if ($product === null){
            throw new \InvalidArgumentException("Product can not be null");
        }

		$obj = [];
	
		$get_src = function($html) {
			$parsed_img = json_decode(json_encode(simplexml_load_string($html)), true);
			$src = $parsed_img['@attributes']['src']; 
			return $src;
		};

        $_p = $product;

		// Get Product General Info
	  
        if (is_object($product)){
            $pid = $product->get_id();
        } else {
            $pid = $product;
            $product = wc_get_product($pid);
        }

		$obj['id']                 = $pid;;
		$obj['type']               = $product->get_type();
        $obj['product_url']        = ($product instanceof \WC_Product_External) ? $product->get_product_url() : null;  //
		$obj['name']               = $product->get_name();
		$obj['slug']               = $product->get_slug();
		$obj['status']             = $product->get_status();
		$obj['featured']           = $product->get_featured();
		$obj['catalog_visibility'] = $product->get_catalog_visibility();
		$obj['description']        = $product->get_description();
		$obj['short_description']  = $product->get_short_description();
		$obj['sku']                = $product->get_sku();
		#$obj['virtual'] = $product->get_virtual();
		#$obj['permalink'] = get_permalink( $product->get_id() );
		#$obj['menu_order'] = $product->get_menu_order(
		$obj['date_created']       = $product->get_date_created()->date('Y-m-d H:i:s');
		$obj['date_modified']      = $product->get_date_modified()->date('Y-m-d H:i:s');
		
		// Get Product Prices
		
		$obj['price']              = $product->get_price();
		$obj['regular_price']      = $product->get_regular_price();
		$obj['sale_price']         = $product->get_sale_price();
		#$obj['date_on_sale_from'] = $product->get_date_on_sale_from();
		#$obj['date_on_sale_to'] = $product->get_date_on_sale_to();
		#$obj['total_sales'] = $product->get_total_sales();
		
		// Get Product Tax, Shipping & Stock
		
		#$obj['tax_status'] = $product->get_tax_status();
		#$obj['tax_class'] = $product->get_tax_class();
		$obj['manage_stock']      = $product->get_manage_stock();
		$obj['stock_quantity']    = $product->get_stock_quantity();
		$obj['stock_status']      = $product->get_stock_status();
		#$obj['backorders'] = $product->get_backorders();
		$obj['is_sold_individually'] = $product->get_sold_individually();   /// deberia ser     sold_individually
		#$obj['purchase_note'] = $product->get_purchase_note();
		#$obj['shipping_class_id'] = $product->get_shipping_class_id();
		
		// Get Product Dimensions
		
		$obj['weight']           = $product->get_weight();
		$obj['length']           = $product->get_length();
		$obj['width']            = $product->get_width();
		$obj['height']           = $product->get_height();
		
		// Get Linked Products
		
		#$obj['upsell_ids'] = $product->get_upsell_ids();
		#$obj['cross_sell_id'] = $product->get_cross_sell_ids();
		$obj['parent_id']        = $product->get_parent_id();
		
		// Get Product Taxonomies
		
		$obj['tags']             = static::getTagsByPid($pid); /// deberia ser tag_ids


		$obj['categories'] = [];
		$category_ids = $product->get_category_ids(); 
	
		foreach ($category_ids as $cat_id){
			$terms = get_term_by( 'id', $cat_id, static::$cat_metakey );
			$obj['categories'][] = [
				'name' => $terms->name,
				'slug' => $terms->slug,
				'description' => $terms->description
			];
		}
			
		
		// Get Product Downloads
		
		#$obj['downloads'] = $product->get_downloads();
		#$obj['download_expiry'] = $product->get_download_expiry();
		#$obj['downloadable'] = $product->get_downloadable();
		#$obj['download_limit'] = $product->get_download_limit();
		
		// Get Product Images
		
		$obj['image_id'] = $product->get_image_id();
		$obj['image']    =  wp_get_attachment_image_src($product->get_image_id(), 'large');  

		$obj['gallery_image_ids'] = $product->get_gallery_image_ids();
			
		$obj['gallery_images'] = [];
		foreach ($obj['gallery_image_ids'] as $giid){
			$obj['gallery_images'][] = wp_get_attachment_image_src($giid, 'large');
		}	
	
		// Get Product Reviews
		
		#$obj['reviews_allowed'] = $product->get_reviews_allowed();
		#$obj['rating_counts'] = $product->get_rating_counts();
		#$obj['average_rating'] = $product->get_average_rating();
		#$obj['review_count'] = $product->get_review_count();
	
		// Get Product Variations and Attributes

		if($obj['type'] == 'variable'){
			$obj['attributes'] = self::getVariationAttributes($product);
			
			$_default_atts = $product->get_default_attributes();

            $default_atts  = [];
            foreach ($_default_atts as $def_at_ix => $def_at_val){
                $def_at_key = $def_at_ix;

                if (!Strings::startsWith('pa_', $def_at_ix)){
                    $def_at_key = 'pa_' . $def_at_ix;
                }

                $default_atts[$def_at_key] = $def_at_val;
            }

			if (!empty($default_atts)){
				$obj['default_attributes'] = $default_atts;
			}		

			$obj['variations'] = $product->get_available_variations();	

			foreach ($obj['variations'] as $var_ix => $var)
            {	
                /*
                    Necesito que cada atributo sea de la forma

                    array (
                        'attribute_pa_color' => 'marron',
                    ),
                */

                $atts = [];
                foreach($var['attributes'] as $taxonomy_name => $at_val){
                    $key = $taxonomy_name;       
            
                    if (Strings::startsWith('attribute_', $key) && !Strings::after($key, 'attribute_pa_')){
                        $key = 'attribute_pa_' . Strings::after($key, 'attribute_');
                        $atts[$key] = $at_val;
                    }
                }

                $obj['variations'][$var_ix]['attributes'] = $atts;

				if ($var['sku'] == $obj['sku']){
					$obj['variations'][$var_ix]['sku'] = '';
				}
			}
		} else {
			// Simple product

			$atts = $product->get_attributes();

            foreach($atts as $ix => $at){
                if ($at instanceof \WC_Product_Attribute){
                    $at_ay     = $at->get_data();
                    $at_name   = $at['name'];
                    $at_value  = $at['value'];

                    $obj['attributes'][$at_name] = $at_value;
                }
            }
		}		
	
		return $obj;		
	}

    // alias
    static function dd($product){
        return static::dumpProduct($product);
    }

    static function addVariation( $pid, Array $args ){
        
        // Get the Variable product object (parent)
        $product = wc_get_product($pid);

        $variation_post = array(
            'post_title'  => $product->get_name(),
            'post_description' => $args['variation_description'] ?? '',
            'post_name'   => 'product-'.$pid.'-variation',
            'post_status' => isset($args['status']) ? $args['status'] : 'publish',
            'post_parent' => $pid,
            'post_type'   => 'product_variation',
            'guid'        => $product->get_permalink()
        );

        // Creating the product variation
        $variation_id = wp_insert_post( $variation_post );

        // Get an instance of the WC_Product_Variation object
        $variation = new \WC_Product_Variation( $variation_id );

        
        // Description and short description:
        $variation->set_description($args['variation_description']);

        if( isset( $args['attributes'] ) ){
            // Iterating through the variations attributes
            foreach ($args['attributes'] as $attribute => $term_name )
            {
                if ($term_name == ''){
                    continue; //
                }

                $taxonomy = str_replace('attribute_pa_', '', $attribute);
                $taxonomy = str_replace('pa_', '', $taxonomy);
                $taxonomy = 'pa_'.$taxonomy; // The attribute taxonomy

                // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
                if( ! taxonomy_exists( $taxonomy ) ){
                    register_taxonomy(
                        $taxonomy,
                    'product_variation',
                        array(
                            'hierarchical' => false,
                            'label' => ucfirst( $attribute ),
                            'query_var' => true,
                            'rewrite' => array( 'slug' => sanitize_title($attribute) ), // The base slug
                        ),
                    );
                }

                if (is_array($term_name)){
                    //dd($term_name, '$term_name');
                    throw new \Exception("\$term_name debe ser un string");
                }

                // Check if the Term name exist and if not we create it.
                if( ! term_exists( $term_name, $taxonomy ) )
                    wp_insert_term( $term_name, $taxonomy ); // Create the term

                $term_slug = get_term_by('name', $term_name, $taxonomy )->slug; // Get the term slug

                // Get the post Terms names from the parent variable product.
                $post_term_names =  wp_get_post_terms( $pid, $taxonomy, array('fields' => 'names') );

                // Check if the post term exist and if not we set it in the parent variable product.
                if( ! in_array( $term_name, $post_term_names ) )
                    wp_set_post_terms( $pid, $term_name, $taxonomy, true );

                // Set/save the attribute data in the product variation
                update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );

            }
        }

        // SKU
        if (isset($args['sku'])){
            $variation->set_sku($args['sku']);
        }

        // Prices
        if (isset($args['display_regular_price'])){
            $variation->set_regular_price( $args['display_regular_price'] );
        }

        if (isset($args['display_price'])){
            $variation->set_sale_price($args['display_price']);
        }
        
        if( isset($args['sale_from'])){
            $variation->set_date_on_sale_from($args['sale_from']);
        }

        if( isset($args['sale_to'])){
            $variation->set_date_on_sale_to($args['sale_to']);
        }

        // Sold Individually
        if (isset($args['sold_individually'])){
            $variation->set_sold_individually($args['is_sold_individually'] != 'no');
        }

        // Weight, dimensions and shipping class
        $variation->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );

        // Stock    
        if (isset($args['stock_status'])){
            $variation->set_stock_status($args['stock_status']);
        } elseif (isset($args['is_in_stock'])){
            $variation->set_stock_status($args['is_in_stock']);
        } else {
            $variation->set_stock_status('instock');        
        }

        if ($args['max_qty'] != '' && $args['max_qty'] != null){
            $variation->set_stock_quantity( $args['max_qty'] );
            $variation->set_manage_stock(true);
        } else {
            $variation->set_manage_stock(false);
        }


        // Image por variation if any

        if (isset($args['image'])){
            $url = $args['image']['full_src'] ?? $args['image']['url'] ?? null;
            $title = $args['image']['title'] ?? '';
            $alt = $args['image']['alt'] ?? '';
            $caption = $args['image']['caption'] ?? '';

            $att_id = static::uploadImage($url, $title, $alt, $caption );
            
            if (!empty($att_id)){
                static::setImagesForPost($variation_id, [$att_id]); 
                static::setDefaultImage($variation_id, $att_id);  
            }
        }

        $variation->save();
        
        // agrega la variación al producto
        $product = wc_get_product($pid);
        $product->save();

        return $variation_id;
    }

    // Utility function that returns the correct product object instance
    static function createProductByObjectType( $type = 'simple') {
        // Get an instance of the WC_Product object (depending on his type)
        if($type === 'variable' ){
            $product = new \WC_Product_Variable();
        } elseif($type === 'grouped' ){
            $product = new \WC_Product_Grouped();
        } elseif($type === 'external' ){
            $product = new \WC_Product_External();
        } elseif($type === 'simple' )  {
            $product = new \WC_Product_Simple(); 
        } 
        
        if( ! is_a( $product, 'WC_Product' ) )
            return false;
        else
            return $product;
    }

    /*
		$product es el objeto producto
		$taxonomy es opcional y es algo como '`pa_talla`'
	*/
	static function getVariationAttributes($product, $taxonomy = null){
		$attr = [];

		if ( $product->get_type() == 'variable' ) {
			foreach ($product->get_available_variations() as $values) {
				foreach ( $values['attributes'] as $attr_variation => $term_slug ) {
					if (!isset($attr[$attr_variation])){
						$attr[$attr_variation] = [];
					}

					if ($taxonomy != null){
						if( $attr_variation === 'attribute_' . $taxonomy ){
							if (!in_array($term_slug, $attr[$attr_variation])){
								$attr[$attr_variation][] = $term_slug;
							}                        
						}
					} else {
						if (!in_array($term_slug, $attr[$attr_variation])){
							$attr[$attr_variation][] = $term_slug;
						} 
					}

				}
			}
		}

		$arr = [];
		foreach ($attr as $taxonomy_name => $ar){            
            $key = Strings::after($taxonomy_name, 'attribute_');
            
            if (!Strings::startsWith('pa_', $key)){
                $key = 'pa_' . $key;
            }
            
            foreach ($ar as $e){
				$arr[$key]['term_names'][] = $e;
			}

			$arr[$key]['is_visible'] = true; 
		}

		/*
			array(
				// Taxonomy and term name values
				'pa_color' => array(
					'term_names' => array('Red', 'Blue'),
					'is_visible' => true,
					'for_variation' => false,
				),
				'pa_tall' =>  array(
					'term_names' => array('X Large'),
					'is_visible' => true,
					'for_variation' => false,
				),
			),
  		*/
		return $arr;
	}


    /*
        Obtener todos los custom attributes disponibles para productos variables

        Salida:

        array (
            'id:18' => 'att_prueba',
            'id:14' => 'bioequivalente',
            'id:3' => 'codigo_isp',
            'id:15' => 'control_de_stock',
            'id:2' => 'dosis',
            'id:8' => 'enfermedades',
            'id:10' => 'es_medicamento',
            'id:13' => 'forma_farmaceutica',
            'id:5' => 'laboratorio',
            'id:9' => 'mostrar_descr',
            'id:11' => 'otros_medicamentos',
            'id:17' => 'precio_easyfarma_plus',
            'id:7' => 'precio_fraccion',
            'id:6' => 'precio_x100',
            'id:12' => 'principio_activo',
            'id:4' => 'req_receta',
            'id:16' => 'size',
        )

        antes getAttributeTaxonomies()
    */
    static function getCustomAttributeTaxonomies(){
        $attributes = wc_get_attribute_taxonomies();
        $slugs      = wp_list_pluck( $attributes, 'attribute_name' );
        
        return $slugs;
    }

    /*
        Crea los atributos (sin valores) que se utilizan normalmente con productos variables
        (re-utilizables)
     
        Uso:
				
		Products::createAttributeTaxonomy(Precio EasyFarma Plus', 'precio_easyfarma_plus');

        Los atributos son creados en la tabla `wp_woocommerce_attribute_taxonomies`

     */
    static function createAttributeTaxonomy($name, $new_slug, $translation_domain = null) 
    {
        $attributes = wc_get_attribute_taxonomies();

        $slugs = wp_list_pluck( $attributes, 'attribute_name' );

        if ( ! in_array( $new_slug, $slugs ) ) {

            if ($translation_domain != null){
                $name  = __($name, $translation_domain );
            }

            $args = array(
                'slug'    => $new_slug,
                'name'    => $name,
                'type'    => 'select',
                'orderby' => 'menu_order',
                'has_archives'  => false,
            );

            $result = wc_create_attribute( $args );

        }
    }

    /*
        @param $product product object
        @param $attr size | color, etc
        @param $by_term_id bool by default gets name.  
        @return Array of terms (values)

        Podría cachearse !
    */
    static function getAttributesInStock($product, $attr, $by_term_id = false) {
        if (!$product->is_type('variable') ) {
            //throw new \InvalidArgumentException("Only variable products are accepted");
            return;
        }    

        $taxonomy    = 'pa_' . $attr; // The product attribute taxonomy
        $sizes_array = []; 

        // Loop through available variation Ids for the variable product
        foreach( $product->get_children() as $child_id ) {
            $variation = wc_get_product( $child_id ); // Get the WC_Product_Variation object

            if( $variation->is_purchasable() && $variation->is_in_stock() ) {
                $term_name = $variation->get_attribute( $taxonomy );

                if ($by_term_id){
                    $term = get_term_by('name', $term_name, 'pa_' . $attr);

                    if ($term === null || !is_object($term)){
                        continue;
                    }

                    $sizes_array[$term_name] = $term->term_id;
                } else {
                    $sizes_array[$term_name] = $term_name;
                }
            }
        }

        return $sizes_array;
    }

    /*
        Quizás de poca utilidad porque no toma en cuenta el stock
    */
    static function getProductsByTermId($term_id){
        global $wpdb;

		$sql = "SELECT * from `{$wpdb->prefix}term_relationships` WHERE term_taxonomy_id = $term_id";
		$arr = $wpdb->get_results($sql, ARRAY_A);

        return array_column($arr, 'object_id');
    }

    /*
        @param $att attribute, ej: 'talla-de-ropa'
        @param $cat category
        @return Array of terms

        No permite filtrar por stock (!)

        array (
            280 => 
            array (
                'slug' => '10-2',
                'name' => '10',
            ),
            281 => 
            array (
                'slug' => '12-2',
                'name' => '12',
            ),
            //  ...
        )
    */
    static function getAttrByCategory($att, $cat){
        $arr = [];

        if (!is_array($cat)){
            $cat = [ $cat ];
        }

        $args = array(
            'category'  => $cat
        );
        
        foreach( wc_get_products($args) as $product ){	
            foreach( $product->get_attributes() as $attr_name => $attr ){
                if ($attr_name != 'pa_' . $att){
                    continue;
                }
        
                foreach( $attr->get_terms() as $term ){

                    if (!in_array($term->name, $arr)){
                        $term_name = $term->name; // name
                        $term_slug = $term->slug; // slug
                        $term_id   = $term->term_id; // Id
                        $term_link = get_term_link( $term ); // Link

                        $arr[$term_id] = [
                            'slug' => $term->slug,
                            'name' => $term->name
                        ];
                    }
                }
            }
        }

        return $arr;
    }



    /*
        Devuelve custom attributes de productos simples. NO confundir con metas

        Ejemplo de uso:

        Products::getCustomAttr($pid)

        o

        Products::getCustomAttr($pid, 'Código ISP')

        Salida:

         array (
            'name' => 'Código ISP',
            'value' => 'F-983/13',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0,
        )
    */
    static function getCustomAttr($pid, $attr_name = null){
        global $wpdb;

        // if (!is_int($pid)){
        //     throw new \InvalidArgumentException("PID debe ser un entero.");
        // }

        // $pid = (int) $pid;

        $sql = "SELECT meta_value FROM `{$wpdb->prefix}postmeta` WHERE post_id = '$pid' AND meta_key = '_product_attributes'";

        $res = $wpdb->get_results($sql, ARRAY_A);   

        if (empty($res)){
            return;
        }

        $attrs = unserialize($res[0]['meta_value']);

        if (!empty($attr_name)){
            foreach ($attrs as $at){
                if ($at['name'] == $attr_name){
                    return $at;
                }
            }

            return;
        }

        return $attrs;
    }

    /*
        Uso:

        Products::getCustomAttrByLabel('Precio por 100 ml o 100 G')

        Salida:

        array (
            'attribute_id' => '6',
            'attribute_name' => 'precio_x100',
            'attribute_label' => 'Precio por 100 ml o 100 G',
            'attribute_type' => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public' => '0',
        )
    */

    static function getCustomAttrByLabel($label){
        global $wpdb;

        $sql = "SELECT * FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE attribute_label = '$label'";

        $res = $wpdb->get_results($sql, ARRAY_A);   

        if (empty($res)){
            return;
        }

        return $res[0];
    }


    /*
        Forma de uso:

        Products::getCustomAttrByName('forma_farmaceutica')

        Salida:

        array (
            'attribute_id' => '6',
            'attribute_name' => 'precio_x100',
            'attribute_label' => 'Precio por 100 ml o 100 G',
            'attribute_type' => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public' => '0',
        )        
    */
    static function getCustomAttrByName($name){
        global $wpdb;

        $sql = "SELECT * FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE attribute_name = '$name'";

        $res = $wpdb->get_results($sql, ARRAY_A);   

        if (empty($res)){
            return;
        }

        return $res[0];
    }

    /*
        Get attribute(s) from VARIANT or VARIATION
    */
    static function getAttributesFromVariation($product, $taxonomy = null){
        if (!empty($taxonomy)){
            if (substr($taxonomy, 0, 3) != 'pa_'){
                $taxonomy = 'pa_' .$taxonomy;
            }
        }

        if ( $product->get_type() != 'variation' ) {

            if ($product->get_type() == 'variable'){
                $variations = $product->get_available_variations();

                $ret = [];
                foreach($variations as $variation){
                    $attrs = $variation["attributes"];

                    if (!empty($taxonomy)){
                        if (!isset($attrs['attribute_' . $taxonomy])){
                            if (isset($attrs[$taxonomy])){
                                $ret[] = $attrs[$taxonomy];
                            }
                        } else {
                            $ret[] = $attrs['attribute_' . $taxonomy];
                        }
                    } else {
                        $ret[] = $attrs;     
                    }
                }

                return $ret;
            }

            //throw new \InvalidArgumentException("Expected variation. Given ". $product->get_type());
        }

        $attrs = $product->get_attributes($taxonomy);

        if (!empty($taxonomy)){
            if (isset($attrs[$taxonomy])){
                return $attrs[$taxonomy];
            } else {
                // caso de que pertenezcan a otra taxonomía
                return [];
            }
        }

        return $attrs;
    }


    static function getProductsByCategoryName(string $cate_name, $in_stock = true, $conditions = []){
        $categos = !is_array($cate_name) ? [ $cate_name ] : $cate_name;

        $query_args = array(
            'category' => $categos,
        );

        if ($in_stock){
            $query_args['stock_status'] = 'instock';
        }

        if (!empty($conditions)){
            $query_args = array_merge($query_args, $conditions);
        }

        return wc_get_products( $query_args );
    }

     /*
		Status

		En WooCommerce puede ser publish, draft, pending
		En Shopify serían active, draft, archived
	*/
    static function convertStatusFromShopifyToWooCommerce(string $status, bool $strict = false){
        $arr = [
            'active'   => 'publish',
            'archived' => 'draft',
            'draft'    => 'draft' 
        ];

        if (in_array($status, $arr)){
            return $arr[$status];
        }

        if ($strict){
            throw new \InvalidArgumentException("Status $status no válido para Shopify");
        }

        return $status;
    }

    static function convertStatusFromWooCommerceToShopify(string $status, bool $strict = false) {
        $arr = [
            'publish' => 'active',
            'draft'   => 'draft', 
            'pending' => 'draft'
        ];

        if (in_array($status, $arr)){
            return $arr[$status];
        }

        if ($strict){
            throw new \InvalidArgumentException("Status $status no válido para Shopify");
        }

        return $status;
    }
   
    static function getCategoriesFromProductID($pid){
        return parent::getCategoriesFromID($pid);
    }

    static function getCategoryNameByProductID($cat_id){
        return parent::getCategoryNameByID($cat_id);
    }

    static function getCategoriesFromProductSKU($sku){
        $pid = static::getProductIdBySKU($sku);
        
        return static::getCategoriesFromProductId($pid);
    }

    static function hide($product){
        if (is_object($product)){
            $pid = $product->get_id();
        } else {
            $pid = $product;
        }

        $terms = array('exclude-from-search', 'exclude-from-catalog' ); // for hidden..
        wp_set_post_terms($pid, $terms, 'product_visibility', false); 
    }

    static function unhide($product){
        if (is_object($product)){
            $pid = $product->get_id();
        } else {
            $pid = $product;
        }

        $terms = array();
        wp_set_post_terms($pid, $terms, 'product_visibility', false); 
    }

    static function duplicate($pid, callable $new_sku = null, Array $props = []){
        $p_ay = static::dumpProduct($pid);

        if (!is_null($new_sku) && is_callable($new_sku)){
            // Solo valido para un solo duplicado porque sino deberia mover el contador
            $p_ay['sku'] = $new_sku($p_ay['sku']);

            if (static::productExists($p_ay['sku'])){
                //dd("Producto con SKU '{$p_ay['sku']}' ya existe. Abortando,...");
                return;
            }

        } else {        
            $p_ay['sku'] = null;
        }

        $p_ay = array_merge($p_ay, $props);

        if (is_cli()){
            dd($p_ay, $pid);
        }

        $dupe = static::createProduct($p_ay);

        return $dupe;
    }

    /*
        Mejor que $p->get_sku() ya que no requiere sea estrictamente numerico el SKU

        Realmente es el $product_id que es buscado como _sku
    */
    static function getSKUFromProductId($product_id)
    {
        return static::getMeta($product_id, '_sku');
    }

    // before load_template_part()
    static function loadTemplatePart($slug, $name  = '') {
        ob_start();
        wc_get_template_part($slug, $name); // WC
        $var = ob_get_contents();
        ob_end_clean();
        return $var;
    }    
}