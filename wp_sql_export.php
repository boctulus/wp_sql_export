<?php
/*
Plugin Name: wp_sql_export
Description: Plugin para realizar backup de la base de datos y exportar imágenes de la galería.
*/

require __DIR__ . '/config/constants.php';
require __DIR__ . '/helpers/db_export.php';

// Acción que se ejecuta cuando se activa el plugin
register_activation_hook( __FILE__, 'wp_sql_export_activate' );
function wp_sql_export_activate() {
    // Crea la carpeta /etc en el directorio del plugin si no existe
    $etc_dir = plugin_dir_path( __FILE__ ) . 'etc';
    if ( ! file_exists( $etc_dir ) ) {
        mkdir( $etc_dir );
    }
}

// Acción que se ejecuta cuando se desactiva el plugin
register_deactivation_hook( __FILE__, 'wp_sql_export_deactivate' );
function wp_sql_export_deactivate() {
    // Realiza cualquier limpieza necesaria al desactivar el plugin
}

// Acción que se ejecuta cuando se desinstala el plugin
register_uninstall_hook( __FILE__, 'wp_sql_export_uninstall' );
function wp_sql_export_uninstall() {
    // Elimina la carpeta /etc y cualquier otro archivo relacionado al desinstalar el plugin
    $etc_dir = plugin_dir_path( __FILE__ ) . 'etc';
    if ( file_exists( $etc_dir ) ) {
        // Elimina todos los archivos en la carpeta /etc
        $files = glob( $etc_dir . '/*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        // Elimina la carpeta /etc
        rmdir( $etc_dir );
    }
}

// Acción para realizar el backup de la base de datos y exportar imágenes de la galería
add_action( 'admin_init', 'wp_sql_export_backup' );
function wp_sql_export_backup() {
    // Verifica si se ha enviado una solicitud para exportar la base de datos y las imágenes
    if ( isset( $_GET['wp_sql_export'] ) && $_GET['wp_sql_export'] === 'backup' ) {
        // Realiza el backup de la base de datos
        wp_sql_export_database();
        
        // Exporta las imágenes de la galería
        wp_sql_export_gallery_images();
        
        // Redirecciona a la página de plugins después de finalizar el proceso
        wp_safe_redirect( admin_url( 'plugins.php' ) );
        exit;
    }
}

// Agrega un enlace al menú de administración para realizar el backup
add_action( 'admin_menu', 'wp_sql_export_admin_menu' );
function wp_sql_export_admin_menu() {
    add_submenu_page( 'tools.php', 'WP SQL Export', 'WP SQL Export', 'manage_options', 'wp_sql_export', 'wp_sql_export_page' );
}

// Página de administración para realizar el backup
function wp_sql_export_page() {
    // URL para realizar el backup
    $backup_url = add_query_arg( array(
        'page'           => 'wp_sql_export',
        'wp_sql_export'  => 'backup',
    ), admin_url( 'tools.php' ) );
    ?>
    <div class="wrap">
        <h1>WP SQL Export</h1>
        <p>Haz clic en el siguiente enlace para realizar el backup de la base de datos y exportar las imágenes de la galería.</p>
        <p><a class="button button-primary" href="<?php echo esc_url( $backup_url ); ?>">Realizar Backup</a></p>
    </div>
    <?php
}
