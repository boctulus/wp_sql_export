<?php

/*
    Este script provee de la funcion is_admin_page() en el frontend

    Es incluido automaticamente por app.php
*/

function check_if_is_admin(){ 
    $is_admin = (debug_backtrace()[1]['function'] == 'my_admin_enqueue');

    ?>
        <script type="text/javascript">
            let is_admin_page = () => {
                return Boolean('<?= $is_admin ?>');
            }
        </script>
    <?php
}

function my_admin_enqueue($hook_suffix) {
   check_if_is_admin();
}

if (!is_cli()){
    add_action('admin_enqueue_scripts', 'my_admin_enqueue');

    add_action('wp_enqueue_scripts', function () {
        check_if_is_admin();
    }, 1);
}

