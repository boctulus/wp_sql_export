<?php 

/*
	@author boctulus
*/

namespace boctulus\SW\core\libs;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

use boctulus\SW\core\libs\Files;

require_once ABSPATH . 'wp-includes/PHPMailer/Exception.php';
require_once ABSPATH . 'wp-includes/PHPMailer/PHPMailer.php';
require_once ABSPATH . 'wp-includes/PHPMailer/SMTP.php';


class Mail 
{
    protected static $mailer      = null;
    protected static $options     = [];
    protected static $errors      = null; 
    protected static $status      = null; 
    protected static $silent      = false;
    protected static $debug_level = null;
    protected static $use_wp_mail = false;

    static function getAdminEmail(){
        return get_option('admin_email');
    }

    static function useWPMail(bool $val){
        static::$use_wp_mail = $val;
    }

    static function errors(){
        return static::$errors;
    }

    static function status(){
        return (empty(static::$errors)) ? 'OK' : 'error';
    }

    // change mailer
    static function setMailer(string $name){
        static::$mailer = $name;
    }

    static function getMailer(){
        global $config;
        return static::$mailer ?? $config['email']['mailer_default'];
    }

    /*
        Overide options
    */
    static function config(Array $options){
        if (!isset($options['SMTPOptions'])){
            static::$options['SMTPOptions'] = $options;
        } else {
            static::$options = $options;
        }
    }

    static function silentDebug($level = null){
        global $config;

        $options = $config['email']['mailers'][ static::getMailer() ];

        if (isset($options['SMTPDebug']) && $options['SMTPDebug'] != 0){
            $default_debug_level = $options['SMTPDebug'];
        }

        $level = static::$debug_level ?? $level ?? $default_debug_level ?? 4;

        static::config([
            'SMTPDebug' => $level
        ]);

        static::$silent = true;
    }

    /*
        level 1 = client; will show you messages sent by the client
        level 2  = client and server; will add server messages, it’s the recommended setting.
        level 3 = client, server, and connection; will add information about the initial information, might be useful for discovering STARTTLS failures
        level 4 = low-level information. 
    */
    static function debug(int $level = 4){
        static::$debug_level = $level;
    }

    /*  
        https://stackoverflow.com/a/39893796/980631

        Ver tambi'en

        https://andres-dev.com/enviar-correos-usando-wp-mail-wordpress/
    */
    static function sendWP(Array $to, $subject = '', $body = '', $attachments = null, Array $from = [], Array $cc = [], Array $bcc = [], Array $reply_to = [])
    {
        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        if (!empty($from['email'])){
            $headers[] = 'From: '. $from['email'];
        }

        if (!empty($reply_to['email'])){
            $headers[] = 'Reply-To: '. $reply_to['email'];
        }

        if (!empty($cc['email'])){
            $headers[] = 'Cc: '. $cc['email'];
        }

        if (!empty($bcc['email'])){
            $headers[] = 'Bcc: '. $bcc['email'];
        }

        return wp_mail(
            $to,
            $subject,
            strip_tags($body),
            $headers,
            $attachments
        );
    }

    static function send(Array $to, $subject = '', $body = '', $attachments = null, Array $from = [], Array $cc = [], Array $bcc = [], Array $reply_to = [], $alt_body = null)
    {
        global  $config;
        
        /*
            Puedo usar wp_mail() 
        */
        
        if (static::$use_wp_mail){
            return static::sendWP($to, $subject, $body, $attachments, $from, $cc, $bcc, $reply_to);
        }

        $body = trim($body);

        if (!Strings::startsWith('<html>', $body)){
            $body = "<html><body>$body</body></html>";
        }

        if (empty($subject)){
            throw new \Exception("Subject is required");
        }

        if (empty($body) && empty($alt_body)){
            throw new \Exception("Body or alt_body is required");
        }

        if (Arrays::is_assoc($to)){
            $to = [ $to ];
        }

        if (Arrays::is_assoc($cc)){
            $cc = [ $cc ];
        }

        if (Arrays::is_assoc($bcc)){
            $bcc = [ $bcc ];
        }

        // if (empty($reply_to)){
        //     $reply_to = $from;
        // }

        $mailer = static::getMailer();

		$mail = new PHPMailer();
        $mail->isSMTP();

        $options = array_merge($config['email']['mailers'][$mailer], static::$options);

        if (static::$debug_level !== null){
            $options['SMTPDebug'] = static::$debug_level;
        }

        foreach ($options as $k => $prop){
			$mail->{$k} = $prop;
        }	

        if (!empty($reply_to)){
            $mail->addReplyTo($reply_to['email'], $reply_to['name'] ?? '');
        }

        $from['email'] = $from['email'] ?? $config['email']['from']['address'] ?? $config['email']['mailers'][$mailer]['Username'];
        $from['mame'] = $from['mame'] ?? $config['email']['from']['name'];

        
        if (!empty($from)){
            $mail->setFrom($from['email'], $from['name'] ?? '');
        }

        foreach ($to as $_to){
            $mail->addAddress($_to['email'], $_to['name'] ?? '');
        }

        $mail->Subject = $subject;
		$mail->msgHTML($body); 
		
		if (!is_null($alt_body)){
            $mail->AltBody = $alt_body;
        }
		
        if (!empty($attachments)){
            if (!is_array($attachments)){
                $attachments = [ $attachments ];
            }

            foreach($attachments as $att){
                $mail->addAttachment($att);    
            }
        }

        if (!empty($cc)){            
            foreach($cc as $_cc){
                $mail->addCC($_cc['email'], $_cc['name'] ?? '');
            }
        }

        if (!empty($bcc)){            
            foreach($bcc as $_bcc){
                $mail->addBCC($_bcc['email'], $_bcc['name'] ?? '');
            }
        }

        if (static::$silent){
            ob_start();
        }
		
        if (!$mail->send())
        {	
            static::$errors = $mail->ErrorInfo;

            if (static::$silent){
                Logger::dump(static::$errors, 'dump.txt', true);
            }

            $ret = static::$errors;
        }else{
            if (static::$silent){
                Logger::dump(true, 'dump.txt', true);
            }

            static::$errors = null;
            $ret =  true;
        }        
                 
        if (static::$silent){
            $content = ob_get_contents();
            ob_end_clean();
        }

        return $ret;
	}
}