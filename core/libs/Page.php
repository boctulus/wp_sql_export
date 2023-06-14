<?php 

namespace boctulus\SW\core\libs;

class Page
{
    // is the Post, a Page?
    static function isPage(string $page = ''){
        return is_page($page);
    }
    
    static function isArchive(){
        return is_archive();
    }

    // is the Post, an Attachment?
    static function isAttachment($attachment = ''){
        return is_attachment($attachment);
    }

    /*
        Is this a Post?

        Si es una pagina de archives o categorias,
        devolveria false
    */
    static function isSingular($post_types = ''){
        return is_singular($post_types);
    }

    static function isHone(){
        return is_home();
    }

    static function is404(){
        return is_404();
    }
    
    /*
        Para productos, devolveria 'product'
    */
    static function getType($post = null){
        return get_post_type($post);
    }

    /*
        WooCommerce
    */

    static function isCart(){
        return is_cart();
    }

    static function isCheckout(){
        return is_checkout();
    }

    static function isProductArchive(){
        return is_shop(); 
    }

    static function isProduct(){
        return is_product();
    }

    static function isProductCategory($term = ''){
        return is_product_category($term);
    }
    
    /*
        Extras
    */

    static function getSlug(){
        return get_post_field('post_name', get_post());
    }

    /*
        Devuelve el post con sus atributos dada la pagina actual

        @param $post_type por ejemplo 'page' o 'product'    
    */
    static function getPost($post_type = 'page') : Array {
        return get_page_by_path(static::getSlug(), ARRAY_A, $post_type );
    }

    /*
        @param callable $callback

        Ejemplo de uso:

        Page::replaceContent(function(&$content){
            $content = preg_replace('/Mi cuenta/', "CuentaaaaaaaX", $content);
        });
    */
    static function replaceContent(callable $callback){
        add_action( 'init', function(){
            ob_start();
        }, 0 );
        
        add_action('wp_footer', function() use ($callback)
        {       
            $content = ob_get_contents();
        
            $callback($content);
            ob_end_clean(); 
        
            echo $content;        
        });
    }

}