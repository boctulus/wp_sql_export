<?php

namespace boctulus\SW\core\libs;

/*
    @author Pablo Bozzolo <boctulus@gmail.com>

    Al constructor o a setMetaAtts() pasar un array con los nombres de los atributos.

    Ej:

    $meta_atts = [
        'Att name 1',
        'Att name 2',
    ];

    Tambien es posible setear un callback para cada metabox.

    Ej:
    
    $atts = [
        'Precio TecnoGlobal',
        'Ganancia %'
    ];

    $mt = new postMetabox($atts);

    $mt->setCallback('Ganancia %', function($pid, $meta_id, &$ganancia){
        $price = posts::getMeta($pid, 'Precio TecnoGlobal');
        $price = $price * (1 + 0.01* $ganancia);

        posts::updatePrice($pid, $price);
    });

    Y setear campos read-only

    Ej:
    
    $mt = new postMetabox( [
        'Precio TecnoGlobal',
        'Ganancia %'
    ]);

    $mt->setCallback('Ganancia %', function($pid, $meta_id, &$ganancia){
        $price = posts::getMeta($pid, 'Precio TecnoGlobal');
        $price = $price * Quotes::dollar();
        $price = $price * (1 + 0.01* $ganancia);

        posts::updatePrice($pid, $price);
    });

    $mt->setReadOnly([
        'Precio TecnoGlobal'
    ]);
*/
class Metabox
{
    protected $meta_atts    = [];
    protected $callbacks    = [];
    protected $element_atts = [];

    // 'post', 'page', .., 'post',... array()
    protected $screen       = null;

    static function set($pid, $meta_key, $dato, bool $sanitize = true){
        return posts::setMeta($pid, $meta_key, $dato, $sanitize);
    }

    static function get($pid, $meta_key){
        return posts::getMeta($pid, $meta_key);
    }

    /*
        Ej:

        'My campo',

        [
            'readonly' => 'readonly'
        ]
    */
    function setElementAtts($field, Array $atts){        
        $meta_key = $field;

        if (!isset($this->element_atts[$meta_key])){
            $this->element_atts[$meta_key] = [];
        }

        foreach ($atts as $at => $val){
            $this->element_atts[$meta_key][$at] = $val;
        }
    }   

    /*
        Idealmente debe desactivar el setter correspondiente
        para que no se pueda hackaer desde el frontend
    */
    function setReadOnly($fields){
        foreach($fields as $field){
            $this->setElementAtts($field, [
                'readonly' => 'readonly'
            ]);
        }
    }

    function __construct(Array $meta_atts = [], $screen = null)
    {   
       
        $this->setMetaAtts($meta_atts);
        $this->setScreen($screen);

        add_action('add_meta_boxes', [$this, 'post_meta_box']);
        add_action('save_post', [$this, 'save_post_meta_box_data']);
    }

    function setScreen($screen){
        $this->screen = $screen;
        return $this;
    }

    function setMetaAtts(Array $meta_atts){
        $this->meta_atts = $meta_atts;
        return $this;
    }

    function setCallback($meta_key, callable $callback){
        $meta_key = $meta_key;
        $this->callbacks[$meta_key] = $callback;
    }

    // Puede ser protected?
    function post_meta_box($screen = null) {
        $screen = $screen ?? $this->screen;
        
        foreach ($this->meta_atts as $meta){
            $meta_id    = $meta;
            $meta_title = $meta;

            $atts = '';
            if (isset($this->element_atts[$meta_id])){
                foreach ($this->element_atts[$meta_id] as $at => $at_val){
                    $atts .= "$at = '$at_val' ";
                }
            }

            
            // dd($this->element_atts[$meta_id] ?? '', $meta_id);
            // exit;


            $meta_callback = function ( $post ) use ($meta_id, $meta_title, $atts) {
                // Add a nonce field so we can check for it later.
                wp_nonce_field( 'post_nonce', 'post_nonce' );
                
                $value = get_post_meta($post->ID, '_'.$meta_id, true);
                $value  = esc_attr($value);
        
                // Usar HTML helper idealmente
                echo "<textarea style=\"width:100%\" id=\"$meta_id\" name=\"$meta_title\" $atts>$value</textarea>";
            };
    
            add_meta_box(
                $meta_id,
                $meta_title,
                $meta_callback,
                $screen
            );    
        }
    }    
    
    /**
     * When the post is saved, saves our custom data.
     *
     * @param int $pid
     */
    function save_post_meta_box_data( $pid) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['post_nonce'] ) ) {
            return;
        }
    
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['post_nonce'], 'post_nonce' ) ) {
            return;
        }
    
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
    
        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
    
            if ( ! current_user_can( 'edit_page', $pid ) ) {
                return;
            }
    
        }
        else {
    
            if ( ! current_user_can( 'edit_post', $pid ) ) {
                return;
            }
        }
    
        /* OK, it's safe for us to save the data now. */
    
        foreach ($this->meta_atts as $meta){
            $meta_id = $meta;
    
            if (isset( $_POST[$meta_id])) {
                $data = sanitize_text_field( $_POST[$meta_id] );
                //dd($data, $meta_id);

                if (isset($this->callbacks[$meta_id])){
                    $cb = $this->callbacks[$meta_id];
                    $cb($pid, $meta_id, $data);
                }

                update_post_meta( $pid, "_{$meta_id}", $data ); 
            }
        }    
    }
}

