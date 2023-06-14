<?php

function admin_msg_view($message, $severity = 'error') {
    if (!in_array($severity, ['success', 'warning', 'error'])){
       throw new \InvalidArgumentException("Severity value can only be 'success', 'warning' or 'error'");
    }

    if (empty($message)){
        return;
    }

    $notice = <<<EOT
        <div class="notice notice-$severity">
            <p>$message</p>
        </div>
    EOT;
	
	echo $notice;
}


function admin_notice($msg, $severity){
    require_once(ABSPATH . 'wp-admin/includes/screen.php');
    
    add_action('admin_notices', 'admin_msg_view', 10, 2);
    do_action( 'admin_notices', $msg, $severity);
}

