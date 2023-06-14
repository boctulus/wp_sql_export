<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\DB;


class Response
{
    static protected $data;
    static protected $to_be_encoded;
    static protected $headers = []; 
    static protected $http_code = NULL;
    static protected $http_code_msg = '';
    static protected $instance = NULL;
    static protected $version = '2';
    static protected $config;
    static protected $pretty;
    static protected $paginator;
    static protected $as_object = false;
    static protected $fake_status_codes = false; // send 200 instead
    static protected $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;


    protected function __construct() { 
        static::$config = config();
        static::$pretty = static::$config['pretty'] ?? false;
    }

    public function __destruct()
    {
        DB::closeAllConnections();
    }    

    static function getInstance(){        
        if(static::$instance == NULL){
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    static function redirect(string $url){
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        }else
            throw new \Exception("Headers already sent");
    }

    function asObject(bool $val = true){
        static::$as_object = $val;
    }

    function addHeaders(array $headers)
    {
        static::$headers = $headers;
        return static::getInstance();
    }
  
    function addHeader(string $header)
    {
        static::$headers[] = $header;
        return static::getInstance();
    }

    /**
     * sendHeaders
     *
     * @param  mixed $headers
     *
     * @return void
     */
    private function sendHeaders(array $headers = []) {
        foreach ($headers as $k => $val){
            if (empty($val))
                continue;
            
            header("{$k}:$val");
        }
    }

    function code(int $http_code, string $msg = NULL)
    {
        static::$http_code_msg = $msg;
        static::$http_code = $http_code;
        return static::getInstance();
    }

    function setPretty(bool $state){
        static::$pretty = $state;
        return static::getInstance();
    }

    protected function encode($data){       
        $options = static::$pretty ? static::$options | JSON_PRETTY_PRINT : static::$pretty;
            
        return json_encode($data, $options);  
    }

    function encoded(){
        self::$to_be_encoded = true;
        return static::getInstance();
    }

    function setPaginator(array $p){
        self::$to_be_encoded = true; 
        static::$paginator = $p;
        return static::getInstance();
    }

    function send($data, int $http_code = NULL){
        if ($http_code >= 400) {
            return $this->error($data, $http_code);
        }

        $http_code = $http_code != NULL ? $http_code : (static::$http_code !== null ? static::$http_code : 200);

        if (!headers_sent()) {
            header(trim('HTTP/'.static::$version.' '.$http_code.' '.static::$http_code_msg));
        }    

        if (static::$as_object || is_object($data) || is_array($data)) {
            $arr = [];

            $paginator_position = @static::$config['paginator']['position'] ?? 'BOTTOM';

            if ($paginator_position == 'TOP'){
                if (static::$paginator != NULL)
                    $arr['paginator'] = static::$paginator;
            }

            /*
                Evita tener data.data pero es un cambio disruptivo
            */
            if (isset($data['data'])){
                $data = $data['data'];
            }

            $data = array_merge($arr,[
                    'data' => $data, 
                    'status_code' => $http_code,
                    'error' => []
            ]);

            static::$http_code = $http_code; //

            if ($paginator_position == 'BOTTOM'){                
                if (static::$paginator != NULL)
                    $data['paginator'] = static::$paginator;
            }          
        }     

        static::$instance->set( $data );
        return static::$instance;   	
    }

    private function zip($data){
        $data  = gzcompress($data, 9);

        ob_start("ob_gzhandler");
        echo $data; 
        ob_end_flush();
    } 

    function sendCode(int $http_code){
        static::$instance->set( json_encode(['status_code' => $http_code]) );
          
        if (!static::$fake_status_codes){    
            http_response_code($http_code);
        }   

        static::$http_code = $http_code; //
        
        return static::$instance; 
    }
 

    function sendOK(){
        if (!headers_sent()) {
            http_response_code(200);
        }
        
        return static::$instance; 
    }

    // send as JSON
    function sendJson($data, int $http_code = null, ?string $error_msg = null){
        $http_code = $http_code != null ? $http_code : (static::$http_code !== null ? static::$http_code : 200);
        
        self::$to_be_encoded = true; 

        if (!headers_sent()) {
            header(trim('HTTP/'.static::$version.' '.$http_code.' '.static::$http_code_msg));
        }

        $res = [ 
            'data' => $data, 
            'status_code' => $http_code,
            'error' => $error_msg ?? ''
        ];

        static::$http_code = $http_code; //

        static::$instance->set($res);

        return static::$instance; 
    }


   
    /**
     * error
     *
     *
     * @return void
     */
    function error($error = null, ?int $http_code = null, $detail = null, ?string $location = null){
        if (is_string($error)){
            $message = $error;
        } elseif (is_array($error)){
            $type    = $error['type'] ?? null;
            $message = $error['text'] ?? null;
            $code    = $error['code'];
        }

        if (!is_cli()){
            if (!headers_sent()) {
                if ($http_code == NULL)
                    if (static::$http_code != NULL)
                        $http_code = static::$http_code;
                    else
                        $http_code = 500;
        
                if ($http_code != NULL && !static::$fake_status_codes)
                    header(trim('HTTP/'.static::$version.' '.$http_code.' '.static::$http_code_msg));
            }   
        }
        
        static::$http_code = $http_code; //
        $res['status'] = $http_code;

        /*
            https://www.baeldung.com/rest-api-error-handling-best-practices
        */
        
        // Parche 14-Nov-2022
        
        if (Url::isPostman() || Url::isInsomnia()){
            if (is_string($detail)){
                $detail = trim($detail);

                if ($detail !== null && Strings::isJSON($detail)){
                    $detail = json_decode($detail, true);
                } 
            } 
        }

        $res['error'] = [ 
            'type'    => $type    ?? null,
            'code'    => $code    ?? null,
            'message' => $message,
            'detail'  => $detail
        ];
            
        // static::$instance->set($res);  
        // static::$instance->flush();

        header('Content-type:application/json;charset=utf-8');
        echo json_encode($res);

        exit; ////
    }

    function set($data){
        static::$data = $data;
        return static::$instance; 
    }

    function get(){
        // Parche aplicado el 14-Nov-2022
        
        if (is_array(static::$data)){
            header('Content-type:application/json;charset=utf-8');
            return json_encode(static::$data);
        }

        return static::$data; 
    }

    function __toString()
    {
        return $this->get();
    }

    function isEmpty(){
        return static::$data == null;
    }

    /*
        Este método podría reducirse practicamente a generar distintos
        tipos de excepciones que serían capturadas por un Handler.php

        https://stackoverflow.com/a/30832286/980631
        https://tutsforweb.com/how-to-create-custom-404-page-laravel/
    */
    function flush(){
        if (self::$to_be_encoded){
            static::$data = $this->encode(static::$data);
            header('Content-type:application/json;charset=utf-8');
        } else {
            $accept = request()->header('Accept');

            if (Strings::startsWith('application/json', $accept)){
                self::$to_be_encoded = true;

                static::$data = $this->encode(static::$data);
                header('Content-type:application/json;charset=utf-8');
            }
        }

        $cli = (php_sapi_name() == 'cli');

        if (isset(static::$data['error']) && !empty(static::$data['error'])){
            if (!$cli){
                view('error.php', [
                    'status'    => static::$http_code,
                    'type'      => static::$data['error']['type'],
                    'code'      => static::$data['error']['code'],
                    'location'  => static::$data['error']['location'] ?? '',
                    'message'   => static::$data['error']['message'] ?? '',
                    'detail'    => static::$data['error']['detail'] ?? '',
                ], 'tpl_basic.php');

            } else {
                $message  = static::$data['error']['message'] ?? '--';
                $type     = static::$data['error']['type'] ?? '--';
                $code     = static::$data['error']['code'] ?? '--';
                $detail   = static::$data['error']['detail'] ?? '--';
                $location = static::$data['error']['location'] ?? '--';

                echo "--| Error: \"$message\". -|Type: $type. -|Code: $code -| Location: $location -|Detail: $detail" .  PHP_EOL. PHP_EOL;
            }
            
        } else {
            if (is_array(static::$data) && !self::$to_be_encoded){
                echo $this->encode(static::$data);
            } else {
                echo static::$data; 
            }                            
        }
        
        exit;
    }


    /*
        Ejecuta un callback cuano $cond es verdadero
    */
    function when($cond, $fn, ...$args){
        if ($cond){
            $fn($this, ...$args);
        }
        
        return $this;
    }
}