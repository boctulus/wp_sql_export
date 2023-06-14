<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\XML;

/*
    Realmente no tiene utilidad ya que se puede capurar el Throwable
    generado por wp_die()
*/
class WPError 
{
    static function getError($html) {
        $dom = XML::getDomDocument($html);
    
        $xpath = new \DOMXPath($dom);
        $titleNode = $xpath->query('//title[contains(text(), "WordPress › Error")]')->item(0);
        
        if ($titleNode !== null) {
            $messageNode = $xpath->query('//div[@class="wp-die-message"]/p')->item(0);
            if ($messageNode !== null) {
                return $messageNode->textContent;
            }
        }
        
        return false;
    }
}