<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

/*
	@author boctulus
*/

class Posts 
{
    static $post_type   = 'post';
    static $cat_metakey = 'category';

    /*
        Puede que no sea la mejor forma porque se salta el mecanismo de cache
    */
    static function setStatus($pid, $status){
        global $wpdb;

        if ($pid == null){
            throw new \InvalidArgumentException("PID can not be null");
        }

        // Status ('publish', 'pending', 'draft' or 'trash')
        if (!in_array($status, ['publish', 'pending', 'draft', 'trash'])){
            throw new \InvalidArgumentException("Estado '$status' invalido.");
        }

        $sql = "UPDATE `{$wpdb->prefix}posts` SET `post_status` = '$status' WHERE `{$wpdb->prefix}posts`.`ID` = $pid;";
        return $wpdb->get_results($sql);    
    }

    // alias
    static function updateStatus($pid, $status){
        return static::setStatus($pid, $status);
    }

    static function setAsDraft($pid){
        static::setStatus($pid, 'draft');
    }

    static function setAsPublish($pid){
        static::setStatus($pid, 'publish');
    }

    static function trash($pid){
        return static::setStatus($pid, 'trash');
    }

    // 'publish'
    static function restore($pid){
        return static::setStatus($pid, 'publish');
    }

    static function getAttr($key = null){
        return post_custom($key);
    }

    /*
        Ej:
        
        Array
        (
            [_edit_lock] => Array
                (
                    [0] => 1685355749:1
                )

            [gdrive_actualizacion] => Array
                (
                    [0] => 2000-10-10
                )

        )
    */
    static function getAttrByID($id, $key = null){
        $attrs = get_post_custom($id);

        if (!empty($key)){
            return $attrs[$key] ?? null;
        }

        return $attrs;
    }

    static function addAttr($post_id, $attr_name, $attr_value){
        add_post_meta($post_id, $attr_name, $attr_value, true);
    }

    /*
        Ej de uso:

            $slug = 'aplique-espejo-impermeable-50-cm';
            Posts::getBySlug($slug, 'product')

        Retorna un array de propiedades

        [
            'ID' => 2295,
            'post_author' => '1',
            'post_date' => '2022-06-27 20:23:42',
            'post_date_gmt' => '2022-06-27 20:23:42',
            'post_content' => '',
            'post_title' => 'Aplique espejo impermeable 50 cm',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => 'aplique-espejo-impermeable-50-cm',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => '2022-07-11 17:00:20',
            'post_modified_gmt' => '2022-07-11 17:00:20',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => 'http://woo1.lan/producto/aplique-espejo-impermeable-50-cm/',
            'menu_order' => 0,
            'post_type' => 'product',
            'post_mime_type' => '',
            'comment_count' => '0',
            'filter' => 'raw',
            'ancestors' =>
            array (
            ),
            'page_template' => '',
            'post_category' =>
            array (
            ),
            'tags_input' =>
            array (
            ),
        ]
    */

    static function getBySlug(string $slug, string $post_type = null, $post_status = 'publish')
    {
        if ($post_type == null){
            $post_type = static::$post_type;
        }

        $args = array(
        'name'        => $slug,
        'post_type'   => $post_type,
        'post_status' => $post_status,
        'numberposts' => 1
        );

        $arr = get_posts($args);

        return (empty($arr)) ? NULL : $arr[0]->to_array();
    }

    static function getIDs($post_type = null, $post_status = null){
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }

        $include_post_status = '';
        if ($post_status !== null){
            $include_post_status = "AND post_status = '$post_status'";
        }

        /*
            El primer post (ID=1) suele ser esto, asi que lo descarto (WHERE ID <> 1)
        
            <!-- wp:paragraph -->
            <p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>
            <!-- /wp:paragraph -->
        */

        $sql = "SELECT ID FROM `{$wpdb->prefix}posts` WHERE ID <> 1 AND post_type = '$post_type' $include_post_status ORDER BY ID ASC";
       
        $res = $wpdb->get_results($sql, ARRAY_A);   

        return  array_column($res, 'ID') ?? null;
	}

    static function getLastPost($post_type = null, $post_status = null){
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }

        $include_post_status = '';
        if ($post_status !== null){
            $include_post_status = "AND post_status = '$post_status'";
        }

        $sql = "SELECT ID FROM `{$wpdb->prefix}posts` WHERE post_type = '$post_type' $include_post_status ORDER BY ID DESC LIMIT 1;";
        $res = $wpdb->get_results($sql, ARRAY_A);   

        return $res[0] ?? null;
	}

    static function getLastID($post_type = null, $post_status = null){
        if ($post_type == null){
            $post_type = static::$post_type;
        }

        $post = static::getLastPost($post_type, $post_status);

        if ($post == null){
            return null;
        }

        return (int) $post['ID'] ?? null;
	}

    static function getAll($post_type = null, $status = 'publish', $limit = -1, $order = null){
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS  * FROM {$wpdb->prefix}posts  WHERE 1=1  AND (({$wpdb->prefix}posts.post_type = '$post_type' AND ({$wpdb->prefix}posts.post_status = '$status')));";
    
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    static function getOne($post_type = null, $status = 'publish', $limit = -1, $order = null){
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS  * FROM {$wpdb->prefix}posts WHERE 1=1 AND (({$wpdb->prefix}posts.post_type = '$post_type' AND ({$wpdb->prefix}posts.post_status = '$status')));";
    
        return $wpdb->get_row($sql, ARRAY_A);
    }    

    /*
        Retorna posts contienen determinado valor en una meta_key
    */
    static function getByMeta($meta_key, $meta_value, $post_type = null, $post_status = null ) {
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }

        if (!Strings::startsWith('_', $meta_key)){
            $meta_key = '_' . $meta_key;
        }

        /*
            SELECT p.*, pm.* FROM wp_postmeta pm
            LEFT JOIN wp_posts p ON p.ID = pm.post_id 
            WHERE p.post_type = 'product' 
            AND pm.meta_key = '_forma_farmaceutica' 
            AND pm.meta_value='triangulo'
            AND p.post_status = 'publish'
            ;
        */

        $sql = "SELECT p.*, pm.* FROM wp_postmeta pm
        LEFT JOIN {$wpdb->prefix}posts p ON p.ID = pm.post_id 
        WHERE p.post_type = '%s' 
        AND pm.meta_key = '%s' 
        AND pm.meta_value='%s'
        ";

        if ($post_status !== null){
            $sql .= " AND p.post_status = '$post_status'";
        }

        $r = $wpdb->get_results($wpdb->prepare($sql, $post_type, $meta_key, $meta_value), ARRAY_A);
    
        return $r;
    }

    /*
        Retorna la cantidad de productos contienen determinado valor en una meta_key
    */
    static function countByMeta($meta_key, $meta_value, $post_type = null, $status = 'publish' ) {
        global $wpdb;

        if ($post_type == null){
            $post_type = static::$post_type;
        }

        if (!Strings::startsWith('_', $meta_key)){
            $meta_key = '_' . $meta_key;
        }

        /*
            SELECT COUNT(*) FROM wp_postmeta pm
            LEFT JOIN wp_posts p ON p.ID = pm.post_id 
            WHERE p.post_type = 'product' 
            AND pm.meta_key = '_forma_farmaceutica' 
            AND pm.meta_value='crema'
            AND p.post_status = 'publish'
            ;
        */
        $sql = "SELECT COUNT(*) FROM wp_postmeta pm
        LEFT JOIN wp_posts p ON p.ID = pm.post_id 
        WHERE p.post_type = '%s' 
        AND pm.meta_key = '%s' 
        AND pm.meta_value='%s'
        AND p.post_status = '%s'
        ";

        $r = (int) $wpdb->get_var($wpdb->prepare($sql, $post_type, $meta_key, $meta_value, $status));
    
        return $r;
    }

    /*
        Uso. Ej:

        Products::getTaxonomyFromTerm('Crema')

        retorna

        array (
            0 => 'pa_forma_farmaceutica',
            1 => 'pa_dosis',
        )
    */
    static function getTaxonomyFromTerm(string $term_name){
        global $wpdb;

        /*  
            SELECT * FROM wp_terms AS t 
            LEFT JOIN wp_termmeta AS tm ON t.term_id = tm.term_id 
            LEFT JOIN wp_term_taxonomy AS tt ON tt.term_id = t.term_id
            WHERE t.name = 'Crema'
        */

        $sql = "SELECT taxonomy FROM wp_terms AS t 
        LEFT JOIN {$wpdb->prefix}termmeta AS tm ON t.term_id = tm.term_id 
        LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_id = t.term_id
        WHERE name = '%s'";

        $r = $wpdb->get_col($wpdb->prepare($sql, $term_name));
    
        return $r;
    }

    static function getTermIdsByTaxonomy(string $taxonomy){
        global $wpdb;

        if (!Strings::startsWith('pa_', $taxonomy)){
            $taxonomy = 'pa_' . $taxonomy;
        }

        $sql = "SELECT term_id FROM `{$wpdb->prefix}term_taxonomy` WHERE `taxonomy` = '$taxonomy';";

        return $wpdb->get_col($sql);
    }

     /*
        Devolucion de array de metas incluidos atributos de productos

        array (
            '_sku' =>
            array (
                0 => '7800063000770',
            ),
            '_regular_price' =>
            array (
                0 => '2790',
            ),

            // ...
            
            '_product_attributes' =>
            array (
                0 => 'a:0:{}',
            ),
            
            // ...

            '_laboratorio' =>
            array (
                0 => 'Mintlab',
            ),
            '_enfermedades' =>
            array (
                0 => 'Gripe',
            ),        
        )

        Si $single es true, en vez de devolver un array, se devuelve un solo valor,
        lo cual tiene sentido con $key != ''
    */
    static function getMetasByID($pid, $meta_key = '', bool $single = false){
        if (!empty($meta_key)){
            if (!Strings::startsWith('_', $meta_key)){
                $meta_key = '_' . $meta_key;
            }
        }

        return get_post_meta($pid, $meta_key, $single);
    }

    static function getMeta($post_id, $meta_key){
        if (!Strings::startsWith('_', $meta_key)){
            $meta_key = '_' . $meta_key;
        }

        return get_post_meta($post_id, $meta_key, true);
    }

    static function setMeta($post_id, $meta_key, $dato, bool $sanitize = true){
        if (!Strings::startsWith('_', $meta_key)){
            $meta_key = '_' . $meta_key;
        }

        if ($sanitize){
            $dato = sanitize_text_field($dato);
        }

        update_post_meta( $post_id, $meta_key, $dato); 
    }

    /*
        Devuelve si un termino existe para una determinada taxonomia

        Nota: atributos re-utilizables de productos variables son "terms" tambien
    */
    static function termExists($term_name, string $taxonomy){
        if (!Strings::startsWith('pa_', $taxonomy)){
            $taxonomy = 'pa_' . $taxonomy;
        }
        
        return (term_exists($term_name, $taxonomy) !== null);
    }

    
    static function getTermBySlug($slug){
		global $wpdb;

		$sql = "SELECT * from `{$wpdb->prefix}terms` WHERE slug = '$slug'";
		return $wpdb->get_row($sql);
	}

    static function getTermById(int $id){
		// global $wpdb;

		// $sql = "SELECT * from `{$wpdb->prefix}terms` WHERE term_id = '$id'";
		// return $wpdb->get_row($sql);

        return get_term($id);
	}

    /*
        Delete Attribute Term by Name

        Borra los terminos agregados con insertAttTerms() de la tabla 'wp_terms' por taxonomia (pa_forma_farmaceutica, etc)
    */
    static function deleteTermByName(string $term_name, string $taxonomy, $args = []){
        if (!Strings::startsWith('pa_', $taxonomy)){
            $taxonomy = 'pa_' . $taxonomy;
        }

        $term_ids = static::getTermIdsByTaxonomy($taxonomy);

        foreach ($term_ids as $term_id){
            wp_delete_term($term_id, $taxonomy, $args);
        }
    }

    /*
        Categories
    */

    static function createCatego($name, $slug = null, $description = null, $id_parent = null){
        // dd([
        //     $name, // the term 
        //     static::$cat_metakey, // the taxonomy
        //     array(
        //         'description'=> $description,
        //         'slug' => $slug,
        //         'parent' => $id_parent
        //     )
        //     ], 'NUEVA CATEGO'
        // );

        $cid = wp_insert_term(
            $name, // the term 
            static::$cat_metakey, // the taxonomy
            array(
                'description'=> $description,
                'slug'       => $slug,
                'parent'     => $id_parent
            )
        );

        // category_id
        return $cid;
    }

    static function createOrUpdateCatego($name, $slug = null, $description = null, $id_parent = null)
    {
        $id = static::getCategoryIdByName($name);

        if ($id !== null){
            //dd("Updating ... $name");

            $cid = wp_update_term(
                $id, 
                static::$cat_metakey, // the taxonomy
                array(
                    'description'=> $description,
                    'slug' => $slug,
                    'parent' => $id_parent
                )
            );
        } else {
            //dd("Creating ... $name");

            $cid = wp_insert_term(
                $name, // the term 
                static::$cat_metakey, // the taxonomy
                array(
                    'description'=> $description,
                    'slug'       => $slug,
                    'parent'     => $id_parent
                )
            );
        }

        // category_id
        return $cid;
    }


    static function getCategoryIdByName($name){
        $category = get_term_by( 'slug', $name, static::$cat_metakey );

        if ($category === null || $category === false){
            return null;
        }

        return $category->term_id;
    }

    /*
        $only_subcategos  determina si las categorias de primer nivel deben o no incluirse 
    */
    static function getCategories(bool $only_subcategos = false){
        static $ret;

        if ($ret !== null){
            return $ret;
        }

        $taxonomy     = static::$cat_metakey;
        $orderby      = 'name';  
        $show_count   = 1;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no  
        $title        = '';  
        $empty        = 0;

        $args = array(
                'taxonomy'     => $taxonomy,
                'orderby'      => $orderby,
                'show_count'   => $show_count,
                'pad_counts'   => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li'     => $title,
                'hide_empty'   => $empty
        );
        
        $all_categories = get_categories( $args );

        if (!$only_subcategos){
            return $all_categories;    
        }

        $ret = [];
        foreach ($all_categories as $cat) {
            if($cat->category_parent == 0) {
                $category_id = $cat->term_id;       
                $link = '<a href="'. get_term_link($cat->slug, static::$cat_metakey) .'">'. $cat->name .'</a>';

                $args2 = array(
                        'taxonomy'     => $taxonomy,
                        'child_of'     => 0,
                        'parent'       => $category_id,
                        'orderby'      => $orderby,
                        'show_count'   => $show_count,
                        'pad_counts'   => $pad_counts,
                        'hierarchical' => $hierarchical,
                        'title'        => $title,
                        'hide_empty'   => $empty
                );
                $sub_cats = get_categories( $args2 );

                if($sub_cats) {
                    foreach($sub_cats as $sub_category) {
                        $ret[] = $sub_category;
                    }   
                } 
            }       
        }

        return $ret;
    }

    static function getCategoSlugs(){
        $categos = static::getCategories();
    
        $ret = [];
        foreach ($categos as $catego){
            $ret[] = $catego->slug;
        }
    
        return $ret;
    }    
    
    static function getCategoryChildren($category_id){
        return get_term_children($category_id, static::$cat_metakey);
    }

    static function getCategoryChildrenBySlug($category_slug){
        $cat = static::getTermBySlug($category_slug);

        if ($cat === null){
            return null;
        }

        $category_id = $cat->term_id;
        return static::getCategoryChildren($category_id);
    }

     /*
        https://devwl.pl/wordpress-get-all-children-of-a-parent-product-category/
    */
    static function getTopLevelCategories(){
		global $wpdb;

        $cat_metakey = static::$cat_metakey;

		$sql = "SELECT t.term_id, t.name, t.slug,  tt.taxonomy, tt.parent, tt.count FROM {$wpdb->prefix}terms t
        LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = $cat_metakey 
        AND tt.parent = 0
        ORDER BY tt.taxonomy;";

		return $wpdb->get_results($sql);
	}

    static function getAllCategories(){
		global $wpdb;

        $cat_metakey = static::$cat_metakey;

		$sql = "SELECT t.term_id, t.name, t.slug,  tt.taxonomy, tt.parent, tt.count FROM {$wpdb->prefix}terms t
        LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = $cat_metakey 
        ORDER BY tt.taxonomy;";

		return $wpdb->get_results($sql);
	}
   
    /*
        Dado el id de una categoria devuelve algo como

        A > A2 > A2-1 > A2-1a
    */
    static function breadcrumb(int $cat_id){
        $search = $cat_id;

        $path = [];
        $parent_id = null;

        while($parent_id !== 0){
            $catego     = get_term($cat_id);
            $parent_id = $catego->parent;

            if ($parent_id == 0){
                break;
            }

            $path[] = get_the_category_by_ID($parent_id);            
            $cat_id = $parent_id;
        }

        $path       = array_reverse($path);
        $last_sep   = empty($path) ? '' : '>';
        $first_sep  = '/';
        
        $breadcrumb = ltrim($first_sep . ' '). ltrim(implode(' > ', $path) . " $last_sep " . get_the_category_by_ID($search));
     
        return $breadcrumb;
    }

    static function getCategoriesFromID($pid){
        return wc_get_product_term_ids($pid, static::$cat_metakey);
    }

    static function getCategoryNameByID($cat_id){
        if( $term = get_term_by('id', $cat_id, static::$cat_metakey) ){
            return $term->name;
        }

        throw new \InvalidArgumentException("Category ID '$cat_id' not found");
    }

        /*
        OJO: hace un AND entre cada categoria en la query
    */
    static function getProductsByCategory(string $by, Array $cate_ids, $post_status = null)
    {
        if (!in_array($by, ['slug', 'id', 'name'])){
            throw new \InvalidArgumentException("Invalid field '$by' for quering");
        }

        if ($post_status != null && !in_array($post_status, ['publish', 'draft', 'pending', 'private', 'trash', 'auto-draft'])){
            throw new \InvalidArgumentException("Invalid post_status '$post_status'");
        }

        // When you have more term_id's seperate them by comma.
        $cate_ids_str = implode(',', $cate_ids);

        $args = array(
            'post_type' => static::$post_type,
            'numberposts' => -1,
            'post_status' => $post_status,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => static::$cat_metakey,
                    'field' => $by,
                    'terms' => $cate_ids_str,
                    'operator' => 'IN',
                    )
                 ),
        );

        return get_posts( $args);
    }

    static function getProductsByCategoryId(Array $cate_ids, $post_status = null){
        return static::getProductsByCategory('id', $cate_ids, $post_status);
    }

    

}