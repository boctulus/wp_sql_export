<?php

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Plugins;


/*
    WP SQL EXPORT < by boctulus >
*/

/*
    WooCommerce es una dependencia en este proyecto.
*/

if (!Plugins::isActive('woocommerce')){
    admin_notice("WooCommerce es requerido. Por favor instale y/o habilite el plugin", "error");
}

function assets(){
    css_file('/css/styles.css');

    js_file('/third_party/sweetalert2/sweetalert.js');

    js_file('/js/utilities.js');
    js_file('/js/notices.js');
    js_file('/js/storage.js');
}

enqueue_admin('assets');


// Acción que se ejecuta cuando se activa el plugin
register_activation_hook(__FILE__, 'wp_sql_export_activate');
function wp_sql_export_activate()
{
    // Crea la carpeta /etc en el directorio del plugin si no existe
    $etc_dir = plugin_dir_path(__FILE__) . 'etc';
    if (!file_exists($etc_dir)) {
        mkdir($etc_dir);
    }
}

// Acción que se ejecuta cuando se desactiva el plugin
register_deactivation_hook(__FILE__, 'wp_sql_export_deactivate');
function wp_sql_export_deactivate()
{
    // Realiza cualquier limpieza necesaria al desactivar el plugin
}

// Acción que se ejecuta cuando se desinstala el plugin
register_uninstall_hook(__FILE__, 'wp_sql_export_uninstall');
function wp_sql_export_uninstall()
{
    // Elimina la carpeta /etc y cualquier otro archivo relacionado al desinstalar el plugin
    $etc_dir = plugin_dir_path(__FILE__) . 'etc';
    if (file_exists($etc_dir)) {
        // Elimina todos los archivos en la carpeta /etc
        $files = glob($etc_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        // Elimina la carpeta /etc
        rmdir($etc_dir);
    }
}


// Agrega un enlace al menú de administración para realizar el backup
add_action('admin_menu', 'wp_sql_export_admin_menu');
function wp_sql_export_admin_menu()
{
    add_submenu_page('tools.php', 'WP SQL Export', 'WP SQL Export', 'manage_options', 'wp_sql_export', 'wp_sql_export_page');
}

// Página de administración para realizar el backup
function wp_sql_export_page()
{
    ?>

    <style>
        #dump_command{
            margin-top: 1em;
            margin-bottom: 1em;
            width: 40em;
        }

        #backup_button {
            margin-top: 1em;
        }
    </style>

    <script>

        const base_url = '<?= Url::getBaseUrl() ?>'

        const endpoint    = base_url + '/wp_sql_export/api/do-export' // <-- ajustar
        const verb        = 'GET'
        // const dataType    = 'json'
        const contentType = 'application/json'
        const ajax_success_alert = {
            title: "Hecho",
            text: "Exportacion exitosa",
            icon: "success",
        }
        const ajax_error_alert   = {
            title: "Error",
            text: "Hubo un error. Intente más tarde.",
            icon: "warning", // "warning", "error", "success" and "info"
        }

        function setNotification(msg) {
            jQuery('#response-output').show()
            jQuery('#response-output').html(msg);
        }

        /*
            Agregado para el "loading,.." con Ajax
        */

        function loadingAjaxNotification() {
            <?php $path = asset('images/loading.gif') ?>
            document.getElementById("loading-text").innerHTML = "<img src=\"<?= $path ?>\" style=\"transform: scale(0.5);\" />";
        }

        function clearAjaxNotification() {
            document.getElementById("loading-text").innerHTML = "";
        }

        // ..

        const do_ajax_call = () => {            
            const url = endpoint; 

            console.log(`Ejecutando Ajax call`)
            //console.log(data)

            // loadingAjaxNotification()

            jQuery.ajax({
                url:  url, 
                type: verb,
        
                cache: false,
                contentType: contentType,
                // dataType: dataType,
                // data: (typeof data === 'string') ? data : JSON.stringify(data),
                success: function(res) {
                    // clearAjaxNotification();

                    console.log('RES', res);

                    if (typeof res['message'] != 'undefined'){
                        let msg = res['message'];
                        
                        // setNotification(msg);
                    
                    } else {
                        //setNotification("Gracias por tu mensaje. Ha sido enviado.");
                        swal(ajax_success_alert);
                    }
                    
                    
                },
                error: function(res) {
                    // clearAjaxNotification();

                    // if (typeof res['message'] != 'undefined'){
                    //     setNotification(res['message']);
                    // }

                    console.log('RES ERROR');
                    console.log(res.responseText)
                    //setNotification("Hubo un error. Inténtelo más tarde.");

                    swal(ajax_error_alert);
                }
            });        
        }

    </script>

    <div class="wrap">
        <h1>WP SQL Export</h1>
       
        <?php
        // Obtén el comando de backup
        $command = wp_sql_export_database();
        ?>

        <textarea rows="5" cols="50" id="dump_command"><?php echo esc_textarea($command); ?></textarea>

        <p>Haz clic en el siguiente enlace para realizar el backup de la base de datos y exportar las imágenes de la galería.</p>

        <p><a class="button button-primary" id="backup_button" href="#" onclick="do_ajax_call()">Realizar Backup</a></p>

        <div id="response-output"></div>
    </div>

    <?php
}



//Template::set('kadence');


// Page::replaceContent(function(&$content){
//     $content = preg_replace('/Mi cuenta/', "CuentaaaaaaaX", $content);
// });





