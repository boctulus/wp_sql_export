<?php

use boctulus\SW\core\libs\Schema;

global $wpdb;

$table_name = $wpdb->prefix . '{name}';

/*
    dbDelta() no siempre funciona asi que 
    puede ser necesario hacer un DROP primero
*/
if ($replace_if_exist){
    Schema::dropIfExists($table_name);
}

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    /* ... */
    created_at TIMESTAMP NULL,
    PRIMARY KEY (id)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);




