<?php

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\Users;


add_action('wp_loaded', function(){
    if (defined('WC_ABSPATH') && !is_admin())
    {
        // ..
    }    
});


?>

<script>
    const base_url = '<?= Url::getBaseUrl() ?>'

    const endpoint    = base_url + '/some-slugs' // <-- cambiar
    const verb        = 'POST'
    const dataType    = 'json'
    const contentType = 'application/json'
    const ajax_success_alert = {
        title: "API KEY generada!",
        text: "Siga instrucciones para configurar plugin WP_MUTA",
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

    document.addEventListener('DOMContentLoaded', function() {
        $ = jQuery
              
    
        const do_ajax_call = (key) => {            
            const url = endpoint; 

            let data = {
                // some data
            }

            console.log(`Ejecutando Ajax call`)
            console.log(data)

            loadingAjaxNotification()

            jQuery.ajax({
                url:  url, 
                type: verb,
                dataType: dataType,
                cache: false,
                contentType: contentType,
                data: (typeof data === 'string') ? data : JSON.stringify(data),
                success: function(res) {
                    clearAjaxNotification();

                    console.log('RES', res);
                    
                    //setNotification("Gracias por tu mensaje. Ha sido enviado.");
                    swal(ajax_success_alert);
                },
                error: function(res) {
                    clearAjaxNotification();

                    // if (typeof res['message'] != 'undefined'){
                    //     setNotification(res['message']);
                    // }

                    console.log('RES ERROR', res);
                    //setNotification("Hubo un error. Inténtelo más tarde.");

                    swal(ajax_error_alert);
                }
            });
            
        }

        
    });
</script>


<script>
    addEventListener("DOMContentLoaded", (event) => {
        if (typeof $ === 'undefined' && typeof jQuery !== 'undefined'){
            $ = jQuery
        }

        // ..
    })
</script>



<script>
    /*
   
   */
</script>


