<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\SW\core\libs;

class Taxes {
    static function VATapplied(){
        return wc_prices_include_tax();
    }

}


