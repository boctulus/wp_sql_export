<?php

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Url;

/*
    Wrapper para Url::consume_api()

    @author Pablo Bozzolo
*/
class ApiClient
{
    const     HTTP_METH_POST   = "POST";
    const     HTTP_METH_GET    = "GET";
    const     HTTP_METH_PATCH  = "PATCH";
    const     HTTP_METH_PUT    = "PUT";
    const     HTTP_METH_DELETE = "DELETE";
    const     HTTP_METH_HEAD   = "HEAD";

    // Request
    protected $url;
    protected $verb;
    protected $req_headers;
    protected $options = [];
    protected $body;
    protected $encode_body;
    protected $max_retries = 1;
    protected $cert_ssl  = null;

    // Response
    protected $response;
    protected $filename;
    protected $res_headers;
    protected $auto_decode;
    protected $status;
    protected $error;

    // Response Info
    protected $effective_url;
    protected $content_type;

    // Cache
    protected $expiration;
    protected $read_only = false;


    function dump(){
        return [
            'url'         => $this->url,
            'verb'        => $this->verb,
            'headers'     => $this->req_headers,
            'options'     => $this->options,
            'body'        => $this->body,
            'encode_body' => $this->encode_body,
            'max_retries' => $this->max_retries,
            'ssl'         => $this->cert_ssl,
        ];
    }

    // alias de dump()
    function dd(){
        return $this->dump();
    }

    function exec(Array $args){
        $this->url         = $args['url'];
        $this->verb        = $args['verb'];
        $this->req_headers = $args['headers'];
        $this->body        = $args['body'];
        $this->options     = $args['options'];
        $this->encode_body = $args['encode_body'];
        $this->max_retries = $args['max_retries'];
        $this->cert_ssl    = $args['ssl'];

        return $this->request($this->url, $this->verb, $this->body, $this->req_headers, $this->options);
    }

    function setUrl($url){
        $this->url = $url;
        return $this;
    }
    
    // alias
    function url($url){
        return $this->setUrl($url);
    }

    function __construct($url = null)
    {
        $this->setUrl($url);
    }

    static function instance($url = null) : ApiClient {
        return new ApiClient($url);
    }

    function readOnly(bool $flag = true){
        $this->read_only = $flag;
        return $this;
    }

    function setHeaders(Array $headers){
        $this->req_headers = $headers;
        return $this;
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

    function setOption($key, $val){
        $this->options[$key] = $val;
        return $this;
    }

    // alias
    function option($key, $val){
        $this->options[$key] = $val;
        return $this;
    }

    function setOptions(Array $options){
        $this->options = $options;
        return $this;
    }

    function addOptions(Array $options){
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    // redirect
    function followLocations($max_redirs = 10){
        $options = [];

        $this->options[CURLOPT_FOLLOWLOCATION] = ($max_redirs > 0);
        $this->options[CURLOPT_MAXREDIRS] = $max_redirs;

        return $this;
    }

    // alas
    function redirect($max_redirs = 10){
        return $this->followLocations($max_redirs);
    }

    function setBody($body, $encoded = true){
        $this->body = $body;
        $this->encode_body = $encoded;
        return $this;
    }

    function setDecode(bool $auto = true){
        $this->auto_decode = $auto;
        return $this;
    }

    // alias
    function decode(bool $val){
        return $this->setDecode($val);
    }

    function noDecode(){
        return $this->setDecode(false);
    }

    /*
        @param $expiration_time int seconds 
    */
    function setCache(int $expiration_time = 60){
        $this->expiration = $expiration_time;
        return $this;
    }

    // alias de setCache()
    function cache(int $expiration_time = 60){
        return $this->setCache($expiration_time);
    }

    function clearCache(){
        unlink($this->getCachePath());
        return $this;
    }

    function getStatus(){
        return $this->status;
    }

    // alias de getStatus()
    function status(){
        return $this->getStatus();
    }

    function getError(){
        return $this->error;
    }

    // alias de getError()
    function error(){
        return $this->error;
    }

    function data(){
        return $this->response;
    }

    function getResponse(?bool $decode = null, ?bool $as_array = null){       
        if ($decode == null){
            $decode = $this->auto_decode;
        }

        if ($as_array == null){
            $as_array = true;
        }

        if ($decode){
            $data = json_decode($this->response, $as_array);
        } else {
            $data = $this->response;
        }   

        $res = [
            'data' => $data,
            'http_code' => $this->status,
            'error' => $this->error
        ];

        return $res;
    }

    function setRetries($qty){
        $this->max_retries = $qty;
        return $this;
    }

    function disableSSL(){
        // dejo claro se aplican settings
        $this->cert_ssl = true;

        $this->options = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ];

        return $this;
    }

    function withoutStrictSSL(){
        return $this->disableSSL();
    }

    /*
        Set SSL certification
    */
    function setSSLCrt(string $crt_path){
        // dejo claro se aplican settings
        $this->cert_ssl = true;

        $this->addOptions([
            CURLOPT_CAINFO => $crt_path,
            CURLOPT_CAPATH => $crt_path,
        ]);
        
        return $this;
    }

    // alias
    function certificate(string $cert_path){
        return $this->setSSLCrt($cert_path);
    }

    function consumeAPI(string $url, string $http_verb, $body = null, ?Array $headers = null, ?Array $options = null, $decode = true, $encode_body = true)
    {
        if (!extension_loaded('curl'))
		{
            throw new \Exception("Curl extension is not enabled");
        }

        if ($headers === null){
            $headers = [];
        } else {
            if (!Arrays::is_assoc($headers)){
                $_hs = [];
                foreach ($headers as $h){
                    list ($k, $v)= explode(':', $h, 2);
                    $_hs[$k] = $v;
                }

                $headers = $_hs;
            }
        }

        if ($options === null){
            $options = [];
        }

        $keys = array_keys($headers);

        $content_type_found = false;
        foreach ($keys as $key){
            if (strtolower($key) == 'content-type'){
                $content_type_found = $key;
                break;
            }
        }

        $accept_found = false;
        foreach ($keys as $key){
            if (strtolower($key) == 'accept'){
                $accept_found = $key;
                break;
            }
        }

        if (!$content_type_found){
            $headers = array_merge(
                [
                    'Content-Type' => 'application/json'
                ],
                ($headers ?? [])
            );
        }

        if ($accept_found) {
            if (Strings::startsWith('text/plain', $headers[$accept_found]) ||
                Strings::startsWith('text/html', $headers[$accept_found])){
                $decode = false;
            }
        }

        if ($encode_body && is_array($body)){
            $data = json_encode($body);
        } else {
            $data = $body;
        }

        $curl = curl_init();

        $http_verb = strtoupper($http_verb);

        if ($http_verb != 'GET' && !empty($data)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            if ($encode_body){
                $headers['Content-Length']   = strlen($data);
            }
        }

        $h = [];
        foreach ($headers as $key => $header){
            $h[] = "$key: $header";
        }

        $options = [
            CURLOPT_HTTPHEADER => $h
        ] + ($options ?? []);

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '' );
        curl_setopt($curl, CURLOPT_TIMEOUT, 0 );

        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_verb);

        // https://stackoverflow.com/a/6364044/980631
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_HTTP200ALIASES, [
            400,
            500
        ]);  //


        $__headers  = [];
        $__filename = null;

        $header_fn = function ($cURLHandle, $header) use (&$__headers, &$__filename) {
            $pieces = explode(":", $header);

            if (count($pieces) >= 2)
                $__headers[trim($pieces[0])] = trim($pieces[1]);


            if (isset($__headers['Content-Disposition'])){
                if (preg_match('/filename="([a-z-_.]+)";/i', $__headers['Content-Disposition'], $matches)){
                    $__filename= $matches[1];
                }
            }

            return strlen($header); // <-- this is the important line!
        };

        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            $header_fn
        );

        $response  = curl_exec($curl);
        $err_msg   = curl_error($curl);
        $http_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $content_type  = curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
        $effective_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        curl_close($curl);

        $data = ($decode && $response !== false) ? json_decode($response, true) : $response;

        $ret = [
            'data'          => $data,
            'http_code'     => $http_code,
            'error'         => $err_msg
        ];

        $this->res_headers   = $__headers;
        $this->filename      = $__filename;
        $this->content_type  = $content_type;
        $this->effective_url = $effective_url;

        return $ret;
    }

    function getHeaders(){
        return $this->req_headers;
    }

    function getContentType(){
        return $this->content_type;
    }

    function getEffectiveUrl(){
        return $this->effective_url;
    }

    function request(string $url, string $http_verb, $body = null, ?Array $headers = null, ?Array $options = null){
        $this->url  = $url;
        $this->verb = strtoupper($http_verb);

        //
        // Sino se aplico nada sobre SSL, vale lo que diga el config
        // 
        if (!$this->cert_ssl){    
            $cert = config()['ssl_cert'];
            
            if ($cert === false){
                $this->disableSSL();
            }

            if (!empty($cert)){
                $this->setSSLCrt($cert);
            }    
        }

        if (!empty($this->options) && !empty($options)){
            $options = array_merge($this->options, $options);
        } else {
            $options = $options ?? $this->options ?? null;
        }

        $body    = $body    ?? $this->body    ?? null;
        $headers = $headers ?? $this->req_headers ?? null;        
        $decode  = $this->auto_decode; 

        if ($this->expiration == null){
            $expired = true;
        } else {
            $cached_path     = $this->getCachePath();
            $expired         = Cache::expiredFile($cached_path, $this->expiration);    
        }
       
        if (!$expired){
            $res = $this->getCache();

            if ($res !== null){
                if (is_string($res)){
                    //dd('DECODING...');
                    $data = json_decode($res['data'], true); 
                    
                    if ($data !== null){
                        //throw new \Exception("Unable to decode response '$res'");
                        $res['data'] = $data;
                    } else {
                        //dd('DECODED!');
                    }
                }
                
                $this->status   = $res['http_code'];
                $this->error    = $res['error'];
                $this->response = $res['data'];

                return $this;
            }
        }

        $ok = null;
        $retries = 0;

        /*
            Con cada intento podría ir incrementando el tiempo máximo para conectar y para obtener resultados
            Esos valores ¨optimos¨ podrían persistirse en un transiente para la url 
        */
        while (!$ok && $retries < $this->max_retries)
        {   
            $res = $this->consumeAPI($url, $http_verb, $body, $headers, $options, false, $this->encode_body);
            $this->status   = $res['http_code'];
            $this->error    = $res['error'];
            $this->response = $res['data'];

            $this->filename     = $this->getFilename();
            $this->res_headers  = $this->getHeaders();

            /*
                Si hay errores && el status code es 0 
                =>
                Significa que fall'o la conexion!

                --| STATUS
                0

                --| ERRORS
                Failed to connect to 200.6.78.1 port 80: Connection refused

                --| RES
                NULL
            */

            $ok = empty($this->error);
            $retries++;

            //d($ok ? 'ok' : 'fail', 'INTENTOS: '. $retries);
        }

        // dd($res, 'RES');

        if ($this->expiration && $res !== null && !$this->read_only){
            $this->saveResponse($res);
        }

        return $this;
    }

    function get($url = null, ?Array $headers = null, ?Array $options = null){        
        return $this->request($this->url ?? $url, 'GET', null, $headers, $options);
    }

    function delete($url = null, ?Array $headers = null, ?Array $options = null){
        return $this->request($this->url ?? $url, 'DELETE', null, $headers, $options);
    }

    function post($url = null, $body = null, ?Array $headers = null, ?Array $options = null){
        return $this->request($this->url ?? $url, 'POST', $body, $headers, $options);
    }

    function put($url = null, $body = null, ?Array $headers = null, ?Array $options = null){
        return $this->request($this->url ?? $url, 'PUT', $body, $headers, $options);
    }

    function patch($url = null, $body = null, ?Array $headers = null, ?Array $options = null){
        return $this->request($this->url ?? $url, 'PATCH', $body, $headers, $options);
    }
    
    function setMethod(string $verb){
        if (!in_array($verb, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])){
            throw new \InvalidArgumentException("Unsupported verb \"$verb\"");
        }

        $this->verb = $verb;
        return $this;
    }

    function send($url = null, $body = null, ?Array $headers = null, ?Array $options = null){
        return $this->request($url ?? $this->url, $this->verb, $body, $headers, $options);
    }

    function getBody(){
        return $this->data();
    }

    /*
        Authentication
    */

    // BASIC
    function setBasicAuth($username, $password){
        $this->setHeaders([
            'Authorization: Basic '. base64_encode("$username:$password")
        ]);

        return $this;
    }

    // JWT
    function setJWTAuth($token_jwt){
        $this->setHeaders([
            "Authorization: Bearer $token_jwt"
        ]);

        return $this;
    }

    function getFilename(){
        return $this->filename;
    }

    /*
        CACHE

        En vez de guardar en disco..... usar Transientes con drivers como Memcached o REDIS !
    */

    function getCachePath(){
        static $path;

        if (empty($this->url)){
            throw new \Exception("Undefined url");
        }

        if (isset($path[$this->url])){
            return $path[$this->url];
        }

        $filename = str_replace(['%'], ['p'], urlencode(Url::normalize($this->url))) . '.php';
        $filename = str_replace('/', '', $filename);

        // Evito problemas con nombres largos
        if (strlen($filename) > 250){
            return null;
        }

        $path[$this->url] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        return $path[$this->url];
    }

	protected function saveResponse(Array $response){
        if ($this->verb != 'GET'){
            return;
        }

        $path = $this->getCachePath();

        if ($path === null){
            return;
        }

        file_put_contents($path, '<?php return ' . var_export($response, true) . ';');
    }

    protected function getCache(){
        $path = $this->getCachePath();

        if ($path === null){
            return;
        }

        if (file_exists($path)){
            return include $path;
        }
    }

    /*

        Tomado de CodeIgniter ---------------------------------------->

    */

    public function simpleFtpGet($url, $file_path, $username = '', $password = '')
	{
		// If there is no ftp:// or any protocol entered, add ftp://
		if ( ! preg_match('!^(ftp|sftp)://! i', $url))
		{
			$url = 'ftp://' . $url;
		}

		// Use an FTP login
		if ($username != '')
		{
			$auth_string = $username;

			if ($password != '')
			{
				$auth_string .= ':' . $password;
			}

			// Add the user auth string after the protocol
			$url = str_replace('://', '://' . $auth_string . '@', $url);
		}

		// Add the filepath
		$url .= $file_path;

		//$this->option(CURLOPT_BINARYTRANSFER, TRUE);
		$this->option(CURLOPT_VERBOSE, TRUE);

		return $this->get();
	}

	/* =================================================================================
	 * ADVANCED METHODS
	 * Use these methods to build up more complex queries
	 * ================================================================================= */

	public function setCookies($params = array())
	{
		if (is_array($params))
		{
			$params = http_build_query($params, '', '&');
		}

		$this->option(CURLOPT_COOKIE, $params);
		return $this;
	}

	public function httpHeader($header, $content = NULL)
	{
		$this->req_headers[] = $content ? $header . ': ' . $content : $header;
		return $this;
	}

	public function httpMethod($method)
	{
		$this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		return $this;
	}

	public function httpLogin($username = '', $password = '', $type = 'any')
	{
		$this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
		$this->option(CURLOPT_USERPWD, $username . ':' . $password);
		return $this;
	}

	public function proxy($url = '', $port = 80)
	{
		$this->option(CURLOPT_HTTPPROXYTUNNEL, TRUE);
		$this->option(CURLOPT_PROXY, $url . ':' . $port);
		return $this;
	}

	public function proxyLogin($username = '', $password = '')
	{
		$this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
		return $this;
	}

	public function isEnabled()
	{
		return function_exists('curl_init');
	}


}

