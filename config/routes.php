<?php

/*
    Routes for Router

    Nota: la ruta mas general debe colocarse al final
*/

return [
    // rutas

    'GET:/wp_sql_export/api/do-export' => 'boctulus\SW\controllers\AjaxController@make_backup',
];
