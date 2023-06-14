<?php

namespace boctulus\SW\core\libs;

/*
	@author boctulus

    Dashboard WP menus
*/

class MenusWooCommerce extends Menus
{
    static protected $capability = 'manage_woocommerce';

    /*
        WooCoomerce main menu
    */
    static function menu($callback = '', $parent_slug = 'admin.php?page=wc-admin', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        parent::submenu(
            $callback,
            $parent_slug, 
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );
    }

    /*
        Products
    */
    static function productsSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'edit.php?post_type=product',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }


    // ...

}

