<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\SW\core\libs;

/*
    Custom Post Type

    Ver
    https://wordpress.stackexchange.com/questions/401323/conditional-delete-metadata-does-not-works
*/
class CPT {
    static function getAll($post_type = 'post', $status = 'publish', $limit = -1, $order = null){
        global $wpdb;
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS  * FROM wp_posts  WHERE 1=1  AND ((wp_posts.post_type = '$post_type' AND (wp_posts.post_status = '$status')));";
    
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    static function getOne($post_type = 'post', $status = 'publish', $limit = -1, $order = null){
        global $wpdb;
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS  * FROM wp_posts  WHERE 1=1  AND ((wp_posts.post_type = '$post_type' AND (wp_posts.post_status = '$status')));";
    
        return $wpdb->get_row($sql, ARRAY_A);
    }
}