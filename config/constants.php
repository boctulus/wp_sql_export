<?php

if (!defined('WP_ROOT_PATH'))
    define('WP_ROOT_PATH', realpath(__DIR__ . '/../../../..').  DIRECTORY_SEPARATOR);

if (!defined('ROOT_PATH'))
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

if (!defined('CONFIG_PATH'))
	define('CONFIG_PATH', ROOT_PATH  . 'config' . DIRECTORY_SEPARATOR);

if (!defined('DOCS_PATH'))
	define('DOCS_PATH', ROOT_PATH  . 'docs' . DIRECTORY_SEPARATOR);

if (!defined('UPLOADS_PATH'))
    define('UPLOADS_PATH', WP_ROOT_PATH . 'wp-content'. DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

if (!defined('STORAGE_PATH'))
    define('STORAGE_PATH', ROOT_PATH . 'storage'. DIRECTORY_SEPARATOR);    
    
if (!defined('CACHE_PATH'))
    define('CACHE_PATH', ROOT_PATH . 'cache'. DIRECTORY_SEPARATOR);  

if (!defined('LOGS_PATH'))
    define('LOGS_PATH', ROOT_PATH . 'logs'. DIRECTORY_SEPARATOR); 

if (!defined('VENDOR_PATH'))
    define('VENDOR_PATH', ROOT_PATH . 'vendor'. DIRECTORY_SEPARATOR); 

if (!defined('APP_PATH'))
    define('APP_PATH', ROOT_PATH);

if (!defined('BACKUP_PATH'))
	define('BACKUP_PATH', ROOT_PATH  . 'backup' . DIRECTORY_SEPARATOR);

if (!defined('UPDATE_PATH'))
	define('UPDATE_PATH', ROOT_PATH  . 'updates' . DIRECTORY_SEPARATOR);

if (!defined('CORE_PATH'))
    define('CORE_PATH', APP_PATH . 'core'. DIRECTORY_SEPARATOR);

if (!defined('CORE_INTERFACE_PATH'))
	define('CORE_INTERFACE_PATH', CORE_PATH  . 'interfaces' . DIRECTORY_SEPARATOR);    

if (!defined('CORE_TRAIT_PATH'))
	define('CORE_TRAIT_PATH', CORE_PATH  . 'traits' . DIRECTORY_SEPARATOR);

if (!defined('CORE_LIBS_PATH'))
    define('CORE_LIBS_PATH', CORE_PATH  . 'libs' . DIRECTORY_SEPARATOR);

if (!defined('CORE_HELPERS_PATH'))
    define('CORE_HELPERS_PATH', CORE_PATH  . 'helpers' . DIRECTORY_SEPARATOR);


if (!defined('TEMPLATES_PATH'))
	define('TEMPLATES_PATH', CORE_PATH  . 'templates' . DIRECTORY_SEPARATOR);

if (!defined('MODELS_PATH'))
    define('MODELS_PATH', APP_PATH . 'models'. DIRECTORY_SEPARATOR);   

if (!defined('SCHEMA_PATH')){
    define('SCHEMA_PATH', APP_PATH . 'schemas' . DIRECTORY_SEPARATOR);
}

if (!defined('CRONOS_PATH')){
    define('CRONOS_PATH', APP_PATH . 'jobs/cronjobs' . DIRECTORY_SEPARATOR);
}

if (!defined('TASKS_PATH')){
    define('TASKS_PATH', APP_PATH . 'jobs/tasks' . DIRECTORY_SEPARATOR);
}

if (!defined('MIGRATIONS_PATH'))
    define('MIGRATIONS_PATH', APP_PATH . 'migrations'. DIRECTORY_SEPARATOR);   

if (!defined('ETC_PATH'))
    define('ETC_PATH', APP_PATH . 'etc'. DIRECTORY_SEPARATOR);     

if (!defined('VIEWS_PATH'))
    define('VIEWS_PATH', APP_PATH .  'views' . DIRECTORY_SEPARATOR);  

if (!defined('CONTROLLERS_PATH'))
    define('CONTROLLERS_PATH', APP_PATH . 'controllers' . DIRECTORY_SEPARATOR);    

if (!defined('SECURITY_PATH'))
    define('SECURITY_PATH', STORAGE_PATH . 'security'. DIRECTORY_SEPARATOR);

if (!defined('API_PATH'))
    define('API_PATH', CONTROLLERS_PATH  . 'api' . DIRECTORY_SEPARATOR); 

if (!defined('INTERFACE_PATH'))
	define('INTERFACE_PATH', APP_PATH  . 'interfaces' . DIRECTORY_SEPARATOR); 

if (!defined('LIBS_PATH'))
    define('LIBS_PATH', APP_PATH . 'libs' . DIRECTORY_SEPARATOR);   

if (!defined('TRAIT_PATH'))
    define('TRAIT_PATH', APP_PATH . 'traits' . DIRECTORY_SEPARATOR); 

if (!defined('HELPERS_PATH'))
    define('HELPERS_PATH', APP_PATH . 'helpers' . DIRECTORY_SEPARATOR);  

if (!defined('LOCALE_PATH'))
    define('LOCALE_PATH', APP_PATH . 'locale' . DIRECTORY_SEPARATOR);  

if (!defined('MIDDLEWARES_PATH'))
    define('MIDDLEWARES_PATH', APP_PATH . 'middlewares' . DIRECTORY_SEPARATOR); 

if (!defined('WIDGETS_PATH'))
    define('WIDGETS_PATH', APP_PATH . 'widgets' . DIRECTORY_SEPARATOR);

if (!defined('PUBLIC_PATH'))
    define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR); 

if (!defined('ASSETS_PATH'))
    define('ASSETS_PATH', PUBLIC_PATH . 'assets' . DIRECTORY_SEPARATOR); 

if (!defined('IMAGES_PATH'))
    define('IMAGES_PATH', PUBLIC_PATH . 'images' . DIRECTORY_SEPARATOR);     

if (!defined('CSS_PATH'))
    define('CSS_PATH', PUBLIC_PATH . 'images' . DIRECTORY_SEPARATOR);   

if (!defined('JS_PATH'))
    define('JS_PATH', PUBLIC_PATH . 'js' . DIRECTORY_SEPARATOR);       

if (!defined('SCRIPTS_PATH'))
	define('SCRIPTS_PATH', ROOT_PATH  . 'scripts' . DIRECTORY_SEPARATOR);

if (!defined('CORE_SCRIPTS_PATH'))
	define('CORE_SCRIPTS_PATH', CORE_PATH  . 'scripts' . DIRECTORY_SEPARATOR);