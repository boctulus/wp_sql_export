<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Metabox;

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

    $mt = new ProductMetabox($atts);

    $mt->setCallback('Ganancia %', function($pid, $meta_id, &$ganancia){
        $price = Products::getMeta($pid, 'Precio TecnoGlobal');
        $price = $price * (1 + 0.01* $ganancia);

        Products::updatePrice($pid, $price);
    });

    Y setear campos read-only

    Ej:
    
    $mt = new ProductMetabox( [
        'Precio TecnoGlobal',
        'Ganancia %'
    ]);

    $mt->setCallback('Ganancia %', function($pid, $meta_id, &$ganancia){
        $price = Products::getMeta($pid, 'Precio TecnoGlobal');
        $price = $price * Quotes::dollar();
        $price = $price * (1 + 0.01* $ganancia);

        Products::updatePrice($pid, $price);
    });

    $mt->setReadOnly([
        'Precio TecnoGlobal'
    ]);
*/

class ProductMetabox extends Metabox
{
    protected $screen = 'product';

}

