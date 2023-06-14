<?php

namespace boctulus\SW\core\libs\HtmlBuilder;

use boctulus\SW\core\libs\Arrays;
use boctulus\SW\core\libs\Strings;

class Html
{
    static protected $pretty     = false;
    static protected $id_eq_name = false;
    static protected $class;
    static protected $colors = [
        'default',
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
        'body',
        'muted',
        'white',
        'black-50',
        'white-50'
    ];

    static protected $text_colors = [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
        'body',
        'muted',
        'white',
        'black-50',
        'white-50'
    ];

    static protected $bg_colors = [
        'default',
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
        'body',
        'white',
        'transparent'
    ];

    static protected $classes = [
        "inputText"      => "form-control",
        "number"         => "form-control",
        "password"       => "form-control",
        "email"          => "form-control",
        "file"           => "form-control",
        "date"           => "form-control",
        "time"           => "form-control",
        "datetime_local" => "form-control",
        "month"          => "form-control",
        "week"           => "form-control",
        "image"          => "form-control",
        "range"          => "form-control",
        "tel"            => "form-control",
        "url"            => "form-control",
        "area"           => "form-control",
        "dataList"       => "form-control",
        "search"         => "form-control",

        "range"          => "form-range",
        "select"         => "form-select",
        "checkbox"       => "form-check-input",
        "radio"          => "form-check-input",
        "label"          => "form-label",
        "button"         => "btn",
        "submit"         => "btn btn-primary",
        "reset"          => "btn btn-primary",
        "inputButton"    => "btn btn-primary",

        "inputGroup"     => "input-group",
        "checkGroup"     => "form-check",
        "buttonToolbar"  => "btn-toolbar",
 
        "formFloating"   => "form-floating",

        "inputColor"     => "form-control form-control-color",

        "alert"          => "alert alert-primary",
        "alertLink"      => "alert-link",

        "badge"          => "badge",

        "blockquoteFooter" => "blockquote-footer",

        /* Stacked form controls */

        "form-check"       => "form-check",
        "form-check-label" => "formCheckLabel",

        /* Cards Begin */

        "card"           => "card",
        "cardBody"       => "card-body",
        "cardLink"       => "card-link",
        "cardText"       => "card-text",
        "cardTitle"      => "card-title",
        "cardSubtitle"   => "card-subtitle",
        "cardImg"        => "card-img",
        "cardImgTop"   => "card-img-top",
        "cardImgBottom"  => "card-img-bottom",
        "cardImgOverlay" => "card-img-overlay",
        "cardListGroup"  => "list-group list-group-flush",
        "cardListGroupItem" => "list-group-item",
        "cardHeader"     => "card-header",
        "cardHeaderTabs" => "card-header-tabs",
        "cardFooter"     => "card-footer",

        /* Cards End */

        /* Carousel Begin */

        "carousel"       => "carousel slide",
        "carouselFade"   => "carousel-fade",
        "carouselDark"   => "carousel-dark",
        "carouselInner"  => "carousel-inner",
        "carouselItem"   => "carousel-item", 
        "carouselControlPrev" => "carousel-control-prev",
        "carouselControlPrevIcon" => "carousel-control-prev-icon",
        "carouselControlNext" => "carousel-control-next",
        "carouselControlNextIcon" => "carousel-control-next-icon",
        "carouselIndicators" => "carousel-indicators",
        "carouselCaption" => "carousel-caption", 
        "carouselImg"   => "d-block w-100",
              
        /* Carousel End */


        "closeButton"    => "btn-close",
        "modal"          => "modal",
        "modalDialog"    => "modal-dialog",
        "modalContent"   => "modal-content",
        "modalHeader"    => "modal-header",
        "modalTitle"     => "modal-title",
        "modalBody"      => "modal-body",
        "modalFooter"    => "modal-footer",

        /* Collapse */

        "collapse"       => "collapse",
        "collapseBody"   => "card card-body",


        /* Dropdown */

        "dropdown"       => "dropdown",
        "dropdownButton" => "btn dropdown-toggle",
        "dropdownLink"   => "dropdown-toggle",
        "dropdownMenu"   => "dropdown-menu",
        "dropdownItem"   => "dropdown-item",
        "dropdownDivider" => "dropdown-divider",
        "tabContent"     => "tab-content",
        "tabPane"        => "tab-pane fade",

        /* List group */

        "listGroup"      => "list-group",
        "listGroupNumbered" => "list-group list-group-numbered",
        "listGroupItem"  => "list-group-item",
        "listGroupItemAction"  => "list-group-item-action",
        
        /* Nav & Navbars Begin */

        "nav"            => "nav",
        "navItem"        => "nav-item",
        "navLink"        => "nav-link",

        "navbar"         => "navbar",
        "navbarBrand"    => "navbar-brand",
        "navbarToggler"  => "navbar-toggler",
        "navbarCollapse" => "collapse navbar-collapse", 
        "navbarNav"      => "navbar-nav",

        /* Nav & Navbars End   */

        /* Offcanvas   */

        "offcanvas" => "offcanvas",
        "offcanvasHeader" => "offcanvas-header",
        "offcanvasTitle" => "offcanvas-title",
        "offcanvasBody"  => "offcanvas-body",
        "offcanvasCloseButton" => "btn-close text-reset",

        /* Progress bars   */

        "progress"        => "progress",
        "progressBar"     => "progress-bar",


        /* Toasts */

        "toast"           => "toast",
        "toastHeader"     => "toast-header",
        "toastBody"       => "toast-body",
        "toast-container" => "toast-container",

        /*
           Otros
        */

        "hidden"         => "visually-hidden",
        "active"         => "active",
        "tooltip"        => "tooltip-test",
        
        /* Table */

        "table"          => "table",

    ];
    
    static protected $macros = [];

    // mover a un helper ya que es muy general
    static protected function shift(string $key, &$var, $default_value = null) : mixed {
        $out = $var[$key] ?? $default_value;
        unset($var[$key]);
        
        return $out;
    }

    static protected function getAtt(string $att, Array $v1, Array $v2, $default_value = null){
        return $v1[$att] ?? $v2[$att] ?? $default_value;
    }

    static protected function shiftClass(){
        $class = static::$class;
        static::$class = '';

        return $class;
    }

    static function pretty(bool $state = true){
        static::$pretty = $state;
    }

    static function removeColors(string $type, array|string &$to) : void{
        if (!in_array($type, ['btn', 'alert', 'bg', 'text', 'border'])){
            throw new \InvalidArgumentException("Color type '$type' is incorrect");
        }

        if (is_array($to)){
            foreach ($to as $ix => $row){
                foreach (static::$colors as $c){
                    static::removeClass("$type-$c", $to[$ix]);

                    if ($type == 'btn'){
                        static::removeClass("btn-outline-$c", $to[$ix]);
                    }                    
                }    
            }

            return;
        }

        foreach (static::$colors as $c){
            static::removeClass("$type-$c", $to);            

            if ($type == 'btn'){
                static::removeClass("$type-outline-$c", $to);
            }  
        }     
    }

    static function addColor(string $color, string &$to, bool $outline = false) : void{
        if (Strings::lastChar($color) == '-'){
            return;
        }

        $prefix = '';

        if (strlen($color)){
            $pos = strpos($color, '-');

            if ($pos !== false){
                $_prefix = substr($color, 0, $pos + 1);

                if (in_array($_prefix, ['btn-', 'alert-', 'text-', 'bg-', 'border-'])){
                    $prefix = $_prefix;
                } else {
                    $pos = 0;
                }
            } else {
                $pos = -1;
            }

            $_color = substr($color, $pos + 1);

            if (in_array($_color, static::$colors)){
                $color = $_color;
            }
        }

        $outline_prefix = '';
        if ($outline){
            $outline_prefix = 'outline-';
        }

        if (empty($to)){
            $to = "{$prefix}{$outline_prefix}{$color}";
            return;
        }

        /* remuevo colores previos */


        $type = substr($prefix, 0, -1);
        static::removeColors($type, $to);  
        
        /* 
            si el color a aplicar, no existe => lo aplico
        */

        if (strpos($to, $color) === false){
            $to .= " {$prefix}{$outline_prefix}{$color}";
        }    
    }

    static function addBgColor(string $color, string &$to) : void{
        if (Strings::lastChar($color) == '-'){
            return;
        }

        if (empty($to)){
            $to = "bg-{$color}";
            return;
        }

        /* remuevo colores previos */

        $type = substr('bg-', 0, -1);
        static::removeColors($type, $to);  
        
        /* 
            si el color a aplicar, no existe => lo aplico
        */

        if (strpos($to, $color) === false){
            $to .= " bg-{$color}";
        }    
    }

    static function hasColor(string $type, string $str, string $color = '') : bool{
        $type_len = strlen($type);

        if (!in_array($type, ['btn', 'alert', 'bg', 'text', 'border'])){
            throw new \InvalidArgumentException("Color type '$type' is incorrect. It should be ['btn', 'alert', 'bg', 'text', 'border']");
        }

        if (strlen($color) > $type_len && substr($color, 0, $type_len) != $type . '-'){
            $color = "{$type}-$color";
        }    

        if (!empty($color)){
            return Strings::containsWord($color, $str);
        }

        foreach (static::$colors as $c){
            if (Strings::containsWord("{$type}-$c", $str)){
                return true;
            }
        }
        
        return false;        
    }

    static function hasBtnColor(string $str, string $color = '') : bool{
        return static::hasColor('btn', $str, $color);        
    }

    static function hasAlertColor(string $str, string $color = '') : bool{
        return static::hasColor('alert', $str, $color);        
    }

    static function hasBgColor(string $str, string $color = '') : bool{
        return static::hasColor('bg', $str, $color);        
    }

    static function hasTextColor(string $str, string $color = '') : bool{
        return static::hasColor('text', $str, $color);        
    }

    static function hasBorderColor(string $str, string $color = '') : bool{
        return static::hasColor('border', $str, $color);        
    }


    static function addClass(string $new_class, string &$to){
        if (empty($to)){
            $to = $new_class;
            return;
        }

        if (strpos($to, $new_class) === false){
            $to .= " $new_class";
        }      
    }

    static function addClasses(mixed $new, string &$to){
        if (empty($to)){
            if (is_array($new)){
                $new = implode(' ', $new);
            }

            $to = $new;
            return;
        }

        if (is_string($new)){
            $new = explode(' ', $new);
        }

        foreach ($new as $new_c){
            if (strpos($to, $new_c) === false){
                $to .= " $new_c";
            }  
        }
    }
    

    static function removeClass(string $class, string &$to){
        if (empty($class)){
            return;
        }

        $pos = strpos($to, $class);

        if ($pos !== false){
            if ($pos === 0){
                $to = substr($to, strlen($class)+1);
                return;
            }

            if ($pos + strlen($class) == strlen($to)){     
                $to = substr($to, 0, $pos-1);
            } else {
                $to = substr($to, 0, $pos) . substr($to, $pos + strlen($class)+1);
            }
        }   
    }

    static function addStyle(string $new_rule, string &$to){
        if (empty($to)){
            $to = $new_rule;
            return;
        }

        if (Strings::lastChar(trim($to)) != ';'){
            $to .= ';';
        }      

        $to .= $new_rule;
    }

    /*
        Copy name attribute into id one
    */
    static function setIdAsName(bool $state = true){
        static::$id_eq_name = $state;
    }

    static function attributes(?Array $atts = []) : string{
        if (empty($atts)){
            return '';
        }
        
        $_att = [];
        foreach ($atts as $att => $val){
            if (is_array($val)){
                continue;
                //throw new \InvalidArgumentException(json_encode($atts));
            }

            $_att[] = "$att=\"$val\"";
        }

        return implode(' ', $_att);
    }

    static public function getClass(string $tag) : string{
        return static::$classes[$tag] ?? '';
    }

    static protected function tag(string $type, ?string $val = '', ?Array $attributes = [], Array|string|null $plain_attr = null, ...$args) : string
    {   
        if (isset($args['disabled'])){
            $plain_attr[] = 'disabled';
            static::addClass('disabled', $attributes['class']);
            unset($args['disabled']);
        }

        if (isset($args['readonly'])){
            $plain_attr[] = 'readonly';
            unset($args['readonly']);
        }

        $justif = [
            "center" => "center",
            "left" => "start",
            "right" => "end",

            "smCenter" => "sm-center",
            "smLeft" => "sm-start",
            "smRight" => "sm-end",

            "mdCenter" => "md-center",
            "mdLeft" => "md-start",
            "mdRight" => "md-end",

            "lgCenter" => "lg-center",
            "lgLeft" => "lg-start",
            "lgRight" => "lg-end",

            "xlCenter" => "xl-center",
            "xlLeft" => "xl-start",
            "xlRight" => "xl-end",
        ];        

        $args = $args + $attributes;

        foreach ($args as $k => $v)
        {
            if (!is_array($v)){
                if (Strings::startsWith('justify', $v)){
                    $attributes['class'] = $attributes['class'] ?? '';                    
                    static::addClass($v, $attributes['class']);

                    unset($args[$k]);
                    continue;
                }

                // center, left, right
                foreach ($justif as $jn => $jv){
                    if ($k == $jn){
                        $attributes['class'] = $attributes['class'] ?? '';
                        static::addClass("text-{$jv}", $attributes['class']);
                        
                        unset($args[$k]);
                        continue;
                    }
                }    
            }

            // ajuste para data-* props
            if (strpos($k, '_') !== false){
                unset($args[$k]);
                $k = str_replace('_', '-', $k);                
                $args[$k] = $v;
            }

            // data-* in camelCase
            if (strlen($k) > 4 && (substr($k, 0, 4) == 'data') && ctype_upper($k[4])){
               unset($args[$k]);
               $k = Strings::camelToSnake($k, '-');
               $args[$k] = $v;
            }

            if (isset(static::$classes[$k])){
                $attributes['class'] = !isset($attributes['class']) ? static::$classes[$k] : $attributes['class'] . ' '.static::$classes[$k];
                unset($args[$k]);
            }
        }   

        if (isset($attributes['class'])){
            if (isset($args['class'])){
                static::addClass($args['class'], $attributes['class']);
                unset($args['class']);
            }
        }  // endforeach


        // Borders

        $border = static::getAtt('border', $attributes, $args);
        
        // Sino se llamara a border() sería null en vez de "" (string vacio)
        if ($border !== null){
            if ($border == ""){
                $attributes['class'] = $attributes['class'] ?? '';
                static::addClass('border', $attributes['class']);
            } else {
                $borderAdd = explode(' ', trim(Strings::removeMultipleSpaces($border)));
            
                foreach ($borderAdd as $ba){
                    switch ($ba){
                        case 'left':
                            $ba = 'start';
                            break;
                        case 'right':
                            $ba = 'end';
                            break;
                    }

                    $attributes['class'] = $attributes['class'] ?? '';
                    static::addClass("border-{$ba}", $attributes['class']);
                }           
            }
            
            unset($args['border']);
        }

        // border substraction
        $borderSub = static::getAtt('borderSub', $attributes, $args);

        if ($borderSub !== null){
            $borderSub = explode(' ', trim(Strings::removeMultipleSpaces($borderSub)));
            
            foreach ($borderSub as $bs){
                $attributes['class'] = $attributes['class'] ?? '';
                static::addClass("border-{$bs}-0", $attributes['class']);
            }
           
            unset($args['borderSub']);
        }

        // border-width
        $borderW = static::getAtt('borderWidth', $attributes, $args);
        
        if ($borderW !== null){
            if ($borderW <0 || $borderW > 5){
                throw new \OutOfRangeException("Border-width $borderW is out of range. It should be in range of [1,6]");
            } 

            $attributes['class'] = $attributes['class'] ?? '';

            if ($border === null){
                static::addClass('border', $attributes['class']);
            }

            static::addClass("border-{$borderW}", $attributes['class']);
            unset($args['borderWidth']);
        }

        // border corners que serán redondeados
        $borderCorner = static::getAtt('borderCorner', $attributes, $args);

        if ($borderCorner !== null){
            $borderCorner = explode(' ', trim(Strings::removeMultipleSpaces($borderCorner)));
                
            foreach ($borderCorner as $bc){
                // corrije bug en la librería
                switch ($bc){
                    case 'left':
                        $bc = 'end';
                        break;
                    case 'right':
                        $bc = 'start';
                        break;
                    case 'top':
                        $bc = 'bottom';
                        break;
                    case 'bottom':
                        $bc = 'top';
                        break;
                }

                $attributes['class'] = $attributes['class'] ?? '';
                static::addClass("rounded-{$bc}", $attributes['class']);
            }     
            
            unset($args['borderCorner']);
        }


        // border-radius
        $borderR = static::getAtt('borderRad', $attributes, $args);
        
        if ($borderR !== null){
            if ($borderR <0 || $borderR > 3){
                throw new \OutOfRangeException("Border-radius $borderR is out of range. It should be in range of [1,6]");
            } 

            $attributes['class'] = $attributes['class'] ?? '';

            if ($border === null && $borderCorner === null){
                static::addClass('border', $attributes['class']);
            }

            static::addClass('rounded', $attributes['class']);
            static::addClass("rounded-{$borderR}", $attributes['class']);
            unset($args['borderRad']);
        }

        // border pill
        $borderPill = static::getAtt('borderPill', $attributes, $args);

        if ($borderPill !== null){
            static::addClass("rounded-pill", $attributes['class']);
            unset($args['borderPill']);
        }

        // border circle
        $borderPill = static::getAtt('borderCircle', $attributes, $args);

        if ($borderPill !== null){
            static::addClass("rounded-circle", $attributes['class']);
            unset($args['borderCircle']);
        }


        // Colors

        $color_types = ['text', 'alert', 'btn', 'border'];

        foreach ($color_types as $ct)
        {
            switch ($ct){
                case 'text':
                    $at = 'textColor';
                    break;
                case 'border':
                    $at = 'borderColor';
                    break;
                default:
                    $at = $ct;
            }
            
            $color = $args[$at] ?? null;
            
            if ($color !== null){
                $attributes['class'] = $attributes['class'] ?? '';

                static::addColor("$ct-{$color}", $attributes['class']);
                unset($args[$at]);
            }
        }

        $bg = $args['bg'] ?? null;

        if ($bg !== null){
            $attributes['class'] = $attributes['class'] ?? '';
            static::addBgColor($bg, $attributes['class']);
            unset($args['bg']);
        }


        // Opacity

        $opacity = $args['opacity'] ?? null;

        if ($opacity !== null){
            if ($opacity <0 || $opacity>1){
                throw new \OutOfRangeException("Opacity $opacity is out of range [0,1]");
            }

            $attributes['style'] = $attributes['style'] ?? '';
            static::addStyle("--bs-text-opacity: $opacity;", $attributes['style']);
        }

        $gradient = $args['gradient'] ?? null;

        if ($gradient !== null){
            static::addClass("bg-gradient", $attributes['class']);
            unset($args['gradient']);
        }

        /*
         Sizing
        */

        // w
        $at = $args['w'] ?? null;

        if ($at !== null){
            if (($at <0 || $at>100) && $at != 'auto') {
                throw new \OutOfRangeException("Width $at is out of range [0, 100]%");
            }

            // otra implementación sería agregando la clase w-{porcentaje}

            $attributes['style'] = $attributes['style'] ?? '';
            static::addStyle("max-width: $at%;", $attributes['style']);

            unset($args['w']);
        }

        // h
        $at = $args['h'] ?? null;

        if ($at !== null){
            if (($at <0 || $at>100) && $at != 'auto') {
                throw new \OutOfRangeException("Height $at is out of range [0, 100]%");
            }

            $attributes['class'] = $attributes['class'] ?? '';
            static::addClass("h-$at", $attributes['class']);

            unset($args['h']);
        }

        
        if (isset($attributes['class'])){
            if (isset($args['class'])){
                static::addClass($args['class'], $attributes['class']);
                unset($args['class']);
            } else {
                // no hago nada.
            }
        } else {
            if (isset($args['class'])){
                $attributes['class'] = $args['class'];
                unset($args['class']);
            }
        }
        
        $attributes = array_merge($attributes, $args);

        $name = $attributes['name'] ?? '';

        if (!empty($name) && static::$id_eq_name){
            $attributes['id'] = $name;
        }

        $att_str = static::attributes($attributes);
        $p_atr   = is_array($plain_attr) ? implode(' ', $plain_attr) : '';

        $props = trim("$att_str $p_atr");
        $props = !empty($props) ? ' '.$props : $props;

        // en principio asumo que abre y cierra
        $ret = "<$type{$props}>$val</$type>";

        /*
            Insert something after tag
        */

        $after_tag = $args['after_tag'] ?? $attributes['after_tag'] ?? null;

        if ($after_tag){
            $ret .= $after_tag;
        }

        return static::$pretty ? static::beautifier($ret) : $ret;
    }

    static function group(mixed $content, string $tag = 'div', Array $attributes = [], ...$args){
        if (is_array($content)){
            $content = implode(' ', $content);
        }

        return static::tag($tag, $content, $attributes, null,...$args);
    }

    static function link(string $anchor, ?string $href = null, Array $attributes = [], ...$args){
        //d($attributes);

        if ($href !== null){
            $attributes['href'] = $href;
        }
        
        if (isset($attributes['href'])){
            if (Strings::startsWith('www.', $attributes['href'])){
                $attributes['href'] = "http://" . $attributes['href'];
            }
        }

        $color = $args['color'] ?? $attributes['color'] ?? null;
            
        if ($color !== null){
            $attributes['class'] = $attributes['class'] ?? '';
            static::addColor("link-$color", $attributes['class']);
            unset($args['color']);
        }

        return static::tag('a', $anchor, $attributes, null, ...$args);
    }

    static function input(string $type, ?string $default = null, Array $attributes = [], Array $plain_attr = [], ...$args)
    {  
        if ($type != 'list'){
            $attributes['type']  = $type;
        }

        if (isset($args['large'])){
            static::addClass('form-control-lg', $attributes['class']);
        }

        if (isset($args['small'])){
            static::addClass('form-control-sm', $attributes['class']);
        }

        $plain_attr[] = is_null($default) ? '' : "value=\"$default\""; 
        
        return static::tag('input', null, $attributes, $plain_attr, ...$args);
    }

    static function inputText(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('text', $default, $attributes, ...$args);
    }

    static function password(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('password', $default, $attributes, ...$args);
    }

    static function email(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('email', $default, $attributes, ...$args);
    }

    static function number(string $text = null,  Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('number', $text, $attributes, ...$args);
    }

    static function file(Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        
        $plain = [];
        if (isset($args['multiple']) || isset($attributes['multiple'])){
            $plain = ['multiple'];
            unset($args['multiple']);
        }
        
        return static::input('file', null, $attributes, $plain, ...$args);
    }

    static function date(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('date', $default, $attributes, ...$args); 
    }

    static function month(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('month', $default, $attributes, ...$args); 
    }

    static function inputTime(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('time', $default, $attributes, ...$args); 
    }

    static function week(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('week', $default, $attributes, ...$args); 
    }

    static function datetimeLocal(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('datetime-local', $default, $attributes, ...$args); 
    }

    static function image(?string $default = null, Array $attributes = [], ...$args){
        if (!isset($attributes['src'])){
            throw new \Exception("src attribute is required");
        }

        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        return static::input('image', $default, $attributes, ...$args); 
    }

    static function range(int $min, int $max, $default = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        $attributes['min'] = $min;
        $attributes['max'] = $max;

        $color = $args['color'] ?? $attributes['color'] ?? null;

        if ($color){
            static::addClass("custom-range custom-range-{$color}", $attributes['class']);
            unset($args['color']);
        }

        return static::input('range', $default, $attributes, ...$args); 
    }

    static function tel(string $pattern, Array $attributes = [], ...$args){
        $attributes['patern'] = $pattern;
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('tel', null, $attributes, ...$args); 
    }

    static function url(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('url', $default, $attributes, ...$args); 
    }

    static protected function label(string $for, string $text, Array $attributes = [], ...$args){
        $attributes['for'] = $for;
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass('label') : static::getClass('label');

        return static::tag('label', $text, $attributes, ...$args);
    }

    // implementación especial 
    static function checkbox(?string $text = null,  bool $checked = false, Array $attributes = [], ...$args){
        $plain_attr = $checked ?  ['checked'] : [];
        $attributes['type']  = __FUNCTION__;
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        return static::tag('input', $text, $attributes, $plain_attr, ...$args);
    }

    // implementación especial 
    static function radio(?string $text = null,  bool $checked = false, Array $attributes = [], ...$args){
        $plain_attr = $checked ?  ['checked'] : [];
        $attributes['type']  = __FUNCTION__;
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        $value = '';
        if (!empty($text)){
            if (isset($args['id'])){
                $attributes['id'] = $args['id'];
            }

            if (empty($attributes['id'])){
                throw new \Exception("With radio and placeholder then id is required");
            }

            $value = static::label($attributes['id'], $text);
        }

        return static::tag('input', $value, $attributes, $plain_attr, ...$args);
    }

    static function inputColor(?string $text = null, Array $attributes = [], ...$args){
        $attributes['class']  = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        $attributes['type']  = 'color';

        if (isset($args['id'])) {
            $attributes['id'] = $args['id'];
            unset($args['id']);
        }

        $val = '';
        if (!empty($text)){
            if (empty($attributes['id'])){
                throw new \Exception("With radio and placeholder then id is required");
            }

            $val = static::label($attributes['id'], $text, ...$args);
        }

        return static::tag('input', $val, $attributes, ...$args);
    }

    static function area(?string $default = null, Array $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::tag('textarea', $default, $attributes, ...$args);
    }

    /*
        Form::select(name:'size', options:['L' => 'Large', 'S' => 'Small'], placeholder:Pick a size...']);

        Además acepta un agrupamiento de opciones en "secciones" o "categorías"

        Form::select(name:'comidas', options:[
        'platos' => [
            'Pasta' => 'pasta',
            'Pizza' => 'pizza',
            'Asado' => 'asado' 
        ],

        'frutas' => [
            'Banana' => 'banana',
            'Frutilla' => 'frutilla'
        ],         
        placeholder:'Escoja su comida favoria');


        Ver
        http://paulrose.com/bootstrap-select-sass/
    */
    static function select(Array $options, ?string $default = null, ?string $placeholder = null, Array $attributes = [], ...$args)
    {   
        $attributes['placeholder'] = $placeholder;
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        if (isset($attributes['selected'])) {
            $default = $attributes['selected'];
            unset($attributes['selected']);
        } else {
            if (isset($args['selected'])) {
                $default = $args['selected'];
                unset($args['selected']);
            }
        }

        $a2 = is_array(Arrays::arrayValueFirst($options));

        // options
        $got_selected = false;

        if ($a2){
            $groups = '';
            foreach ($options as $grp){
                $_opt  = [];
                foreach ($grp as $opt => $val){
                    if ($val == $default){
                        $selected = 'selected';
                        $got_selected = true;
                    } else {
                        $selected = '';
                    }
    
                    $_opt[] = "<option value=\"$val\" $selected>$opt</option>";
                }
    
                if (!empty($placeholder)){
                    $selected = !$got_selected;
                    $_opt = array_merge(['<option hidden="hidden" selected="selected">'.$placeholder.'</option>'], $_opt);
                }
            
                $opt_str = implode(' ', $_opt);
                $groups .= static::tag('optgroup', $opt_str, ['label' => $opt]);
            }

            $opt_str = $groups;
        } else {     
            $_opt  = [];       
            foreach ($options as $opt => $val){
                if ($val == $default){
                    $selected = 'selected';
                    $got_selected = true;
                } else {
                    $selected = '';
                }

                $_opt[] = "<option value=\"$val\" $selected>$opt</option>";
            }

            if (!empty($placeholder)){
                $selected = !$got_selected;
                $_opt = array_merge(["<option $selected>$placeholder</option>"], $_opt);
            }
        
            $opt_str = implode(' ', $_opt);
        }

        if (isset($args['large'])){
            static::addClass('form-control-lg', $attributes['class']);
        }

        if (isset($args['small'])){
            static::addClass('form-control-sm', $attributes['class']);
        }
               
        return static::tag(__FUNCTION__, $opt_str, $attributes, ...$args);
    }

    static function dataList(string $listName, Array $options, ?string $placeholder = null, ?string $label = '', Array $attributes = [], ...$args)
    {
        $attributes['placeholder'] = $placeholder;
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        $attributes['list'] = $listName;

        if (isset($args['id'])) {
            $attributes['id'] = $args['id'];
            unset($args['id']);
        }

        // options
        $_opt = [];
        foreach ($options as $val){
            $_opt[] = "<option value=\"$val\"/>";
        }
    
        $opt_str = implode(' ', $_opt);

        $datalist = static::tag(__FUNCTION__, $opt_str, ['id' => $listName]);
        $label_t  = !empty($label) ? static::label($attributes['id'], $label) : '';
        $input    = static::input('list', null, $attributes, ...$args);

        return $label_t . $input . $datalist;
    }

    static function inputButton(string $value, Array $attributes = [], string $type = 'button', ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
            
        $kargs = array_merge(array_keys($args), array_keys($attributes));
 
        if (in_array('large', $kargs)){
            static::addClass('btn-lg', $attributes['class']);
        } else if (in_array('small', $kargs)){
            static::addClass('btn-sm', $attributes['class']);
        }

        $color_applied = false;
        foreach ($kargs as $k){
            if (in_array($k, static::$colors)){
                static::addColor("btn-$k", $attributes['class']); 
                $color_applied = true;
                unset($args[$k]);
                break;
            }          
        }
        
        if (!$color_applied){
            static::addColor("btn-primary", $attributes['class']); 
        }


        if (array_key_exists('placeholder', $args) || in_array('placeholder', $attributes)){
            static::addClass('disabled placeholder w-50', $attributes['class']);
            $value = '';
        }
        
        return static::input($type, $value, $attributes, ...$args);
    } 

    static function submit(string $value, Array $attributes = [], ...$args){
        return static::inputButton($value, $attributes, __FUNCTION__, ...$args);
    }

    static function reset(string $value, Array $attributes = [], ...$args){
        return static::inputButton($value, $attributes, __FUNCTION__, ...$args);
    }

    static function search(Array $attributes  = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::input('search', null, $attributes, ...$args);
    }

    static  function fieldset(mixed $content, $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function hidden(string $value, Array $attributes = [], ...$args){
        return static::input('hidden', $value, $attributes, ...$args);
    }

       /*
        Form::macro('myField', function()
        {
            return '<input type="awesome">';
        });

        Calling A Custom Form Macro

        echo Form::myField();

    */
    static function macro(string $name, callable $render_fn){
        static::$macros[$name] = $render_fn;
    }

    static function __callStatic($method, $args){
        if (isset(static::$macros[$method])){
            return static::$macros[$method](...$args);
        }

        return static::tag($method, '', [], [], ...$args);
    }

    static function div(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function header(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function nav(mixed $content, $attributes = [], ...$args){
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);
        return static::group($content, 'ul', $attributes, ...$args);
    }

    static function main(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function section(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function article(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function aside(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function details(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function summary(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    function mark(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function picture(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function figure(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function figcaption(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    function time(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function footer(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function ol(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function ul(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    /*
        <blockquote>
        <p>Beware of bugs in the above code; I have only proved it correct, not tried it.” </p>
        <cite><a href="http://www-cs-faculty.stanford.edu/~uno/faq.html">Donald Knuth: Notes on the van Emde Boas construction of priority deques: An instructive use of recursion, March 29th, 1977</a>
        </blockquote>
    */
    static function blockquote(mixed $content, $attributes = [], ...$args)
    {   
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        $color = $args['color'] ?? $attributes['color'] ?? null;

        // default shadow
        if (empty($color)){
            $color = 'primary';
        }

        static::addColor("quote-$color", $attributes['class']);

        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function q(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function cite(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function code(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function basicButton(mixed $content = null, $attributes = [], ...$args){
        $attributes['type']="button";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        if ($content === null){
            if (isset($args['text'])){
                $content = $args['text'];
            } elseif (isset($args['value'])){
                $content = $args['value'];
            }
        }

        if (isset($args['large'])){
            static::addClass('btn-lg', $attributes['class']);
        }

        if (isset($args['small'])){
            static::addClass('btn-sm', $attributes['class']);
        }

        foreach ($args as $k => $val){
            if (in_array($k, static::$colors)){
                static::addColor("btn-$k", $attributes['class']); 
                unset($args[$k]);
                break;
            }           
        }

        if ($content === null){
            if (isset($args['text'])){
                $content = $args['text'];
                unset($args['text']);
            } elseif (isset($args['value'])){
                $content = $args['value'];
                unset($args['value']);
            }
        }

        static::removeColors('btn', $args);

        return static::group($content,'button', $attributes, ...$args);
    }

    static function button(mixed $content = [], $attributes = [], ...$args){
        $attributes['type']="button";
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' '. static::getClass(__FUNCTION__) : static::getClass(__FUNCTION__);

        if (isset($args['large'])){
            static::addClass('btn-lg', $attributes['class']);
            unset($args['large']);
        }

        if (isset($args['small'])){
            static::addClass('btn-sm', $attributes['class']);
            unset($args['small']);
        }

        $outline = array_key_exists('outline', $attributes) || array_key_exists('outline', $args);

        $color_applied = false;
        foreach ($args as $k => $val){
            if (in_array($k, static::$colors)){
                static::addColor("btn-$k", $attributes['class'], $outline); 
                $color_applied = true;
                unset($args[$k]);
                break;
            }           
        }

        $bg = $args['bg'] ?? $attributes['bg'] ?? null;

        if (!$color_applied && !static::hasBtnColor($attributes['class']) && $bg == null){
            static::addColor("btn-primary", $attributes['class'], $outline); 
        } 

        $flat = array_key_exists('flat', $attributes) || array_key_exists('flat', $args);

        if ($flat){
            static::addClass('btn-flat', $attributes['class']);
            unset($args['flat']);
        }

        $block = array_key_exists('block', $attributes) || array_key_exists('block', $args);

        if ($block){
            static::addClass('btn-block', $attributes['class']);
            unset($args['block']);
        }

        $icon = $args['icon'] ?? $attributes['icon'] ?? null;

        if ($icon){
            if (is_array($content)){
                $content = implode('', $content);
            }

            $icon    = Strings::replaceFirst('fa-', '', $icon);
            $content = "<i class=\"fa fa-{$icon}\"></i> $content";

            unset($args['icon']);
        }

        // if (isset($args['placeholder'])){
        //     $attributes['placeholder'] = $args['placeholder'];
        // }

        if (empty($content)){
            if (isset($args['text'])){
                $content = $args['text'];
                unset($args['text']);
            } elseif (isset($args['value'])){
                $content = $args['value'];
                unset($args['value']);
            }
        }

        static::removeColors('btn', $args);

        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function p(string $text = '', Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }

    static function li(string $text, Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }
    
    static function span(string $text, Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }

    static function legend(string $text, Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }

    static function strong(string $text, Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }

    static function em(string $text, Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, $text, $attributes, null, ...$args);
    }

    static function h(int $size, string $text, Array $attributes = [], ...$args){
        if ($size <1 || $size > 6){
            throw new \InvalidArgumentException("Incorrect size for H tag. Given $size. Expected 1 to 6");
        }

        return static::tag('h'. (string) $size, $text, $attributes, ...$args);
    }

    static function h1(string $text, Array $attributes = [], ...$args){
        return static::h(1, $text, $attributes, ...$args);
    }

    static function h2(string $text, Array $attributes = [], ...$args){        
        return static::h(2, $text, $attributes, ...$args);
    }

    static function h3(string $text, Array $attributes = [], ...$args){
        return static::h(3, $text, $attributes, ...$args);
    }

    static function h4(string $text, Array $attributes = [], ...$args){
        return static::h(4, $text, $attributes, ...$args);
    }

    static function h5(string $text, Array $attributes = [], ...$args){
        return static::h(5, $text, $attributes, ...$args);
    }

    static function h6(string $text, Array $attributes = [], ...$args){
        return static::h(6, $text, $attributes, ...$args);
    }

    static function br(Array $attributes = [], ...$args){
        return static::tag(__FUNCTION__, null, $attributes, null, ...$args);
    }

    static function img(string $src, Array $attributes = [], ...$args){
        $attributes['src'] = $src;
        return static::tag('img', null, $attributes, null, ...$args); 
    }

    static function table(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function thead(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function tbody(mixed $content, $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function th(mixed $content, Array $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    static function tr(mixed $content, Array $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);;
    }

    static function td(mixed $content, Array $attributes = [], ...$args){
        return static::group($content, __FUNCTION__, $attributes, ...$args);
    }

    /*
        No usar ni htmLawed ni tidy !!!
    */
    static function beautifier(string $html){  
        return $html;
    }

    static function emailTable(mixed $content = [], $attributes = [], ...$args)
    {   
        //....
    }

}

