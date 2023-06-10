<?php

// Función para realizar el backup de la base de datos
function wp_sql_export_database() 
{    
    // Nombre del archivo de backup
    $backup_file = ETC_PATH . 'database_backup.sql';
    
    // Verifica la plataforma del sistema operativo
    $platform = strtoupper( substr( PHP_OS, 0, 3 ) );
    
    // Comprueba si la herramienta mysqldump está en el PATH
    $mysqldump_command = '';
    if ( $platform === 'WIN' ) {
        // Para Windows, se busca el archivo mysqldump.exe en la variable de entorno PATH
        // exec( 'where mysqldump.exe', $output, $return_code );
        // if ( $return_code === 0 ) {
            $mysqldump_command = 'mysqldump.exe';
        // }
    } else {
        // Para Linux y macOS, se busca el comando mysqldump en el PATH
        // exec( 'command -v mysqldump', $output, $return_code );
        // if ( $return_code === 0 ) {
            $mysqldump_command = 'mysqldump';
        // }
    }
    
    // Verifica si se encontró el comando mysqldump
    if ( empty( $mysqldump_command ) ) {
        // Mostrar un mensaje de error si no se encontró el comando mysqldump
        wp_die( 'El comando mysqldump no se encontró en el PATH. Asegúrate de que la herramienta de línea de comandos de MySQL esté instalada y accesible desde el servidor web.' );
    }
    
    // Genera el comando para crear el archivo de backup
    $command = $mysqldump_command . ' --user=' . DB_USER . ' -p="' . DB_PASSWORD . '" --host=' . DB_HOST . ' ' . DB_NAME . ' > ' . $backup_file;

    // Ejecuta el comando
    exec( $command, $output );
    file_put_contents('output.txt', $output);
}

// Función para exportar imágenes de la galería
function wp_sql_export_gallery_images() {
    // Obtén todas las imágenes adjuntas
    $attachments = get_posts( array(
        'post_type'      => 'attachment',
        'posts_per_page' => -1,
    ) );
    
    // Verifica si hay imágenes adjuntas
    if ( $attachments ) {
        // Carpeta de destino para las imágenes exportadas
        $export_dir = plugin_dir_path( __FILE__ ) . 'etc/images';
        
        // Crea la carpeta si no existe
        if ( ! file_exists( $export_dir ) ) {
            mkdir( $export_dir );
        }
        
        // Itera sobre cada imagen adjunta y la exporta
        foreach ( $attachments as $attachment ) {
            // Ruta de la imagen adjunta
            $attachment_file = get_attached_file( $attachment->ID );
            
            // Ruta de destino para la imagen exportada
            $export_file = $export_dir . '/' . basename( $attachment_file );
            
            // Copia la imagen adjunta al destino
            copy( $attachment_file, $export_file );
        }
    }
}
