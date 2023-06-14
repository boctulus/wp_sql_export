<?php

namespace boctulus\SW\core\libs;

/*
	@author boctulus
*/

class ApacheWebServer
{
    static function updateHtaccessFile($directives, $path)
    {
        $htaccessPath = rtrim($path, '/') . DIRECTORY_SEPARATOR . (!Strings::endsWith('.htaccess', $path) ? '.htaccess' : '');
        $content = '';
    
        // Verificar si el archivo .htaccess existe
        if (file_exists($htaccessPath)) {
            $content = file_get_contents($htaccessPath);
    
            // Verificar y actualizar las directivas
            foreach ($directives as $directive => $value) {
                $directivePattern = preg_quote($directive, '/');
                $existingDirectivePattern = "/\b$directivePattern\b/";
    
                if (preg_match($existingDirectivePattern, $content)) {
                    // La directiva ya existe, actualizar su valor
                    $content = preg_replace("/\b$directivePattern\b.*/", "$directive $value", $content);
                } else {
                    // La directiva no existe, agregarla al final del archivo
                    $content .= "\nphp_value $directive $value";
                }
            }
    
            // Guardar el contenido actualizado en el archivo .htaccess
            file_put_contents($htaccessPath, $content);
        }
    }    
}


