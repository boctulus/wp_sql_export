<?php

namespace boctulus\SW\core\libs\HtmlBuilder;

use simplerest\controllers\api\M;
use boctulus\SW\libs\Strings;

/*
    Re-implementar:

    Alerts -- done
    Cards
    inputColor
    Select
    Accordion (un reemplazo)
    switch 
    checkbox

    En BTS cambian los data-* por data-bs-* 

    Ej:

    data-toggle por data-bs-toggle
*/

class AdminLte extends Bt5Form
{
    static function alert(string $content, bool $dismissible = false, Array $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        $attributes['role']  = "alert";

        $title = $args['title'] ?? $attributes['title'] ?? '';
        
        $close_btn = '';
        if ($dismissible || in_array('dismissible', $attributes)){
            static::addClasses('alert-dismissible fade show', $attributes['class']);
            $close_btn = '<button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">×</button>';
        }

        // Proceso colores por si se envian usando color($color)
                
        $color   = $attributes['color'] ?? $args['color'] ?? null;
 
        if ($color !== null){
            if (!in_array($color, static::$colors)){
                throw new \InvalidArgumentException("Invalid color for '$color'");
            }

            static::addColor("alert-$color", $attributes['class']);
            unset($args['color']);
        }

        $kargs = array_merge(array_keys($args), array_keys($attributes));
 
        // Proceso colores provinientes en cualquier key => mucho más in-eficiente

        foreach ($kargs as $k){
            if (in_array($k, static::$colors)){
                static::addClass(" alert-$k", $attributes['class']); 
                $color = $k;
                break;
            }           
        }

        $icons = [
            'danger'  => 'fa-ban',
            'info'    => 'fa-info',
            'warning' => 'fa-exclamation-triangle',
            'success' => 'fa-check'
        ];

        $_title = '';
        if ($title != null && isset($icons[$color])){
            $icon   = $icons[$color];
            $_title = "<h5><i class=\"icon fas $icon\"></i> $title </h5>";
        }
            
        $content = $close_btn . $_title .$content;

        return static::div($content, $attributes, ...$args);
    }

    static function appButton(string $content, string $href, $attributes = [], ...$args){
        $_ = "btn btn-app";
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. $_ : $_;

        $icon = $args['icon'] ?? $attributes['icon'] ?? null;

        if (!$icon){
            throw new \Exception("icon is required");
        }

        $icon    = Strings::replaceFirst('fa-', '', $icon);
        unset($args['icon']);
    
        $qty = $args['badgeQty'] ?? $attributes['badgeQty'] ?? null;

        $badge = '';
        if ($qty !== null){
            $badge_color = $args['badgeColor'] ?? $attributes['badgeColor'] ?? 'danger';
        
            if ($badge_color !== null){          
                unset($args['badgeColor']);
            } 

            $badge = tag('span')->class('badge')->text($qty)->bg($badge_color);
            unset($args['qty']);
        }

        $anchor = $badge . "<i class=\"fas fa-{$icon}\"></i> $content</a>";

        return static::link($anchor, $href , $attributes, ...$args);
    }
   
    /*
        Puede manejarse con Javascript

        Ej:

        $('#range_1').ionRangeSlider({
            min     : 0,
            max     : 5000,
            from    : 1000,
            to      : 4000,
            type    : 'double',
            step    : 1,
            prefix  : '$',
            prettify: false,
            hasGrid : true
        })

        $('#range_5').ionRangeSlider({
            min     : 0,
            max     : 10,
            type    : 'single',
            step    : 0.1,
            postfix : ' mm',
            prettify: false,
            hasGrid : true
        })
    */
    static function ionSlider(mixed $default = null, Array $attributes = [], ...$args){
        /*
            Incluir el CSS acá genera dos problemas muy graves:

            1) No puede ser cacheado y 

            2) Queda repetido tantas veces como se incluya el componente! 

            La solución para "producción" sería "compilar" el las vistas con lo cual los archivos css 
            de cada componente serían incluídos una sola vez para la vista correspondiente.

            En si,... include_css() debería "encolar" los archivos css para la vista corespondiente.
        */

        include_css(ASSETS_PATH . 'adminlte/plugins/ion-rangeslider/css/ion.rangeSlider.min.css');

        $att = [
        ];

        // symbol == postfix
        $postfix = $args['symbol'] ?? $attributes['symbol'] ?? $args['postfix'] ?? $attributes['postfix'] ?? null;

        if ($postfix){
            $att['data-postfix'] = $postfix;

            unset($args['postfix']);
            unset($args['symbol']);
        }

        $step = $args['step'] ?? $attributes['step'] ?? null;

        if ($step){
            $att['data-step'] = $step;

            unset($args['step']);
        }

        $from = $args['from'] ?? $attributes['from'] ?? null;

        if ($from){
            $att['data-from'] = $from;

            unset($args['from']);
        }

        $to = $args['to'] ?? $attributes['to'] ?? null;

        if ($to){
            $att['data-to'] = $to;

            unset($args['to']);
        }


        $min = $args['min'] ?? $attributes['min'] ?? null;

        if ($min){
            $att['data-min'] = $min;

            unset($args['min']);
        }

        $max = $args['max'] ?? $attributes['max'] ?? null;

        if ($max){
            $att['data-max'] = $max;

            unset($args['max']);
        }

        $type = $args['type'] ?? $attributes['type'] ?? 'single';

        if ($type != 'single' && $type != 'double'){
            throw new \InvalidArgumentException("Type should be single or double");
        }

        $att['data-type'] = $max;
        unset($args['type']);


        $id = $args['id'] ?? $attributes['id'] ?? null;
        
        if ($id){
            $att['after_tag'] = 
            js("
                $(function () {
                    $('#$id').ionRangeSlider({})
                });
            ");
        }

        $attributes = $attributes + $att;

        return static::inputText($default, $attributes, ...$args);
    }


    /*
        Ribbon
    */

    static function ribbonTitle(mixed $content = null, Array $attributes = [], ...$args){
        $_ = "ribbon-wrapper";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. $_  : $_ ;

        $att = [
            'class' => ''
        ];

        $bg = $args['bg'] ?? $attributes['bg'] ?? null;

        if ($bg){
            static::addBgColor($bg, $att['class']);
            unset($args['bg']);
        }

        $size = $args['size'] ?? $attributes['size'] ?? null;

        if ($size){
            if ($size != 'lg' && $size != 'xl'){
                throw new \InvalidArgumentException("Invalid size '$size'. It can only be lg or xl");
            }

            static::addClass("ribbon-{$size}", $attributes['class']);
            unset($args['size']);
        }

        $textSize = $args['textSize'] ?? $attributes['textSize'] ?? null;

        if ($textSize){
            if ($textSize != 'lg' && $textSize != 'xl'){
                throw new \InvalidArgumentException("Invalid text size '$textSize'. It can only be lg or xl");
            }

            static::addClass("text-{$textSize}", $att['class']);
            unset($args['size']);
        }



        $content = static::ribbonContent($content, $att);


        return static::div($content, $attributes, ...$args);
    }

    static function ribbonContent(mixed $content = null, Array $attributes = [], ...$args){
        $_ = "ribbon";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. $_  : $_ ;
        
        return static::div($content, $attributes, ...$args);
    }


    static function ribbon(mixed $content = null, Array $attributes = [], ...$args){
        $_ = "position-relative";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. $_  : $_ ;
        
        if (empty($content)){
            $title  = $args['title']  ?? $attributes['title'] ?? null;
            $header = $args['header'] ?? $attributes['header'] ?? null;
            $body   = $args['body']   ?? $attributes['body'] ?? null;
            
            if ($body != null){
                unset($args['body']);
            }

            if ($title != null){
                unset($args['title']);
            }

            if ($header != null){
                unset($args['header']);
            }

            $content = [
                $header ?? '',
                $title  ?? '',
                $body
            ];
        }
        
        return static::div($content, $attributes, ...$args);
    }


    static function customFile(mixed $content = null, Array $attributes = [], ...$args){    
        $_ = "custom-file";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. $_ : $_;

        $id = $args['id'] ?? $attributes['id'] ?? null;
        
        if (empty($id)){
            throw new \Exception("id is required for custom input file");
        }

        unset($args['id']);

        $placeholder = $content ?? $args['placeholder'] ?? $attributes['placeholder'] ?? '';
        unset($args['placeholder']);

        $content = [
            static::file([
                'id' => $id,
                'class' => "custom-file-input"
            ]),
            static::label($id, $placeholder, [
                'class' => "custom-file-label"
            ])
        ];

        return static::div($content, $attributes, ...$args);
    }

    static function select2(Array $options, ?string $default = null, ?string $placeholder = null, Array $attributes = [], ...$args){
        $attributes['class'] = $attributes['class'] ?? $args['class'] ?? '';
        static::addClass('select2', $attributes['class']);

        return static::select($options, $default, $placeholder, $attributes, ...$args);
    }

    static function duallistbox(Array $options, ?string $default = null, ?string $placeholder = null, Array $attributes = [], ...$args){        
        $attributes['class'] = $attributes['class'] ?? $args['class'] ?? '';
        static::addClass('duallistbox', $attributes['class']);

        return static::select($options, $default, $placeholder, $attributes, ...$args);
    }

    static function inputMask(string $icon, string $format, Array $attributes = [], ...$args){    
        $id = $args['id'] ?? $attributes['id'] ?? null;
        
        if (empty($id)){
            throw new \Exception("id is required for custom input file");
        }

        unset($args['id']);

        $input_att = [
            'id' => $id,
            'class' => "form-control",               
            'data-inputmask-inputformat' => $format
        ];

        if ($icon == 'fa-calendar-alt' || $format == "dd/mm/yyyy" || $format == "mm/dd/yyyy"){
            $input_att['data-inputmask-alias'] = "datetime";
        }

        $content = [
            static::div('<span class="input-group-text"><i class="fas '.$icon.'"></i></span>', [
                'class' => "input-group-prepend"
            ]),
            static::input('text', null, 
                $input_att
            , ["data-mask"])
        ];
        
        return static::inputGroup($content, $attributes, ...$args);
    }

    static function dateMask(?string $format = null, Array $attributes = [], ...$args){    
        $format = $format ?? $args['format'] ?? $attributes['format'] ?? "dd/mm/yyyy";
        unset($args['format']);

        return static::inputMask('fa-calendar-alt', $format, $attributes, ...$args);
    }

    static function phoneMask(?string $format = null, Array $attributes = [], ...$args){    
        $format = $format ?? $args['format'] ?? $attributes['format'] ?? "(999) 999-9999";
        unset($args['format']);

        $id = $args['id'] ?? $attributes['id'] ?? null;
        
        if (empty($id)){
            throw new \Exception("id is required for custom input file");
        }

        unset($args['id']);

        $icon = 'fa-phone';

        $content = [
            static::div('<span class="input-group-text"><i class="fas '.$icon.'"></i></span>', [
                'class' => "input-group-prepend"
            ]),
            '<input type="text" class="form-control" id="'.$id.'" data-inputmask=\'"mask": "'.$format.'"\' data-mask>'
        ];
        
        return static::inputGroup($content, $attributes, ...$args);
    }

}

