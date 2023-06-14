<?php

use boctulus\SW\core\libs\DB;
use boctulus\SW\models\MyModel;
use boctulus\SW\core\libs\Files;
use boctulus\SW\core\libs\Model;
use boctulus\SW\core\libs\Logger;
use boctulus\SW\core\libs\StdOut;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\controllers\MakeControllerBase;

function log_db_truncate(){
    DB::statement("TRUNCATE `mysql`.`general_log`");
}

function log_db_dump(bool $to_file = false){
    DB::getConnection();

    DB::statement("USE `mysql`;");

    $rows = table('general_log')
    ->removePrefix(get_db_prefix())
    ->get();

    if ($to_file){
        Logger::dump($rows, 'sql_log.txt');
    }

    return $rows;
}

function log_db_on(){
    log_db_truncate();
    DB::statement("SET global general_log = 'ON';");
}

function log_db_off(){
    DB::statement("SET global general_log = 'OFF';");
}

/*
    En Windows al menos, solo esta funcionando hacia tabla 
    porque crea el archivo pero luego no ingresa nada

    La ruta aparentemente debe tener el formato de Linux aun en Windows

    E:

    /www/woo4/wp-content/plugins/droppishop/logs/mysql_log.txt
*/
function log_db_start($filename = null){
    log_db_on();

    if ($filename == null){
        DB::statement("SET global log_output = 'table';"); // ok -> mysql.general_log
    } else {
        DB::statement("SET global general_log_file = '$filename';"); 
    }
}

function get_db_prefix(){
    global $wpdb;
    return $wpdb->prefix;
}

function db_errors(bool $status){
    global $wpdb;
    $wpdb->show_errors = $status;
}

function get_default_connection(){
    return DB::getDefaultConnection();
}

function get_default_connection_id(){
    return DB::getDefaultConnectionId();
}

function get_default_database_name(){
    $def_con = get_default_connection_id();
    return config()['db_connections'][$def_con]['db_name'];
}

/*
    Similar to DB::table() but schema is not loaded so no validations are performed
*/
function table(string $tb_name){
    return (new MyModel(true))->table($tb_name);
}

function get_users_table(){
    global $wpdb;

    return $wpdb . 'users';
}

function get_model_namespace($tenant_id = null){
    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId(true);
    }   

    if ($tenant_id == config()['db_connection_default']){
        $extra = config()['db_connection_default'] . '\\';
    } else {
        $group = DB::getTenantGroupName($tenant_id);

        if ($group){
            $extra = $group . '\\'; 
        } else {
            $extra = '';
        }
    }

    return '\\boctulus\SW\\models\\' . $extra;
}

function get_model_name($table_name, $tenant_id = null){
    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId(true);
    }   

    if ($tenant_id == config()['db_connection_default']){
        $extra = config()['db_connection_default'] . '\\';
    } else {
        $group = DB::getTenantGroupName($tenant_id);

        if ($group){
            $extra = $group . '\\'; 
        } else {
            $extra = '';
        }
    }

    return '\\boctulus\SW\\models\\' . $extra . Strings::snakeToCamel($table_name). 'Model';
}

function get_user_model_name(){
    static $model_name;
    
    $users_table = get_users_table(true);
    $conn_id     = DB::getCurrentConnectionId(true);
    $key         = $conn_id . '.' . $users_table;

    if (isset($model_name[$key])){
        return $model_name[$key];
    }

    $model_name[$key] = get_model_name($users_table, $conn_id);

    return $model_name[$key];
}


function get_schema_path($table_name = null, $tenant_id = null){
    static $schema_paths = [];

    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId(true);
    }   

    $_table_name = ($table_name !== null ? $table_name : '__NULL__');
    
    if (isset($schema_paths[$tenant_id][$_table_name])){
        return $schema_paths[$tenant_id][$_table_name];
    }

    if ($tenant_id == config()['db_connection_default']){
        $extra = config()['db_connection_default'] . '/';
    } else {
        $group = DB::getTenantGroupName($tenant_id);

        if ($group){
            $extra = $group . '/'; 
        } else {
            $extra = '';
        }
    }

    $path = SCHEMA_PATH . $extra ;

    if ($table_name !== null){
        $path .= Strings::snakeToCamel($table_name). 'Schema.php';
    }

    $schema_paths[$tenant_id][$_table_name] = $path;
    return $path;
}

function get_schema_name($table_name, $tenant_id = null){
    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId();
    }   

    if ($tenant_id == config()['db_connection_default']){
        $extra = config()['db_connection_default'] . '\\';
    } else {
        $group = DB::getTenantGroupName($tenant_id);

        if ($group){
            $extra = $group . '\\'; 
        } else {
            $extra = '';
        }
    }

    return '\\boctulus\SW\\schemas\\' . $extra . Strings::snakeToCamel($table_name). 'Schema';
}

function has_schema($table_name, $tenant_id = null){
    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId();

        if ($tenant_id == null){
            DB::getDefaultConnection();
            $tenant_id = DB::getCurrentConnectionId();
        }
    }

    $class = get_schema_name($table_name, $tenant_id);

    return class_exists($class);
}

function get_schema($table_name, $tenant_id = null){
    static $sc;

    if ($tenant_id == null){
        $tenant_id = DB::getCurrentConnectionId();

        if ($tenant_id == null){
            DB::getDefaultConnection();
            $tenant_id = DB::getCurrentConnectionId();
        }
    }

    $key = "$tenant_id:$table_name";

    if (isset($sc[$key])){
        return $sc[$key];
    }

    $class = get_schema_name($table_name, $tenant_id);

    if (!class_exists($class)){
        throw new \Exception("Class $class does not exist");
    }

    $sc[$key] = $class::get();

    return $sc[$key];
}


/*
    Check if *at least* there is one relation between tables which is x_x where x_x can be 1:1, 1:n o n:m 

    If relation ("table1.key1=table2.key2") is given then it is the only evaluated
*/
function is_x_x(string $x_x, string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    static $rel;

    if (!in_array($x_x, ['1:1', '1:n', 'n:1', 'n:m'])){
        throw new \InvalidArgumentException("First parameter can only be ['1:1', '1:n', 'n:m']");
    }

    if (is_null($tenant_id)){
        $tenant_id = DB::getCurrentConnectionId(true);
    }  

    $key = "$tenant_id:$t1.{$t2}|" . (empty($relation_str) ? 'all' : $relation_str) . " -- for $x_x";

    if (isset($rel[$key])){
        return $rel[$key];
    }

    $rls = get_rels($t1, $t2, $x_x, $tenant_id, $relation_str);

    $rel[$key] = !empty($rls);

    return $rel[$key] ;
}

/*
    Check if *at least* there is one relation between tables which is 1:n
*/
function is_1_1(string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    return is_x_x('1:1', $t1, $t2, $relation_str, $tenant_id);
}

/*
    Check if *at least* there is one relation between tables which is 1:n
*/
function is_1_n(string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    return is_x_x('1:n', $t1, $t2, $relation_str, $tenant_id);
}

function is_n_1(string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    return is_x_x('n:1', $t1, $t2, $relation_str, $tenant_id);
}

/*
    Check if *at least* there is one relation between tables which is n:m
*/
function is_n_m(string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    return is_x_x('n:m', $t1, $t2, $relation_str, $tenant_id);
}

/*
    Get the type of relationship between two tables

*/
function get_rel_type(string $t1, string $t2, ?string $relation_str = null, ?string $tenant_id = null){
    static $rel;

    if (is_null($tenant_id)){
        $tenant_id = DB::getCurrentConnectionId(true);
    }    

    $key = "$tenant_id:$t1.{$t2}|$relation_str";

    if (isset($rel[$key])){
        return $rel[$key];
    }

    if (is_n_m($t1, $t2, $relation_str, $tenant_id)){
        $rel[$key] = 'n:m';
        return $rel[$key];
    }

    if (is_1_n($t1, $t2, $relation_str, $tenant_id)){
        $rel[$key] = '1:n';
        return $rel[$key];
    }

    if (is_n_1($t1, $t2, $relation_str, $tenant_id)){
        $rel[$key] = 'n:1';
        return $rel[$key];
    }

    if (is_1_1($t1, $t2, $relation_str, $tenant_id)){
        $rel[$key] = '1:1';
        return $rel[$key];
    }

    $rel[$key] = false;
    return $rel[$key];
}


/*
    Returns if relation can produce multiple rows
*/
function is_mul_rel(string $t1, string $t2, ?string $relation_str = null ,?string $tenant_id = null) {	
    if (empty($tenant_id)){
        $tenant_id = DB::getDefaultConnectionId(); 
    }
   
    $rel_type = get_rel_type($t1, $t2, $relation_str, $tenant_id);

    switch ($rel_type){
        case '1:1':
            return false;
        case 'n:m':
            return true;
        case '1:n':
            return true;
        case 'n:1': 
            return false;  

        default:
            StdOut::pprint("[ Warning ] Unknow or ambiguous relationship for $tenant_id:$t1~$t2 !!!");
    }
}

function is_mul_rel_cached(string $t1, string $t2, ?string $relation_str = null ,?string $tenant_id = null){
    if (!is_null($relation_str)){
        return is_mul_rel($t1, $t2, $relation_str, $tenant_id);
    }

    $def_conn_id = config()['db_connection_default'];

    if ($tenant_id == $def_conn_id){
        $folder = $def_conn_id . '/';
    } else {
        if ($tenant_id === null){
            $tenant_id = DB::getDefaultConnectionId();
            $folder = $tenant_id . '/';

            DB::getConnection($tenant_id);
        } else {
            $group = DB::getTenantGroupName($tenant_id);

            if ($group){
                $folder = $group . '/';
            } else {
                $folder = '';
            }
        }
    }

    $path = SCHEMA_PATH . $folder . 'Relations.php';

    if (!file_exists($path)){
        throw new \Exception("Please run 'php com make relation_scan --from:$tenant_id' or re-build all schemas. Detail: $path is not updated. Multiplicity for $t1~$t2 not found");
    }

    $r = include $path;

    if (!isset($r['multiplicity']["$t1~$t2"])){
        throw new \Exception("Please run 'php com make relation_scan --from:$tenant_id' or re-build all schemas. Detail: $path is not updated. Multiplicity for $t1~$t2 not found");
    }

    return $r['multiplicity']["$t1~$t2"];
}

function in_schema(array $props, string $table_name, ?string $tenant_id = null)
{  
    $sc = get_schema($table_name, $tenant_id);
    $attributes = array_keys($sc['attr_types']);

	if (empty($props))
		throw new \InvalidArgumentException("Attributes not found!");

	foreach ($props as $prop)
		if (!in_array($prop, $attributes)){
			return false; 
		}	
	
	return true;
}

// alias
function inSchema(array $props, string $table_name, ?string $tenant_id = null){
    return in_schema($props, $table_name, $tenant_id);
}

function get_primary_key(string $table_name, $tenant_id = null){
    static $keys = [];

    if (is_null($tenant_id)){
        $tenant_id = DB::getCurrentConnectionId(true);
    } 

    if (isset($keys[$tenant_id][$table_name])){
        return $keys[$tenant_id][$table_name];
    }

    $sc  = get_schema($table_name, $tenant_id);
    $keys[$tenant_id][$table_name] = $sc['id_name'];

    return $keys[$tenant_id][$table_name];
}

// alias
function get_id_name($table_name, $tenant_id = null){
    return get_primary_key($table_name, $tenant_id);
}

function get_pivot(Array $tables, ?string $tenant_id = null){
    static $ret = [];

    if (is_null($tenant_id)){
        $tenant_id = DB::getCurrentConnectionId(true);
    }  

    sort($tables);
    
    $needle = implode(',', $tables);

    if (isset($ret[$tenant_id][$needle])){
        return $ret[$tenant_id][$needle];
    }

    $dir = get_schema_path(null, $tenant_id);
    
    if (!file_exists($dir . 'Pivots.php')){
        \boctulus\SW\core\libs\StdOut::hideResponse();

        $mk = new MakeControllerBase();

        if (!empty($tenant_id)){
            $mk->pivot_scan("--from:$tenant_id");
        } else {
            $mk->pivot_scan();
        }

        \boctulus\SW\core\libs\StdOut::showResponse();
    }

    include $dir . 'Pivots.php';
    
    
    if (!isset($pivots[$needle])){
        return;
    }

    if (!isset($ret[$tenant_id])){
        $ret[$tenant_id] = [];
    }

    if (!isset($ret[$tenant_id])){
        $ret[$tenant_id][$needle] = [];
    }

    $bridge = $pivots[$needle];

    $ret[$tenant_id][$needle] = [
        'bridge' => $bridge, 
        'fks' => $pivot_fks[$bridge],
        'relationships' => $relationships[ $pivots[$needle] ]
    ];

    return $ret[$tenant_id][$needle];
}


/*
    Retorna la o las relaciones del tipo solicitado entre dos tablas (si existen)

    @param $type '1:1' or '1:n' or 'n:m'
    @return mixed

    El retorno es algo complejo porque puede devolver false o un array vacio en caso de "fallo"
    o un array con las relaciones en caso de "éxito". Específicamente devuelve false cuando 
    no hay una relación directa entre las tablas (caso n:m) y se pregunta si la relación es 1:1 o 1:n

    A su vez, el array devuelto tiene una estructura distinta según sea una relación n:m o 1:1/1:n

*/
function get_rels(string $t1, string $t2, string $type, ?string $tenant_id = null, string $relation_str = null){
    $type = strtolower($type);

    if (!in_array($type, ['1:1', '1:n', 'n:1', 'n:m'])){
        throw new \InvalidArgumentException("Type can be only '1:1', '1:n', 'n:1' or 'n:m'");
    }

    $current_id_conn = DB::getCurrentConnectionId();
    $conn = DB::getConnection($tenant_id);

    try {

        switch ($type){
            /*
                https://stackoverflow.com/questions/10292355/how-to-create-a-real-one-to-one-relationship-in-sql-server
                https://stackoverflow.com/a/25547364/980631

            */
            case '1:1':
                $pris = [];
    
                $sc  = get_schema_name($t1, $tenant_id)::get();

                if (!array_key_exists('autoincrement', $sc)){
                    throw new \Exception("Schema file for $t1 is outdated. Please re-generate all.");
                }
                
                $rels  = $sc['expanded_relationships'] ?? null;
                $uni1  = $sc['uniques'];
                $pri1  = $sc['primary']; 
                $auto1 = $sc['autoincrement'];
    
                if ($rels === null){
                    throw new \Exception("Unexpected error. There are not relationships");
                }
                $rs = $rels[$t2] ?? null;
                if (empty($rs)){
                    return false;
                }
    
                /*
                    SI la relación es entre dos PRIMARY KEYS (con sus campos AUTOINCREMENT o en caso de ser compuesta UNIQUE)
                    SI la relación es entre una PRIMARY KEY y una FK que es UNIQUE
    
                    =>
    
                    La relación es 1:1
                */
    
                // cumplen con la relación
                $meet = [];
    
                $sc2  = get_schema_name($t2, $tenant_id)::get();
                
                // $rels2 = $sc2['expanded_relationships'] ?? null;
                $uni2  = $sc2['uniques'];
                $pri2  = $sc2['primary'];
                $auto2 = $sc2['autoincrement']; 
    
                $pris[$t1] = $pri1;
                $pris[$t2] = $pri2;
    
                $unis[$t1] = $uni1;
                $unis[$t2] = $uni2;
    
    
                foreach ($rs as $ix => $r){             
    
                    $tb1 = $r[0][0];
                    $k1  = $r[0][1];
                    
                    $tb2 = $r[1][0];
                    $k2  = $r[1][1];
                
    
                    $meet[$ix] = false;
                    $rel_between_pri_keys = false;
                    $rel_between_pri_key_and_unique_key = false;

                    $rel_between_pri_keys = (in_array($k1, $pris[$tb1]) && in_array($k2, $pris[$tb2]));
                    
                    if ($rel_between_pri_keys){
                        // Ahora chequearé si hay "unicidad" de ambos lados,....
                        
                        // dd($pris[$tb1], '$pris[$tb1]');
                        // dd($pris[$tb2], '$pris[$tb2]');

                        $cnt_pris_tb1 = count($pris[$tb1]);
                        $cnt_pris_tb2 = count($pris[$tb2]);

                        // Caso simple: ambas PRI KEYs son simples
                        if ($cnt_pris_tb1 == 1 && $cnt_pris_tb2 == 1){
                            $meet[$ix] = true;
                            continue;
                        } else {
                            // Acá es menos claro el asunto,........
                        }                    
                    } 

                    // dd($tb1, 'tb1');
                    // dd($k1, 'k1');
                    // dd($tb2, 'tb1');
                    // dd($k2, 'k2');

                    $k1_in_pris_tb1 = in_array($k1, $pris[$tb1]);
                    $k1_in_unis_tb1 = in_array($k1, $unis[$tb1]);
                    $k2_in_pris_tb2 = in_array($k2, $pris[$tb2]);
                    $k2_in_unis_tb2 = in_array($k2, $unis[$tb2]);

                    // $rel_between_pri_key_and_unique_key1 = (in_array($k1, $pris[$tb1]) ||  in_array($k1, $unis[$tb1]));
                    // $rel_between_pri_key_and_unique_key2 = (in_array($k2, $pris[$tb2]) ||  in_array($k2, $unis[$tb2]));

                    if ($k1_in_pris_tb1 && $k2_in_unis_tb2){
                        $meet[$ix] = true;
                        continue;
                    }

                    if ($k1_in_unis_tb1 && $k2_in_pris_tb2){
                        $meet[$ix] = true;
                        continue;
                    }

                } // end foreach
    
                /*
                    Elimino relaciones que no cumplen
                */
                foreach (array_keys($rs) as $rsk){
                    if (!$meet[$rsk]){
                        unset($rs[$rsk]);
                    }
                }
               
                return $rs;
            case '1:n':
            case 'n:1':    
            
                /*
                    Si es de tipo 1:1 no puede ser de tipo 1:n
                */
                $rel_1_1 = get_rels($t1, $t2, '1:1', $tenant_id);
    
                if ($rel_1_1 === false || (is_array($rel_1_1) && !empty($rel_1_1))){
                    return false;
                } 
    
                $sc  = get_schema_name($t2, $tenant_id)::get();
        
                $rels = $sc['expanded_relationships'] ?? null;
                if ($rels === null){
                    throw new \Exception("Unexpected error. There are not relationships");
                }

                $rs = $rels[$t1] ?? null;
                if (empty($rs)){
                    return false;
                }

                // aún no se si son 1:n o n:1
        
                $_rels = $rs;
                break;

            case 'n:m':
                $pivot = get_pivot([
                    $t1, $t2
                ], $tenant_id);
    
                // por concistencia
                if ($pivot === null){
                    $pivot = [];
                }
    
                return $pivot;
        }
        
        // Ahora diferencio entre 1:n y n:1
    
        $sc   = get_schema($t1, $tenant_id);
        $fks  = $sc['fks'] ?? false;

        #dd($fks, 'FKs');
        #dd($_rels, 'RELS');

        if ($fks === false){
            throw new \Exception("Schema file for $t1 is outdated. Please re-generate all.");
        }

        if (empty($relation_str)){
            $meet_n_1 = [];
            // relaciones entre dos tablas (puede haber varias)
            foreach ($_rels as $ix => $rel){
                #dd($rel, "REL $ix");

                $key = $rel[0][1];
                #dd($key, 'KEY');
    
                if (in_array($key, $fks)){
                    $meet_n_1[] = $ix;
                }
            }

            #dd($meet_n_1, 'MEETS N:1');
            #exit;
    
            // Llegado a este punto, solo puede ser 1:n o n:1 en cada relación

            $rel_is = null;

            #dd($_rels, 'RELS');
            #dd(count($meet_n_1) . ' de '. count($_rels), "RELACIONES QUE CUMPLEN ");

            // Si hay una sola relación,....
            if (count($meet_n_1) == count($_rels)){
                // Como en todas las relaciones entre las dos tablas (concistentemente)
                // encuentro que la FK está del lado izquierdo de la realción, concluyo es N:1
                // en cualquier caso.

                $rel_is = 'n:1';
            } else {
                //  la única otra posibilidad
                $rel_is = '1:n';
            }   

            if ($type == $rel_is){
                return $_rels;
            } else {
                return false;
            }
        } else {
            // Se especifica la relación en particular entre dos tablas. 
            // Útil en el escenario donde hay varias relaciones entre las dos tablas (debo verificar no sean cíclica

            $_r = explode('=', $relation_str);
            $relation_str_inv = "{$_r[1]}={$_r[0]}";
    
            // reune las condiciones para n:1 ? sino será 1:n
            $meet = false;
            foreach ($_rels as $ix => $r){
                $tr1 = $r[0][0];
                $tr2 = $r[1][0];
    
                $k1 = $r[0][1];
                $k2 = $r[1][1];
    
                $current_rel = "$tr1.$k1=$tr2.$k2";
    
                if ($current_rel == $relation_str || $current_rel == $relation_str_inv){
                    if (in_array($k1, $fks)){
                        $meet = $ix;
                        break;
                    }
                }
            }
    
            if ($meet !== false){
                $rel_is = 'n:1';
            } else {
                $rel_is = '1:n';
            }   

            if ($type == $rel_is){
                return $_rels[$ix];
            } else {
                return false;
            }
        }

    } finally {
        // Restore previous connection
        if (!empty($current_id_conn)){
            DB::setConnection($current_id_conn);
        }
    }    
}

/*
    Returns FK(s) on $t1 pointing to $t2

    If there is more than one relationship between tables, then can be more than one FK.
*/
function get_fks(string $t1, string $t2, ?string $tenant_id = null){    
    $sc  = get_schema($t1, $tenant_id);
    $fks = $sc['fks'];

    if (empty($fks)){
        return [];
    }

    if (!isset($sc['expanded_relationships_from'][$t2])){
        throw new \Exception("There is no relationship from '$t1' to '$t2'");
    }

    $rels = $sc['expanded_relationships_from'][$t2];
    $fks_t2 = array_column(array_column($rels, 1),1); 

    // d($rels, 'RELS');
    // d($fks_t2, 'FKs');

    return $fks_t2;
}


function sql_formater(string $sql, ...$options){
    return Model::sqlFormatter($sql, ...$options);
}

/*
    Lee linea por línea y ejecuta sentencias SQL
*/
function process_sql_file(string $path, string $delimeter = ';'){
    $file = file_get_contents($path);
    
    $sentences = explode($delimeter, $file);
        
    foreach ($sentences as $sentence){
        $sentence = trim($sentence);

        if ($sentence == ''){
            continue;
        }

        //dd($sentence, 'SENTENCE');

        try {
            $ok = DB::statement($sentence);
        } catch (\Exception $e){
            dd($e, 'Sql Exception');
        }
    }    
}