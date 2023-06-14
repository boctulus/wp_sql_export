<?php

namespace boctulus\SW\core\libs;

/*
	@author boctulus

    Dashboard WP menus

    Implementar tambien ----------->

    add_menu_page(): Esta función se utiliza para agregar un nuevo menú principal al Dashboard de WordPress. Permite crear una nueva página de menú con su propio contenido y submenús asociados.

    remove_menu_page(): Permite eliminar un menú principal específico del Dashboard. Puede ser útil para ocultar menús que no sean necesarios o relevantes para tu sitio.

    add_dashboard_page(): Agrega una página personalizada al tablero de WordPress. Esta función te permite crear una nueva página dentro del tablero de administración con tu propio contenido.

    add_options_page(): Agrega una página de opciones al menú de configuración de WordPress. Esta función se utiliza para agregar una nueva página de opciones donde los administradores pueden configurar ajustes específicos del tema o plugin.

    add_theme_page(): Permite agregar una página personalizada al menú de apariencia de WordPress. Esta función se utiliza para agregar páginas relacionadas con la apariencia y personalización del tema actual.

    add_plugins_page(): Agrega una página personalizada al menú de plugins de WordPress. Esta función se utiliza para agregar páginas relacionadas con la gestión y configuración de plugins.


    Chequear librerias de "White Label PRO" (en lo posible de la version PRO)

  */

class Menus
{
    static protected $capability;

    static protected function submenu($callback = '', $parent_slug = 'index.php', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        if (empty($page_title)){
            throw new \InvalidArgumentException("Page title is required");
        }
        
        if ($menu_title == null){
            $menu_title = $page_title; 
        }

        $menu_title = Strings::getUpTo($menu_title, null, 18);

        if ($menu_slug == null){    
            $menu_slug = Strings::toSnakeCase($menu_title);
        }
        
        add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability ?? static::$capability,
            $menu_slug,
            $callback,
            $position
        );    
    }

    /*

    */
    static function editSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'edit.php',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }

    /*
        Este slug te permite agregar un submenú a la sección "Plugins" en el panel de administración de WordPress, donde puedes gestionar los plugins instalados.
    */
    static function pluginsSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'plugins.php',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }

    /*
        Puedes usar este slug para agregar un submenú a la sección "Usuarios" en el panel de administración de WordPress, donde puedes administrar los usuarios y sus permisos
    */  
    static function usersPluginsSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'users.php',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }

    /*
        Utiliza este slug para agregar un submenú a la sección "Herramientas" en el panel de administración de WordPress, donde puedes acceder a diversas utilidades y configuraciones.
        
        > Tools --ok
    */
    static function toolsSubMenu( $callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'tools.php',
            $page_title,
            $menu_title,
            $capability ?? 'manage_options',
            $menu_slug,
            $position
        );    
    }

    /*
        Este slug te permite agregar un submenú a la sección "Ajustes" en el panel de administración de WordPress, donde puedes configurar opciones generales de tu sitio.

        > Settings ("Ajustes")  --ok    
    */
    static function optionsSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'options-general.php',
            $page_title,
            $menu_title,
            $capability ?? 'manage_options',
            $menu_slug,
            $position
        );    
    }

    /*
        Puedes usar este slug para agregar un submenú a la sección "Páginas" en el panel de administración de WordPress, donde puedes administrar las páginas.
    */
    static function CPTSubMenu($callback = '', $post_type = 'post', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'edit.php?post_type=' . $post_type,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }

    /*
        Puedes usar este slug para agregar un submenú a la sección "Páginas" en el panel de administración de WordPress, donde puedes administrar las páginas.
    */
    static function pageSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::CPTSubMenu(
            $callback,
            'page',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }

    /*
        Utiliza este slug para agregar un submenú a la sección "Apariencia" en el panel de administración de WordPress, donde puedes administrar los temas y personalizar el diseño.
    */
    static function themesSubMenu($callback = '', $page_title = null, $menu_title = null, $capability = null, $menu_slug = null, $position = null){
        static::submenu(
            $callback,
            'themes.php',
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $position
        );    
    }
}

