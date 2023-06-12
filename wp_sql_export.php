<?php

// wp_sql_export.php

/*
    Plugin Name: WP SQL Export
    Description: Plugin para realizar backup de la base de datos y exportar imágenes de la galería.
    Author: Pablo Bozzolo
*/

require __DIR__ . '/config/constants.php';
require __DIR__ . '/helpers/db_export.php';


// Agrega una función para registrar y encolar el archivo JavaScript de SweetAlert
add_action('admin_enqueue_scripts', 'wp_sql_export_enqueue_sweetalert');
function wp_sql_export_enqueue_sweetalert()
{
    // Ruta relativa al archivo JavaScript de SweetAlert
    $sweetalert_js = plugin_dir_url(__FILE__) . 'assets/third_party/sweetalert2/sweetalert.js';

    // Registra y encola el archivo JavaScript de SweetAlert
    wp_register_script('sweetalert', $sweetalert_js, array(), '1.0.0', true);
    wp_enqueue_script('sweetalert');
}

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

// Función para realizar el backup de la base de datos y exportar imágenes de la galería
function wp_sql_export_backup()
{
    global $wpdb;

    $sql_file = $wpdb->dbname; // debe coincidir con el nombre de la DB en WP

    // Realiza el backup de la base de datos
    $command = wp_sql_export_database();

    // Exporta las imágenes de la galería
    wp_sql_export_gallery_images();

    // Descarga el archivo de respaldo al finalizar el proceso
    wp_sql_export_download_backup($sql_file);

    // Envía la respuesta
    wp_send_json_success($command);
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
    // URL para realizar el backup
    $backup_url = admin_url('admin-ajax.php?action=wp_sql_export_backup');
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

    <div class="wrap">
        <h1>WP SQL Export</h1>
       
        <?php
        // Obtén el comando de backup
        $command = wp_sql_export_database();
        ?>

        <textarea rows="5" cols="50" id="dump_command"><?php echo esc_textarea($command); ?></textarea>

        <p>Haz clic en el siguiente enlace para realizar el backup de la base de datos y exportar las imágenes de la galería.</p>

        <p><a class="button button-primary" id="backup_button" href="<?php echo esc_url($backup_url); ?>">Realizar Backup</a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var backupButton = document.getElementById('backup_button');
        var dumpCommandTextarea = document.getElementById('dump_command');

        backupButton.addEventListener('click', function (event) {
            event.preventDefault();

            // Realizar la solicitud AJAX para obtener el comando
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Actualizar el contenido del textarea con el comando
                        dumpCommandTextarea.value = xhr.responseText;

                        swal({
                            title: "Exportación exitosa!",
                            icon: "success",
                        });
                    } else {
                        // Mostrar el detalle del error en la alerta
                        var errorDetail = "Error desconocido";

                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.hasOwnProperty('detail')) {
                                errorDetail = response.detail;
                            }
                        } catch (e) {
                            // No se pudo analizar la respuesta JSON, se mantiene el mensaje de error genérico
                        }

                        swal({
                            title: "Error en la exportación",
                            text: errorDetail,
                            icon: "warning",
                        });
                    }
                }
            };
            xhr.open('GET', backupButton.href);
            xhr.send();
        });
    });
    </script>
    <?php
}
