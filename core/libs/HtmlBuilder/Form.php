<?php

namespace boctulus\SW\core\libs\HtmlBuilder;

/*
    Falta decorar con las clases de Boostrap 5
*/
class Form extends Html
{
    function render(?string $enclosingTag = 'form', Array $attributes = [], ...$args) : string {
        return parent::render($enclosingTag, $attributes, ...$args);
    }
}

