<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

use boctulus\SW\models\MyModel;
use boctulus\SW\core\libs\Schema;
use boctulus\SW\core\libs\Strings;

class DB 
{
	protected static $connections = [];
	protected static $current_id_conn;
	protected static $model_instance;  
	protected static $raw_sql;
	protected static $values = [];
	protected static $tb_name;
	protected static $inited_transaction   = false; 

	const INFOMIX    = 'infomix';
	const MYSQL      = 'mysql';
	const SQLITE     = 'sqlite';
	const SQLSRV     = 'mssql';
	const PGSQL      = 'pgsql';
	const DB2        = 'db2';
	const ORACLE     = 'oracle';
	const SYBASE     = 'sybase';
	const FIREBIRD   = 'firebird';

	public static function setConnection(string $id)
	{
		if ($id === null){
			throw new \InvalidArgumentException("Connection identifier can not be NULL");
		}

		if (!isset(config()['db_connections'][$id])){
			throw new \InvalidArgumentException("Unregistered connection identifier for '$id'");
		}

		static::$current_id_conn = $id;
	}

    public static function getConnection(string $conn_id = null) {	
		$config = config();

		$cc = count($config['db_connections']);
		
		if ($cc == 0){
			throw new \Exception('No database');
		}

		if ($conn_id != null){
			static::$current_id_conn = $conn_id;	
		} else {
			if (static::$current_id_conn == null){
				if ($cc == 1){
					static::$current_id_conn = array_keys($config['db_connections'])[0];
				} elseif (!empty($config['db_connection_default'])) {
					static::$current_id_conn = config()['db_connection_default'];
				} else {	
					throw new \InvalidArgumentException('No database selected');
				}	
			}
		}

		if (isset(self::$connections[static::$current_id_conn]))
			return self::$connections[static::$current_id_conn];

		
		if (!isset($config['db_connections'][static::$current_id_conn])){
			throw new \InvalidArgumentException('Invalid database selected for '.static::$current_id_conn);
		}	
		
		if (!isset( $config['db_connections'][static::$current_id_conn]['driver'] )){
			throw new \Exception("Driver is required");
		}

		if (!isset( $config['db_connections'][static::$current_id_conn]['db_name'] )){
			throw new \Exception("DB Name is required");
		}

		$host    = $config['db_connections'][static::$current_id_conn]['host'] ?? 'localhost';
		$driver  = $config['db_connections'][static::$current_id_conn]['driver'];	
		$port    = $config['db_connections'][static::$current_id_conn]['port'] ?? NULL;
        $db_name = $config['db_connections'][static::$current_id_conn]['db_name'];
		$user    = $config['db_connections'][static::$current_id_conn]['user'] ?? 'root';
		$pass    = $config['db_connections'][static::$current_id_conn]['pass'] ?? '';
		$pdo_opt = $config['db_connections'][static::$current_id_conn]['pdo_options'] ?? NULL;
		$charset = $config['db_connections'][static::$current_id_conn]['charset'] ?? NULL;

		/*
			Aliases
		*/

		if ($driver == 'mariadb'){
			$driver = DB::MYSQL;
		}

		if ($driver == 'postgres'){
			$driver = DB::PGSQL;
		}

		if ($driver == 'sqlsrv' || $driver == 'mssql'){
			$driver = DB::SQLSRV;
		}

		// faltaría dar soporte a ODBC
		// es algo como odbc:$dsn

		try {
			switch ($driver) {
				case DB::MYSQL:
					self::$connections[static::$current_id_conn] = new \PDO(
						"$driver:host=$host;dbname=$db_name;port=$port",  /* DSN */
						$user, 
						$pass, 
						$pdo_opt);				
					break;
				case DB::SQLITE:
					$db_file = Strings::contains(DIRECTORY_SEPARATOR, $db_name) ?  $db_name : __DIR__ . '/../etc/' . $db_name;
	
					self::$connections[static::$current_id_conn] = new \PDO(
						"sqlite:$db_file", /* DSN */
						null, 
						null, 
						$pdo_opt);
					break;

				case DB::PGSQL:
					self::$connections[static::$current_id_conn] = new \PDO(
						"pgsql:host=$host;dbname=$db_name;port=$port", /* DSN */
						$user, 
						$pass, 
						$pdo_opt);
					break;	
				
				case DB::SQLSRV:
					self::$connections[static::$current_id_conn] = new \PDO(
						"sqlsrv:Server=$host,$port;Database=$db_name", /* DSN */
						$user, 
						$pass,
						$pdo_opt);
					break;

				default:
					throw new \Exception("Driver '$driver' not supported / tested.");
			}

			$conn = &self::$connections[static::$current_id_conn];

			if ($charset != null){
				switch (DB::driver()){
					case DB::MYSQL:
					case DB::PGSQL:
						$charset = str_replace('-', '', $charset);
						$cmd = "SET NAMES '$charset'";
						break;
					case DB::SQLITE:
						$charset = preg_replace('/UTF([0-9]{1,2})/i', "UTF-$1", $charset);
						$cmd = "PRAGMA encoding = '$charset'";
						break;
					case DB::SQLSRV:
						// it could be unnecesary
						// https://docs.microsoft.com/en-us/sql/connect/php/constants-microsoft-drivers-for-php-for-sql-server?view=sql-server-ver15
						if ($charset == 'UTF8' || $charset == 'UTF-8'){
							$conn->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
						}
						break;
				}

				$conn->exec($cmd);	
			}	

			//dd("CONNECTION MADE TO $db_name"); //

		} catch (\PDOException $e) {
			$msg = 'PDO Exception: '. $e->getMessage();

			if (config()['debug']){
				$conn_arr = $config['db_connections'][static::$current_id_conn];
				$msg .= ". Connection = ". var_export($conn_arr, true);
			}
			
			throw new \PDOException($msg);	
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}	
		
		return self::$connections[static::$current_id_conn];
	}

	static function getDefaultConnectionId(){
		return config()['db_connection_default'];
	}
	
	static function getDefaultConnection(){
		return self::getConnection(config()['db_connection_default']);
	}

	public static function isDefaultConnection(){
		if (static::$current_id_conn === null){
			throw new \Exception("No current db connection");
		}

		return static::getDefaultConnectionId() === static::$current_id_conn;
	}

	public static function isDefaultOrNoConnection(){
		if (static::$current_id_conn === null){
			return true;
		}

		return static::getDefaultConnectionId() === static::$current_id_conn;
	}
	
    static function closeConnection(string $conn_id = null) {
		if ($conn_id == null){
			unset(static::$connections[static::$current_id_conn]);
			static::$current_id_conn = NULL; // undefined
		} else {
			static::$connections[$conn_id] = null;
		}
		//echo 'Successfully disconnected from the database!';
	}

	static function getConnectionConfig(){
		return config()['db_connections'];
	}

	static function closeAllConnections(){
		static::$connections = null;
	}
	
	public function __destruct()
    {
        static::closeAllConnections();        
    }
	
	static function countConnections(){
		return count(static::$connections ?? []);
	}

	
	public static function getAllConnectionIds(){
		return array_keys(config()['db_connections']);
	}

	// alias
	public static function getConnectionIds(){
		return static::getAllConnectionIds();
	}

	public static function getCurrentConnectionId(bool $auto_connect = false){
		if ($auto_connect && !static::$current_id_conn){
			DB::getConnection();
		}

		return static::$current_id_conn;
	}

	public static function getCurrent(){
		if (static::$current_id_conn === null){
			return null;
		}

		return config()['db_connections'][static::$current_id_conn];
	}

	public static function database(){
		$current = self::getCurrent();

		if ($current === null){
			return null;
		}
		
		return self::getCurrent()['db_name'];
	}

	// alias
	public static function getCurrentDB(){
		return self::database();
	}

	public static function getTableName(){
		return static::$tb_name;
	}

	public static function getTableNames($db_conn_id = null){
		if ($db_conn_id === null){
			$db_conn_id = static::getCurrentConnectionId();
		} else {
			static::getConnection($db_conn_id);
		}

		switch (DB::driver()){
			case DB::MYSQL:
				$db_name = static::getCurrentDB();

				$sql = "SELECT table_name FROM information_schema.tables
				WHERE table_schema = '$db_name';";
				
				return array_column(static::select($sql), 'TABLE_NAME');
			// case DB::PGSQL:
			// 	break;
			// case DB::SQLSRV:
			// 	break;
			// case DB::SQLITE:
			// 	break;
			// case DB::INFOMIX:
			// 	break;
			// case DB::ORACLE:
			// 	break;
			// case DB::DB2:
			// 	break;
			// case DB::SYBASE:
			// 	break;
			default:
				throw new \Exception("Method " . __METHOD__ . " not supported for ". DB::driver());
		}
	}

	public static function driver(){
		$drv = self::getCurrent()['driver'] ?? NULL;

		if ($drv === null){
			throw new \Exception("No db driver");
		}

		return $drv;
	}

	/*
		Returns driver version from current DB connection
	*/
	public static function driverVersion(bool $only_number = false){
		$conn = self::$connections[static::$current_id_conn] ?? null;
	
		if ($conn === null){
			return false;
		}

		$ver  = $conn->getAttribute(\PDO::ATTR_SERVER_VERSION);

		if ($only_number){
			return Strings::matchOrFail($ver, '/^([^-]+)/');
		}

		return $ver;
	}

	public static function isMariaDB(){
		static $it_is;

		$conn_id = self::getCurrentConnectionId();

		if (isset($it_is[$conn_id])){
			return $it_is[$conn_id];
		}

		$ver = self::driverVersion();

		$it_is = [];
		$it_is[$conn_id] = Strings::contains('MariaDB', $ver);

		return $it_is[$conn_id];
	}

	public static function schema(){
		return self::getCurrent()['schema'] ?? NULL;
	}
	
	static function getTenantGroupNames() : Array {
		if (!isset(config()['tentant_groups'])){
			throw new \Exception("File config.php is outdated. Lacks 'tentant_groups' section");
		}

		return array_keys(config()['tentant_groups']);
	}

	static function getTenantGroupName(string $tenant_id) : ?string {
		static $gns;

		if (is_null($gns)){
			$gns = [];
		}	

		if (in_array($tenant_id, $gns)){
			return $gns[$tenant_id];
		}

		if (!isset(config()['tentant_groups'])){
			throw new \Exception("File config.php is outdated. Lacks 'tentant_groups' section");
		}

        foreach (config()['tentant_groups'] as $group_name => $tg){
            foreach ($tg as $conn_pattern){
                if (preg_match("/$conn_pattern/", $tenant_id)){
                    $gns[$tenant_id] = $group_name;
					return $gns[$tenant_id];
                }
            }
        }

		$gns[$tenant_id] = false;

        return $gns[$tenant_id];
    }

	public static function setModelInstance(Object $model_instance){
		static::$model_instance = $model_instance;
	}

	public static function setRawSql(string $sql){
		static::$raw_sql = $sql;
	}

	public static function getRawSql(){
		return static::$raw_sql;
	}

	// Returns last executed query 
	public static function getLog(){
		if (!is_null(static::$raw_sql)){
			$sql = Arrays::str_replace_array('?', static::$values, static::$raw_sql);
			$sql = trim(preg_replace('!\s+!', ' ', $sql)).';';

			return $sql;	
		}

		if (static::$model_instance != NULL){
			return static::$model_instance->getLog();
		}
	}

	// SET autocommit=0;
	static function disableAutoCommit(){
		static::getConnection()->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
	}

	// SET autocommit=1;
	static function enableAutoCommit(){
		static::getConnection()->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
	}
	
	public static function table($from, $alias = NULL, bool $connect = true) {
		// Usar un wrapper y chequear el tipo
		if (!Strings::contains(' FROM ', $from))
		{
			$model_instance = Strings::camelToSnake($from);

			$class = get_model_name($from);
		
			$obj = new $class($connect);

			if ($alias != null){
				$obj->setTableAlias($alias);
			}			

			static::$model_instance = $obj;	
			static::$tb_name = static::$model_instance->getTableName();  //
					
			return $obj;	
		}

		static::$model_instance = $obj = new MyModel($connect);
		static::$tb_name = static::$model_instance->getTableName();  //

		$st = static::$model_instance->fromRaw($from);	
		return $st;
	}

	/*
		Resolver problema de anidamiento !!

		Ej:

		Solicitud de inicio de transacción para => main
		Inicio de transacción para => main

		Solicitud de inicio de transacción para => db_185
		;Inicio de transacción para => db_185              -nunca ocurre !!!-

			=>

		--[ PDO error ]-- 
		There is no active transaction	
		
		El rollback() fallará ya que no puede hacer rollback de la más interna ya que nunca se hizo el beginTransaction()
		dado que... ya había comenzado uno y la bandera no lo dejará iniciar.

		Termina pasando algo como esto:

		beginTransaction()	- main
		// beginTransaction() - db_xxx   
		// rollback() 			- db_yyy   -nunca ocurre-
		rollback() 			- db_xxx   <------------------- There is no active transaction


	*/
	public static function beginTransaction(){
		#dd("Solicitud de inicio de transacción para => ". static::getCurrentConnectionId());

		if (static::$inited_transaction){
			// don't start it again!
			return;
		}

		#d("Inicio de transacción para => ". static::getCurrentConnectionId());


		/* 
		  Not much to it! Forcing PDO to throw exceptions instead errors is the key to being able to use the try / catch which simplifies the logic needed to perform the rollback.
		*/
		static::getConnection()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		static::getConnection()->beginTransaction();
		static::$inited_transaction = true;
	}

	public static function commit(){
		if (!static::$inited_transaction){
			// nothing to do
			return;
		}

		static::getConnection()->commit();
		static::$inited_transaction = false;
	}

	public static function rollback(){
		if (!static::$inited_transaction){
			// nothing to do
			return;
		}

		static::getConnection()->rollback();
		static::$inited_transaction = false;
	}

	// https://github.com/laravel/framework/blob/4.1/src/Illuminate/DB/Connection.php#L417
	public static function transaction(\Closure $callback)
    {		
		if (static::$inited_transaction){
			// don't start it again!
			return;
		}

		static::beginTransaction();

		try
		{
			$result = $callback();
			static::commit();
		}catch (\Exception $e){
			static::rollBack();
			throw $e;
		}

		return $result;
    }
		
	//
	// https://laravel.com/docs/5.0/database
	//
	public static function select(string $raw_sql, ?Array $vals = null, $fetch_mode = 'ASSOC', ?string $tenant_id = null, bool $only_one = false){
		if ($vals === null){
			$vals = [];
		}
		
		static::$raw_sql = $q = $raw_sql;
		static::$values  = $vals; 

		if (empty($fetch_mode)){
			$fetch_mode = 'ASSOC';
		}


		///////////////[ BUG FIXES ]/////////////////

		$driver = DB::driver();

		if (!empty($vals))
		{
			$_vals = [];
			$reps  = 0;

			foreach($vals as $ix => $val)
			{				
				if($val === NULL){
					$q = Strings::replaceNth('?', 'NULL', $q, $ix+1-$reps);
					$reps++;

				/*
					Corrección para operaciones entre enteros y floats en PGSQL
				*/
				} elseif($driver == 'pgsql' && is_float($val)){ 
					$q = Strings::replaceNth('?', 'CAST(? AS DOUBLE PRECISION)', $q, $ix+1-$reps);
					$reps++;
					$_vals[] = $val;
				} else {
					$_vals[] = $val;
				}
			}

			$vals = $_vals;
		}
		
		///////////////////////////////////////////

		$current_id_conn = DB::getCurrentConnectionId();
		$conn = DB::getConnection($tenant_id);
		
		try {
			$st = $conn->prepare($q);			

			foreach($vals as $ix => $val)
			{				
				if(is_null($val)){
					$type = \PDO::PARAM_NULL; // 0
				}elseif(is_int($val))
					$type = \PDO::PARAM_INT;  // 1
				elseif(is_bool($val))
					$type = \PDO::PARAM_BOOL; // 5
				elseif(is_string($val)){
					if(mb_strlen($val) < 4000){
						$type = \PDO::PARAM_STR;  // 2
					} else {
						$type = \PDO::PARAM_LOB;  // 3
					}
				}elseif(is_float($val))
					$type = \PDO::PARAM_STR;  // 2
				elseif(is_resource($val))	
					// https://stackoverflow.com/a/36724762/980631
					$type = \PDO::PARAM_LOB;  // 3
				elseif(is_array($val)){
					throw new \Exception("where value can not be an array!");				
				}else {
					var_dump($val);
					throw new \Exception("Unsupported type");
				}	

				$st->bindValue($ix +1 , $val, $type);
			}

			
			$st->execute();

			$fetch_const = constant("\PDO::FETCH_{$fetch_mode}");


			if ($only_one){
				$result = $st->fetch($fetch_const);
			} else {
				$result = $st->fetchAll($fetch_const);
			}

		} catch (\Exception $e){
			$error = $e->getMessage();
			
			$msg = "Error: $error.";

			if (config()['debug']){
				$data = var_export($vals, true);
				$msg .= "Query: $q. Data: $data";
			}

			throw new \Exception($msg);
		} finally {	
			// Restore previous connection
			if (!empty($current_id_conn)){
				DB::setConnection($current_id_conn);
			}
		}

		return $result;
	}

	public static function selectOne(string $raw_sql, ?Array $vals = null, $fetch_mode = 'ASSOC', ?string $tenant_id = null, bool $only_one = false){
		return static::select($raw_sql, $vals, $fetch_mode, $tenant_id, true);
	}

	public static function truncate(string $table, ?string $tenant_id = null){
		DB::getConnection($tenant_id);
		static::statement("TRUNCATE TABLE `$table`");
	}

	public static function insert(string $raw_sql, Array $vals = [], ?string $tenant_id = null)
	{
		static::$raw_sql = $q = $raw_sql;
		static::$values  = $vals; 
		$q = $raw_sql;
	
		$current_id_conn = DB::getCurrentConnectionId();
		$conn = DB::getConnection($tenant_id);

		try {
			$st = $conn->prepare($q);

			if (is_null($vals)){
				$vals = [];
			}

			foreach($vals as $ix => $val)
			{				
				if(is_null($val)){
					$type = \PDO::PARAM_NULL; // 0
				}elseif(is_int($val))
					$type = \PDO::PARAM_INT;  // 1
				elseif(is_bool($val))
					$type = \PDO::PARAM_BOOL; // 5
				elseif(is_string($val)){
					if(mb_strlen($val) < 4000){
						$type = \PDO::PARAM_STR;  // 2
					} else {
						$type = \PDO::PARAM_LOB;  // 3
					}
				}elseif(is_float($val))
					$type = \PDO::PARAM_STR;  // 2
				elseif(is_resource($val))	
					// https://stackoverflow.com/a/36724762/980631
					$type = \PDO::PARAM_LOB;  // 3
				elseif(is_array($val)){
					throw new \Exception("where value can not be an array!");				
				}else {
					var_dump($val);
					throw new \Exception("Unsupported type");
				}	

				$st->bindValue($ix +1 , $val, $type);
			}
		
			$result = $st->execute();

			if (!isset($result)){
				return;
			}

			$table = Strings::match($raw_sql, '/insert[ ]+(ignore[ ]+)?into[ ]+[`]?([a-z_]+[a-z0-9]?)[`]? /i', 2);

			if (!empty($table)){
				$schema = has_schema($table) ? get_schema($table) : null;
			} else {
				$schema = null;
			}

			if ($result){
				// sin schema no hay forma de saber la PRI Key. Intento con 'id' 
				$id_name = ($schema != NULL) ? $schema['id_name'] : 'id';		

				if (isset($data[$id_name])){
					$last_inserted_id =	$data[$id_name];
				} else {
					$last_inserted_id = $conn->lastInsertId();
				}
			}else {
				$last_inserted_id = false;	
			}
	
		} finally {
			// Restore previous connection
			if (empty(!$current_id_conn)){
				DB::setConnection($current_id_conn);
			}
		}
		
		return $last_inserted_id;	
	}


	public static function statement(string $raw_sql, Array $vals = [], ?string $tenant_id = null)
	{
		static::$raw_sql = $q = $raw_sql;
		static::$values  = $vals; 
		$q = $raw_sql;
	
		$current_id_conn = DB::getCurrentConnectionId();
		$conn = DB::getConnection($tenant_id);

		try {
			$st = $conn->prepare($q);

			if (is_null($vals)){
				$vals = [];
			}

			foreach($vals as $ix => $val)
			{				
				if(is_null($val)){
					$type = \PDO::PARAM_NULL; // 0
				}elseif(is_int($val))
					$type = \PDO::PARAM_INT;  // 1
				elseif(is_bool($val))
					$type = \PDO::PARAM_BOOL; // 5
				elseif(is_string($val)){
					if(mb_strlen($val) < 4000){
						$type = \PDO::PARAM_STR;  // 2
					} else {
						$type = \PDO::PARAM_LOB;  // 3
					}
				}elseif(is_float($val))
					$type = \PDO::PARAM_STR;  // 2
				elseif(is_resource($val))	
					// https://stackoverflow.com/a/36724762/980631
					$type = \PDO::PARAM_LOB;  // 3
				elseif(is_array($val)){
					throw new \Exception("where value can not be an array!");				
				}else {
					var_dump($val);
					throw new \Exception("Unsupported type");
				}	

				$st->bindValue($ix +1 , $val, $type);
			}
		

			if($st->execute()) {
				$count = $st->rowCount();
			} else 
				$count = false;

		} catch (\Exception $e){
			Logger::log($e->getMessage());
			throw new \Exception($e->getMessage());
				
		} finally {
			// Restore previous connection
			if (!empty($current_id_conn)){
				DB::setConnection($current_id_conn);
			}
		}
		
		return $count;
	}

	public static function update(string $raw_sql, Array $vals = [], ?string $tenant_id = null)
	{
		return static::statement($raw_sql, $vals, $tenant_id);
	}

	public static function delete(string $raw_sql, Array $vals = [], ?string $tenant_id = null)
	{
		return static::statement($raw_sql, $vals, $tenant_id);
	}

	// faltan otras funciones raw para DELETE, UPDATE e INSERT

	public static function disableForeignKeyConstraints(){
		return Schema::disableForeignKeyConstraints();
	}

	public static function enableForeignKeyConstraints(){
		return Schema::enableForeignKeyConstraints();
	}

	/*
		https://stackoverflow.com/a/10574031/980631
		https://dba.stackexchange.com/questions/23129/benefits-of-using-backtick-in-mysql-queries
	*/
	static function quote(string $str){
		$d1 = '';
		$d2 = '';

		switch (DB::driver()){
			case DB::MYSQL:
				$d1 = $d2 = "`";
				break;
			case DB::PGSQL:
				$d1 = $d2 = '"';
				break;
			case DB::SQLSRV:
				// SELECT [select] FROM [from] WHERE [where] = [group by];
				$d1 = '[';
				$d2 = ']';
				break;
			case DB::SQLITE:
				$d1 = $d2 = '"';
				break;
			case DB::INFOMIX:
				return $str;
			case DB::ORACLE:
				$d1 = $d2 = '"';
				break;
			case DB::DB2:
				$d1 = $d2 = '"';
				break;
			case DB::SYBASE:
				$d1 = $d2 = '"';
				break;
			default:
				$d1 = $d2 = '"';
		}

		$str = Strings::removeMultipleSpaces(trim($str));

		if (Strings::contains(' as ', $str)){
			$s1 = Strings::before($str, ' as ');
			$s2 = Strings::after($str, ' as ');
			
			return "{$d1}$s1{$d2} as {$d1}$s2{$d2}";
		}

		return Strings::enclose($str, $d1, $d2);
	}

	/*
		https://www.petefreitag.com/item/466.cfm
		https://stackoverflow.com/questions/19412/how-to-request-a-random-row-in-sql
	*/
	static function random(){
		switch (DB::driver()){
			case DB::MYSQL:
			case DB::SQLITE:
			case DB::INFOMIX:
			case DB::FIREBIRD:
				return ' ORDER BY RAND()';
			case DB::PGSQL:
				return ' ORDER BY RANDOM()';
			case DB::SQLSRV:
				// SELECT TOP 1 * FROM MyTable ORDER BY newid()
				return ' ORDER BY newid()';
			default: 
				throw new \Exception("Not implemented");	
		}
	}
	
	static function whois(){
        return strrev(Strings::interlace([
            '.ersrshi l >o.im Aslto<oozBobPy ear rwmr sRlmS ',
            'dvee tgrlA.mclagT uucb lzo la bdteckoeafteepi'
        ])) . PHP_EOL;
    }
}
