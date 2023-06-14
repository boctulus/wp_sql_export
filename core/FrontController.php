<?php

namespace boctulus\SW\core;

use boctulus\SW\core\libs\Url;
use boctulus\SW\core\libs\Request;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Response;


class FrontController
{
    const DEFAULT_ACTION = "index";

    static function resolve()
    {
        global $argv;

        $config = config();

        $res    = Response::getInstance();

        if (php_sapi_name() != 'cli') {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = preg_replace('/(.*)\/index.php/', '/', $path);

            /*
                La idea es tener ciertas rutas relativas para las cuales no se intente interpretar como Controller
            */

            $allowed_paths = [
                '/app/views/'
            ];

            foreach ($allowed_paths as $ok_path) {
                if (Strings::startsWith($ok_path, $path)) {
                    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
                    $path = Strings::removeTrailingSlash(ROOT_PATH) . $path;

                    include $path;

                    // evito siga el flujo normal
                    return;
                }
            }

            if ($path === false || !Url::urlCheck($_SERVER['REQUEST_URI'])) {
                $res->error("MALFORMED_URL", 400);
            }

            $_params = explode('/', $path);

            if (empty($_params[0]))
                array_shift($_params);
        } else {
            $_params = array_slice($argv, 1);
        }

        if (!isset($_params[0])) {
            return; // *
        }

        $req = Request::getInstance();
    
        //dd($_params, 'PARAMS:');

        /*
            Parche para WP porque no existe un default controller 
            porque este seria ejecutar WP sin controladores
        */
        if (empty($_params) || (count($_params) == 1 && empty($_params[0]))){
            return;
        }


        $namespace = 'boctulus\\SW\\controllers\\';

        if (empty($_params) || $_params[0] == '') {
            $class_file = substr($config['default_controller'], 0, strlen($config['default_controller']) - 10);
            $class_name = Strings::snakeToCamel($class_file);
            $class_name = "{$namespace}{$class_name}Controller";
            $method = self::DEFAULT_ACTION;
            $params = [];
        } else {
            // Hipótesis
            $ix = 0;
            $folder = '';
            $controller = $_params[$ix];

            $class_file =  CONTROLLERS_PATH . Strings::snakeToCamel($controller) . 'Controller.php';
            $cnt  = count($_params) - 1;
            while (!file_exists($class_file) && ($ix < $cnt)) {
                $ix++;
                $folder = implode(DIRECTORY_SEPARATOR, array_slice($_params, 0, $ix)) . DIRECTORY_SEPARATOR;
                //dd($folder, 'NAMESPACE:');

                if (is_numeric($_params[$ix])) {
                    break;
                }

                $controller = $_params[$ix];
                $class_file =  CONTROLLERS_PATH . $folder . Strings::snakeToCamel($controller) . 'Controller.php';;
            }

            // dd($class_file, "Probando ...");

            $action = $_params[$ix + 1] ?? null;
            $params = array_slice($_params, $ix + 2);
            $req->setParams($params);

            $method = !empty($action) ? $action : self::DEFAULT_ACTION;

            $class_name = Strings::snakeToCamel($controller);
            $class_name = "{$namespace}{$folder}{$class_name}Controller";

            // dd($class_name, 'CLASS_NAME:');
            // dd($method, 'METHOD:');
        }

        if (is_numeric($class_name) || is_numeric($method)){
            return;
        }

        // dd($class_name, 'CLASS_NAME:');
        // dd($method, 'METHOD:');

        $class_name = str_replace('/', "\\", $class_name);

        if (!class_exists($class_name)) {
            return; // *
        }

        if (!method_exists($class_name, $method)) {
            if (php_sapi_name() != 'cli' || $method != self::DEFAULT_ACTION) {
                /*
                    Se agrego is_callable() para poder usarse con __call()
                */
                if (!method_exists($class_name, '__call') || !is_callable($class_name, $method)) {
                    return; // *
                }
            } else {
                $dont_exec = true;
            }
        }

        $controller_obj = new $class_name();

        if (isset($dont_exec)) {
            exit;
        }

        $data = call_user_func_array([$controller_obj, $method], $params);

        // Devolver algo desde un controlador sería equivalente a enviarlo como respuesta
        if (!empty($data)) {
            $res->set($data);
        }

        $res->flush();
        exit;
    }
}
