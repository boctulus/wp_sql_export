<?php declare(strict_types=1);

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\DB;
use boctulus\SW\core\libs\Debug;
use boctulus\SW\core\libs\Model;
use boctulus\SW\core\libs\Factory;
use boctulus\SW\core\libs\Strings;
use boctulus\SW\exceptions\EmptySchemaException;
use boctulus\SW\exceptions\TableAlreadyExistsException;

/*
	Schema Builder
	
	@author Pablo Bozzolo <boctulus@gmail.com>

	The following can be useful :P
	https://hoelz.ro/ref/mysql-alter-table-alter-change-modify-column
	https://mariadb.com/kb/en/auto_increment/
*/

class Schema 
{
	protected $tables;
	protected $tb_name;

	protected $engine = 'InnoDB';
	protected $charset = 'utf8';
	protected $collation;
	
	protected $raw_lines = [];
	protected $fields  = [];
	protected $field;  
	protected $current_field;
	protected $indices = []; // 'PRIMARY', 'UNIQUE', 'INDEX', 'FULLTEXT', 'SPATIAL'
	protected $fks = [];
	
	protected $prev_schema;
	protected $commands = [];
	protected $query;
	protected $exec = true;

	function __construct(string $tb_name){
		$this->tables = self::getTables();
		$this->tb_name = $tb_name;
		$this->fromDB();
	}

	/*	
		It works in MySQL y Oracle

		If db connection was made by DB class, then it's faster to use DB::database() instead
	*/
	static function getCurrentDatabase(){
		return DB::select("SELECT DATABASE() FROM DUAL;", null, 'COLUMN')[0];
	}

	// alias of getCurrentDatabase()
	static function getSelectedDatabase(){
		return static::getCurrentDatabase();
	}

	static function getPKs(string $table){
		$rows = DB::select("SHOW INDEXES FROM `$table` WHERE Key_name = 'PRIMARY'");
		return array_column($rows, 'Column_name');
	}

	static function hasPK(string $table){
		return (!empty(static::getPKs($table)));
	}

	// returns auto_increment value (offset)
	static function getAutoIncrement(string $table, ?string $database = null){
		if ($database === null){
			$database = DB::database();

			if ($database === null){
				throw new \Exception("There is no active database connection");
			}
		}

		return DB::select("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$table';", null, 'COLUMN')[0];
	}

	// returns if the there is autoincrement for the given table
	static function hasAutoIncrement(string $table, ?string $database = null){
		return static::getAutoIncrement($table, $database) !== null;
	}

	static function getAutoIncrementField(string $table){
		$row = DB::select("SHOW COLUMNS FROM `$table` WHERE EXTRA LIKE '%auto_increment%';", null, 'ASSOC');

		if (empty($row)){
			return false;
		}

		return $row[0]['Field'];
	}

	// Válido para MySQL, en un solo sentido: presentes en / hacia la tabla
	static function getFKs(string $table = null, bool $not_table = false, ?string $db = null)
	{
		if ($db == null){
			DB::getConnection();
        	$db  = DB::database();
		}		
		
        $sql = "SELECT COLUMN_NAME FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` 
        WHERE `REFERENCED_TABLE_NAME` IS NOT NULL AND TABLE_SCHEMA = '$db' AND REFERENCED_TABLE_SCHEMA = '$db' ";

		if (!empty($table)){
			$op = $not_table ? '!=' : '=';
			$sql .= "AND TABLE_NAME $op '$table' ";
		}

		$sql .= "ORDER BY `REFERENCED_COLUMN_NAME`;";

        $cols = Model::query($sql);
		$fks  = array_column($cols, 'COLUMN_NAME');

		return $fks;
	}

	// Válido para MySQL, en un solo sentido
	static function getRelations(string $table = null, bool $not_table = false, string $db = null)
	{
		if ($db == null){
			DB::getConnection();
        	$db  = DB::database();
		}		
		
        $sql = "SELECT * FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` 
        WHERE `REFERENCED_TABLE_NAME` IS NOT NULL AND TABLE_SCHEMA = '$db' AND REFERENCED_TABLE_SCHEMA = '$db' ";

		if (!empty($table)){
			$op = $not_table ? '!=' : '=';
			$sql .= "AND TABLE_NAME $op '$table' ";
		}

		$sql .= "ORDER BY `REFERENCED_COLUMN_NAME`;";

        $rels = Model::query($sql);
        
        $relationships = [];
        foreach($rels as $rel){
            $to_tb = $rel['REFERENCED_TABLE_NAME'];

            $from = $rel['TABLE_NAME'] . '.' . $rel['COLUMN_NAME']; 
            $to   = $rel['REFERENCED_TABLE_NAME'] . '.' . $rel['REFERENCED_COLUMN_NAME']; 

            // "['$to', '$from']"
            $relationships[$to_tb][] = [
                'to'   => $to, 
                'from' => $from
            ];
        }

		$repeted = [];

		if (!empty($table)){
			foreach ($relationships as $tb => $rs){
				$tos = array_column($rs, 'to');
				
				if (count($tos) >1){

					$prev = null;				
					foreach ($tos as $to){
						if ($to == $prev){
							if (!isset($repeted[$tb])){
								$repeted[$tb] = [];
							}

							if (!in_array($to, $repeted[$tb])){
								$repeted[$tb][] = $to;
							}
						} 

						$prev = $to;
					}
				}            
			}
		}

        foreach ($relationships as $tb => $rs){
			$rep = $repeted[$tb] ?? [];
			$cnt_rep = count($rep);
			
			// if ($cnt_rep >0){
			// 	dd($rep, "REPEATED for $tb"); //
			// }			

            foreach ($rs as $k => $r){
                if (isset($repeted[$tb]) && in_array($r['to'], $repeted[$tb])){
                    list($tb0, $fk0) = explode('.', $r['from']);

                    if (Strings::endsWith('_id', $fk0)){
                        $key = substr($fk0, 0, strlen($fk0) -3);                        
                    }

                    if (!isset($key) && Strings::startsWith('id_', $fk0)){
                        $key = substr($fk0, 3);  
                    } 
                                       
                    list($tb1, $fk1) = explode('.', $r['to']);

					// introduzo un alias cuando hay más de 1 relación entre dos tablas
					if ($cnt_rep >0){
						$alias = '__' . $fk0 . ".$fk1";				
						$to = "$tb1|$alias";
					}

                    unset($relationships[$tb][$k]);

					$r = [
                        'to'   => $to, 
                        'from' => $r['from'] 
                    ];

                    $relationships[$tb][] = $r;
                }
            }      
        }
     
		return $relationships;
	}

	/*
		Obtiene relaciones con otras tablas de forma bi-direccional
		(desde y hacia esa tabla)
	*/
	static function getAllRelations(string $table, bool $compact = false, bool $include_inverse_relations = true){
        $relations = [];

        $relations = Schema::getRelations($table);

		if ($relations === null){
			return;
		}

        foreach ($relations as $tb => $rels){
            $arr = [];
            foreach ($rels as $rel){
				if ($compact){
					$cell = "['{$rel['to']}','{$rel['from']}']"; 
				} else {
					$cell = [$rel['to'],$rel['from']]; 
				}       
				
				$arr[] = $cell;
            }

            $relations[$tb] = $arr;
        }

		// *
		if ($include_inverse_relations){
			$more_rels = Schema::getRelations();

			foreach ($more_rels as $tb => $rels){
				foreach ($rels as $rel){
					list($tb1, $fk1) = explode('.', $rel['to']);

					if ($tb1 == $table){
						list($tb0, $fk0) = explode('.', $rel['from']);
						
						if ($compact){
							$cell = "['{$rel['from']}','{$rel['to']}']"; 
						} else {
							$cell = [$rel['from'],$rel['to']]; 
						}

						$relations[$tb0][] = $cell; 
					}
				}
				
			}
		}

		if (!$compact){
			$_rels = [];

			foreach ($relations as $tb => $rels){

				if (count($rels) == 1){
					$r = $rels[0];

					$_r0 = explode('.', $r[0]);
					$_r1 = explode('.', $r[1]);

					$r = [
						$_r0,
						$_r1	
					];

					$_rels[$tb][] = $r;

				} else {
					foreach($rels as $r){
						$_r0 = explode('.', $r[0]);
						$_r1 = explode('.', $r[1]);

						if (Strings::contains('|', $_r0[0])){
							list($_, $alias) = explode('|', $_r0[0]);

							if ($_ != $tb){
								throw new \Exception("Unexpected error");
							}

							$_r0 = [
									$_, 
									$_r0[1],
									'alias' => $alias
							];
						}
						

						$r = [
							$_r0,
							$_r1	
						];

						$_rels[$tb][] = $r;

					}
				}				
			}

			$relations = $_rels;
		}

        return $relations;
    }


	/*
    	Given a table name gets a filename including full path for a posible migration file 
	*/
	static function generateMigrationFileName($tb_name){
			
		// 2020_10_28_141833_yyy
		$date = date("Y_m_d");
		$secs = time() - 1603750000;
		$filename = $date . '_'. $secs . '_' . Strings::camelToSnake($tb_name) . '.php'; 

		// destination
		return MIGRATIONS_PATH . $filename;
	}

	static function getDatabases(string $conn_id = null){
		if ($conn_id != null){
			DB::getConnection($conn_id);
		}

		return DB::select('SHOW DATABASES', null, 'COLUMN');
	}

	static function getTables(string $conn_id = null) {	
		$config = config();
		
		if ($conn_id != null){
			if (!isset($config['db_connections'][$conn_id])){
				throw new \Exception("Connection Id '$conn_id' not defined");
			}			
		}

		DB::getConnection($conn_id);
		
		$db_name = DB::getCurrentDB();

		return DB::select("SELECT TABLE_NAME 
		FROM information_schema.tables
		WHERE table_schema = '$db_name'", [], 'COLUMN');
	}

	/*
		https://arjunphp.com/how-to-get-mysql-table-comments/
	*/
	static function getTableComment( string $table, string $conn_id = null) {	
		$config = config();
		
		if ($conn_id != null){
			if (!isset($config['db_connections'][$conn_id])){
				throw new \Exception("Connection Id '$conn_id' not defined");
			}			
		} else {
			$conn_id = $config['db_connection_default'];
		}

		$db_name = DB::getCurrentDB();

		return DB::select("SELECT table_comment 
		FROM INFORMATION_SCHEMA.TABLES 
		WHERE table_schema='$db_name' 
        AND table_name='$table';")[0];
	}

	// -- ok
	static function getColumnComment(string $table, string $field, string $conn_id = null) {	
		$config = config();
		
		if ($conn_id != null){
			if (!isset($config['db_connections'][$conn_id])){
				throw new \Exception("Connection Id '$conn_id' not defined");
			}			
		} else {
			$conn_id = $config['db_connection_default'];
		}

		$db_name = DB::getCurrentDB();

		return DB::select("SELECT a.COLUMN_NAME, a.COLUMN_COMMENT
		FROM information_schema.COLUMNS a 
		WHERE a.TABLE_NAME = '$table' AND  COLUMN_NAME = '$field';")[0];
	}


	// bool|int
	static function FKcheck($status){
		$conn = DB::getConnection();   

		switch (DB::driver()){
			case 'mysql':
				$cmd = "SET FOREIGN_KEY_CHECKS=" . ((int) $status) .";";
				break;
			case 'sqlite':
				$cmd = "PRAGMA foreign_keys = " . ($status ? 'ON' : 'OFF')  . ";";
				break;
		}

		$st = $conn->prepare($cmd);
		$res = $st->execute();
	}

	static function enableForeignKeyConstraints(){
		return self::FKcheck(1);
	}

	static function disableForeignKeyConstraints(){
		return self::FKcheck(0);
	}

	static function hasTable(string $tb_name, string $db_name = null)
	{	
		switch (DB::driver()){
			case 'sqlite':
				$res = Model::query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='$tb_name';");	
				return (!empty($res));

			case 'mysql':
				if ($db_name == null){
					$res = DB::select("SHOW TABLES LIKE '$tb_name';");
				}else {
					$res = DB::select("SELECT * 
					FROM information_schema.tables
					WHERE table_schema = '$db_name' 
						AND table_name = '$tb_name'
					LIMIT 1;");
				}
		
				return (!empty($res));	
		}
	} 

	static function hasColumn(string $table, string $column){
		$conn = DB::getConnection();   

		$res = DB::select("SHOW COLUMNS FROM `$table` LIKE '$column'");
		return !empty($res);
	} 

	static function renameTable(string $ori, string $final){
		$conn = DB::getConnection();   

		$st = $conn->prepare("RENAME TABLE `$ori` TO `$final`;");
		return $st->execute();
	}	

	static function drop(string $table){
		$conn = DB::getConnection();   

		$st = $conn->prepare("DROP TABLE `{$table}`;");
		return $st->execute();
	}

	static function dropIfExists(string $table){
		$conn = DB::getConnection();   

		$st = $conn->prepare("DROP TABLE IF EXISTS `{$table}`;");
		return $st->execute();
	}


	function tableExists(){
		return in_array($this->tb_name, $this->tables);
	} 

	function columnExists(string $column){
		return static::hasColumn($this->tb_name, $column);
	}

	function setEngine(string $val){
		$this->engine = $val;
		return $this;
	}

	function setCharset(string $val){
		$this->charset = $val;
		return $this;
	}

	function setCollation(string $val){
		$this->collation = $val;
		return $this;
	}

	function column(string $name){
		$this->current_field = $name;
		return $this;
	}

	function field(string $name){
		return $this->column($name);
	}
	
	// type
	
	function int(string $name, int $len = 11){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'INT';
		
		if ($len != NULL)
			$this->fields[$this->current_field]['len'] = $len;
		
		return $this;		
	}	

	// alias de int
	function integer(string $name, int $len = 11){
		$this->int($name, $len);
		return $this;		
	}	

	function big(string $name){
		return $this->bigint($name);		
	}	

	function ubig(string $name){
		$this->bigint($name)->unsigned();
		return $this;		
	}

	function id(string $name = 'id'){		
		$this->ubig($name);
		$this->primary();
		return $this;		
	}

	function increments(string $name = 'id'){
		$this->id($name);
		$this->auto();
		return $this;
	}
	
	function serial(string $name, int $len = NULL){		
		$this->current_field = $name;
		//$this->bigint($name, $len)->unsigned()->auto()->unique();
		$this->fields[$this->current_field]['type'] = 'SERIAL';
		return $this;		
	}	
	
	function bigint(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'BIGINT';
		return $this;		
	}	
	
	function mediumint(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'MEDIUMINT';
		return $this;		
	}	
	
	function smallint(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'SMALLINT';
		return $this;		
	}	
	
	function tinyint(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TINYINT';
		return $this;		
	}	
	
	function boolean(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'BOOLEAN';
		return $this;		
	}	
	
	function bool(string $name){
		$this->boolean($name);
		return $this;		
	}
	
	function bit(string $name, int $len){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'BIT';
		$this->fields[$this->current_field]['len'] = $len;		
		return $this;		
	}
	
	function decimal(string $name, int $len = 15, int $len_dec = 4){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DECIMAL';
		$this->fields[$this->current_field]['len'] = [$len, $len_dec];		
		return $this;		
	}	
	
	function float(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'FLOAT';
		return $this;		
	}	
	
	function double(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DOUBLE';
		return $this;		
	}	
	
	function real(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'REAL';
		return $this;		
	}	
	
	function char(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'CHAR';
		return $this;		
	}	
	
	function varchar(string $name, int $len = 60){
		if ($len > 65535)
			throw new \InvalidArgumentException("Max length is 65535");
		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'VARCHAR';
		$this->fields[$this->current_field]['len'] = $len;
		return $this;		
	}	

	// alias de varchar
	function string(string $name, int $len = 60){
		return $this->varchar($name, $len);
	}
	
	function text(string $name, int $len = NULL){
		if ($len > 65535)
			throw new \InvalidArgumentException("Max length is 65535");
		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TEXT';
		
		if ($len != NULL)
			$this->fields[$this->current_field]['len'] = $len;
		
		return $this;		
	}	
	
	function tinytext(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TINYTEXT';
		return $this;		
	}
	
	function mediumtext(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'MEDIUMTEXT';
		return $this;		
	}
	
	function longtext(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'LONGTEXT';
		return $this;		
	}
	
	function varbinary(string $name, int $len = 60){
		if ($len > 65535)
			throw new \InvalidArgumentException("Max length is 65535");
		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'VARBINARY';
		$this->fields[$this->current_field]['len'] = $len;
		return $this;		
	}
	
	function blob(string $name){
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'BLOB';
		return $this;		
	}	
	
	function binary(string $name, int $len){
		if ($len > 255)
			throw new \InvalidArgumentException("Max length is 65535");
		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'BINARY';
		$this->fields[$this->current_field]['len'] = $len;
		return $this;		
	}
	
	function tinyblob(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TINYBLOB';
		return $this;		
	}
	
	function mediumblob(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'MEDIUMBLOB';
		return $this;		
	}
	
	function longblob(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'LONGBLOB';
		return $this;		
	}
	
	function json(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'JSON';
		return $this;		
	}
	
	function set(string $name, array $values){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'SET';
		$this->fields[$this->current_field]['array'] = $values;
		return $this;		
	}
	
	function enum(string $name, array $values){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'ENUM';
		$this->fields[$this->current_field]['array'] = $values;
		return $this;		
	}
	
	function time(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TIME';
		return $this;		
	}
	
	function year(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'YEAR';
		return $this;		
	}
	
	function date(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DATE';
		return $this;		
	}
	
	function datetime(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DATETIME';
		return $this;		
	}
	
	function timestamp(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'TIMESTAMP';
		return $this;		
	}
	
	function softDeletes(){		
		$this->current_field = 'deleted_at';
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DATETIME';
		return $this;		
	}
	
	function datetimes(){		
		$this->current_field = 'created_at';
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DATETIME';
		$this->current_field = 'updated_at';
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'DATETIME';
		return $this;		
	}

	// De momento es alias de datetimes()
	function timestamps(){
		return $this->datetimes();
	}
	
	function point(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'POINT';
		return $this;		
	}
	
	function multipoint(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'MULTIPOINT';
		return $this;		
	}
	
	function linestring(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'LINESTRING';
		return $this;		
	}
	
	function polygon(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'POLYGON';
		return $this;		
	}
	
	function multipolygon(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'MULTIPOLYGON';
		return $this;		
	}
	
	function geometry(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'GEOMETRY';
		return $this;		
	}
	
	function geometrycollection(string $name){		
		$this->current_field = $name;
		$this->fields[$this->current_field] = [];
		$this->fields[$this->current_field]['type'] = 'GEOMETRYCOLLECTION';
		return $this;		
	}	
	
	// collation && charset 
	
	function collation(string $val){
		$this->fields[$this->current_field]['collation'] = $val;
		return $this;		
	}

	// alias
	function collate(string $val){
		$this->collation($val);
		return $this;		
	}
	
	function charset(string $val){
		$this->fields[$this->current_field]['charset'] = $val;
		return $this;		
	}
	
	/* 
		modifiers
	*/
	
	// autoincrement
	function auto(bool $val = true){
		$this->fields[$this->current_field]['auto'] =  $val;
		return $this;
	}

	function addAuto(){
		$this->fields[$this->current_field]['auto'] =  true;
		return $this;	
	}

	function dropAuto(){
		$this->current_field = static::getAutoIncrementField($this->tb_name);
		return $this->auto(false);
	}

	// alias de auto(false)
	function notAuto(){
		return $this->dropAuto();
	}

	/*
		This function only set as nullable but don't drop default as dropNullable()
	*/
	function nullable(bool $value =  true){
		$this->fields[$this->current_field]['nullable'] =  $value ? 'NULL' : 'NOT NULL';
		return $this;
	}

	function dropNullable(){
		return $this->dropDefault()->nullable(false);
	}

	// alias de dropNullable()
	function notNullable(){
		return $this->dropNullable();
	}
	
	function commentField(string $string){
		$this->fields[$this->current_field]['comment'] =  $string;
		return $this;
	}

	// alias
	function comment(string $string){
		return $this->commentField($string);
	}

	function dropCommentField(){
		// ..
	}
	
	function default($val = NULL){
		if ($val === NULL) {
			$val = 'NULL';
		} elseif ($val === false) {
			$val = NULL;
		}

		$this->fields[$this->current_field]['default'] =  $val;
		return $this;
	}
	
	function dropDefault(){
		$this->fields[$this->current_field]['default'] =  NULL;
		return $this;
	}

	function currentTimestamp(){
		$this->default('current_timestamp()');	
		return $this;
	}
	
	protected function setAttr($attr){
		if (!in_array($attr, ['UNSIGNED', 'UNSIGNED ZEROFILL', 'BINARY'])){
			throw new \Exception("Attribute '$attr' is not valid.");
		}

		$this->fields[$this->current_field]['attr'] = $attr;
	}

	// clears any attribute {'UNSIGNED', 'UNSIGNED ZEROFILL', 'BINARY'}
	function dropAttr(){
		$this->fields[$this->current_field]['attr'] = NULL;
		return $this;
	}
	
	function unsigned(){
		$this->setAttr('UNSIGNED');
		return $this;
	}
	
	function zeroFill(){
		$this->setAttr('UNSIGNED ZEROFILL');
		return $this;
	}
	
	function binaryAttr(){
		$this->setAttr('BINARY');
		return $this;
	}
	
	// ALTER TABLE `aaa` ADD `ahora` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `fecha`;
	function onUpdateCurrent(){
		$this->setAttr('current_timestamp()');	
		return $this;
	}
	
	function after(string $field){
		$this->fields[$this->current_field]['after'] =  $field;
		return $this;
	}
	
	// ALTER TABLE `aaa` ADD `inicio` INT NOT NULL FIRST;
	function first(){
		if (isset($this->fields[$this->current_field]['after']))
			unset($this->fields[$this->current_field]['after']);
		
		foreach ($this->fields as $k => $field){
			if (isset($this->fields[$k]['first']))
				unset($this->fields[$k]['first']);
		}	
		
		$this->fields[$this->current_field]['first'] =  true;
		return $this;
	}
	
	// FKs
	
	function foreign(string $field_name){
		$this->current_field = $field_name;
		$this->fks[$this->current_field] = [];
		return $this;
	}

	// alias for foreign
	function foreignId(string $field_name){
		return $this->foreign($field_name);
	}

	// alias for foreign
	function fk(string $field_name){
		return $this->foreign($field_name);
	}
	
	// alias for foreign
	function fromField(string $field_name){
		return $this->foreign($field_name);
	}

	function references(string $field_name){
		$this->fks[$this->current_field]['references'] = $field_name;
		return $this;
	}

	// alias for references()
	function toField(string $field_name){
		return $this->references($field_name);
	}
	
	function on(string $table){
		$this->fks[$this->current_field]['on'] = $table;
		return $this;
	}

	// alias for on() + references('id')
	function constrained(string $table){
		$this->references('id');
		return $this->on($table);
	}

	// alias for on()
	function onTable(string $table){
		return $this->on($table);
	}

	// alias for on()
	function toTable(string $table){
		return $this->on($table);
	}
		
	function onDelete(string $action){
		$action = strtoupper($action);
		
		if (!in_array($action, ['CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL'])){
			throw new \InvalidArgumentException("Action for ON DELETE / ON CASCADE should be ['CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL']");
		}

		$this->fks[$this->current_field]['on_delete'] = $action;
		return $this;
	}
	
	function onUpdate(string $action){
		$action = strtoupper($action);
		
		if (!in_array($action, ['CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL', 'SET DEFAULT'])){
			throw new \InvalidArgumentException("Invalid action '$action'. Action for ON DELETE / ON CASCADE should be ['CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL', 'SET DEFAULT']");
		}

		$this->fks[$this->current_field]['on_update'] = $action;
		return $this;
	}

	function constraint(string $constraint_name){
		$this->fks[$this->current_field]['constraint'] = $constraint_name;
		return $this;
	}

	
	// INDICES >>>
	
	protected function setIndex(string $type){
		$type = strtoupper($type);

		if (!in_array($type, ['PRIMARY', 'UNIQUE', 'INDEX', 'FULLTEXT', 'SPATIAL']))
			throw new \InvalidArgumentException("Invalid index $type");
		
		$this->indices[$this->current_field] = $type;
		//dd($this->indices);
	}
	
	function primary(){
		$this->setIndex('PRIMARY');
		return $this;
	}
	
	// alias of primary()
	function pri(){
		return $this->primary();
	}
	
	function unique(){
		$this->setIndex('UNIQUE');
		return $this;
	}
	
	function index(){
		$this->setIndex('INDEX');
		return $this;
	}
	
	function fulltext(){
		$this->setIndex('FULLTEXT');
		return $this;
	}
	
	function spatial(){
		$this->setIndex('SPATIAL');
		return $this;
	}
	
	///////////////////////////////
	
	/*
		`nombre_campo` tipo[(longitud)] [(array_set_enum)] [charset] [collate] [attributos] NULL|NOT_NULL [default] [AUTOINCREMENT]
	*/
	function getDefinition($field){
		$cmd = '';		
		if (in_array($field['type'], ['SET', 'ENUM'])){
			$values = implode(',', array_map(function($e){ return "'$e'"; }, $field['array']));	
			$cmd .= "($values) ";
		}else{
			if (isset($field['len'])){
				$len = implode(',', (array) $field['len']);	
				$cmd .= "($len) ";
			}else
				$cmd .= " ";	
		}
		
		if (isset($field['attr'])){
			$cmd .= "{$field['attr']} ";
		}
		
		if (isset($field['charset'])){
			$cmd .= "CHARACTER SET {$field['charset']} ";
		}
		
		if (isset($field['collation'])){
			$cmd .= "COLLATE {$field['collation']} ";
		}
			
		if (isset($field['nullable'])){
			$cmd .= "{$field['nullable']} ";
		}else
			$cmd .= "NOT NULL ";

		if (isset($field['default'])){
			$cmd .= "DEFAULT {$field['default']} ";
		}

		if (isset($field['auto'])){
			$cmd .= "AUTO_INCREMENT PRIMARY KEY";
		}
		
		return trim($cmd);
	}

	private function showTable(){
		$conn = DB::getConnection();
		
		$stmt = $conn->query("SHOW CREATE TABLE `{$this->tb_name}`", \PDO::FETCH_ASSOC);
		$res  = $stmt->fetch();
		
		return $res;
	}
		
	// FOREIGN KEY (`abono_id`) REFERENCES `abonos` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
	private function addFKs(){
		foreach ($this->fks as $name => $fk){
			$on_delete  = !empty($fk['on_delete'])  ? 'ON DELETE ' .$fk['on_delete']  : '';
			$on_update  = !empty($fk['on_update'])  ? 'ON UPDATE ' .$fk['on_update']  : '';
			$constraint = !empty($fk['constraint']) ? 'CONSTRAINT `'.$fk['constraint'].'`' : '';
			
			$this->commands[] = trim("ALTER TABLE  `{$this->tb_name}` ADD $constraint FOREIGN KEY (`$name`) REFERENCES `{$fk['on']}` (`{$fk['references']}`) $on_delete $on_update").';';
		}
	} 

	function createTable(bool $ignore_if_exists = false, bool $ignore_warnings = true){
		if (!$ignore_if_exists){
			if ($this->tableExists()){
				if ($ignore_warnings){
					return false;
				}

				throw new TableAlreadyExistsException("Table {$this->tb_name} already exists");
			}
		}		

		if (empty($this->fields)){
			if ($ignore_warnings){
				return false;
			}

			throw new EmptySchemaException("No fields!");
		}	

		if ($this->engine == NULL){
			throw new \Exception("Please specify table engine");
		}
		
		if ($this->charset == NULL){
			throw new \Exception("Please specify charset");
		}

		$this->commands = [
			'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
			/*
			'SET AUTOCOMMIT = 0;',
			'START TRANSACTION;',
			*/
			'SET time_zone = "+00:00";'
		];
	
		$cmd = '';
		foreach ($this->fields as $name => $field){
			$cmd .= "`$name` {$field['type']} ";			
			$cmd .= $this->getDefinition($field);	
			$cmd .= ",\n";
		}
		
		$cmd = substr($cmd,0,strlen($cmd)-2);

		$if_not = $ignore_if_exists ? 'IF NOT EXISTS' : '';		
		$cmd = "CREATE TABLE $if_not `{$this->tb_name}` (\n$cmd\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset};";
		
		$this->commands[] = $cmd;
		
		// Indices
		
		/*
			Ver en versión con "pending changes" 
		*/
		if (count($this->indices) >0)
		{			
			$cmd = '';		
			foreach ($this->indices as $nombre => $tipo){
			
				switch ($tipo){
					case 'INDEX':
						$cmd .= "ADD INDEX (`$nombre`),\n";
					break;
					case 'PRIMARY':
						// PRIMARY can not be "ADDed"
					break;
					case 'UNIQUE':
						$cmd .= "ADD UNIQUE KEY `$nombre` (`$nombre`),\n";
					break;
					case 'SPATIAL':
						$cmd .= "ADD SPATIAL KEY `$nombre` (`$nombre`),\n";
					break;
					case 'FULLTEXT':
						$cmd .= "ADD FULLTEXT KEY `$nombre` (`$nombre`),\n";  // sin probar
						break;
					
					default:
						throw new \Exception("Invalid index type");
				}				
			}
			
			$cmd = substr($cmd,0,-2);
			$cmd = "ALTER TABLE `{$this->tb_name}` \n$cmd;";
			
			$this->commands[] = $cmd;
		}		
		
		
		// FKs		
		$this->addFKs();
				
		//$this->commands[] = 'COMMIT;';		
		$this->query = implode("\r\n",$this->commands)."\n";

		if (!$this->exec){
			return;
		}

		$conn = DB::getConnection();  
			
		DB::beginTransaction();
		try {
			// $rollback = function() use ($conn){
			// 	$st = $conn->prepare("DROP TABLE IF EXISTS `{$this->tb_name}`;");
			// 	$res = $st->execute();
			// };

			foreach($this->commands as $change){     
				$res = DB::statement($change);
			}

			DB::commit();

		} catch (\PDOException $e) {
			DB::rollback();
			throw $e;		
        } catch (\Exception $e) {;
			DB::rollback();
            throw $e;
        } catch (\Throwable $e) {
			DB::rollback();
            throw $e;   
        }     

		return true;
	}

	// alias
	function create(bool $if_not_exists = false){
		return $this->createTable($if_not_exists);
	}
	
	// alias
	function createIfNotExists(){
		return $this->createTable(true);
	}

	function dropTable(){
		$this->commands[] = "DROP TABLE `{$this->tb_name}`;";
		return $this;
	}

	function dropTableIfExists(){
		$this->commands[] = "DROP TABLE IF EXISTS `{$this->tb_name}`;";
		return $this;
	}


	// TRUNCATE `az`.`xxy`
	function truncateTable(string $tb){
		$this->commands[] = "TRUNCATE `{$this->tb_name}`.`$tb`;";
		return $this;
	}


	// RENAME TABLE `az`.`xxx` TO `az`.`xxy`;
	function renameTableTo(string $final){
		$this->commands[] = "RENAME TABLE `{$this->field}` TO `$final`;";
		return $this;
	}	


	function dropColumn(string $name){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` DROP `$name`;";
		return $this;
	}

	// https://popsql.com/learn-sql/mysql/how-to-rename-a-column-in-mysql/
	function renameColumn(string $ori, string $final){
		if (DB::driver() == 'mysql' && !DB::isMariaDB() && DB::driverVersion(true) >= 8){
			$this->commands[] = "ALTER TABLE `{$this->tb_name}` RENAME COLUMN `$ori` TO `$final`;";
		} else {
			if (!isset($this->prev_schema['fields'][$ori])){
				throw new \InvalidArgumentException("Schema error. Column '$ori' does not exist in `{$this->tb_name}`");
			}

			$datatype = $this->prev_schema['fields'][$ori]['type'];

			if (isset($this->prev_schema['fields'][$this->current_field]['array'])){
				$datatype .= '(' . implode(',', $this->fields[$this->current_field]['array']). ')';
			} elseif (isset($this->prev_schema['fields'][$this->current_field]['len'])){
				$datatype .= '(' . $this->prev_schema['fields'][$this->current_field]['len'] . ')';
			} 

			$this->commands[] = "ALTER TABLE `{$this->tb_name}` CHANGE `$ori` `$final` $datatype;";
		}

		return $this;
	}
	
	function renameColumnTo(string $final){		
		return $this->renameColumn($this->current_field, $final);
	}

	/*	
		@param string|Array
	*/
	function addIndex($column){
		if (is_array($column)){
			$cols = implode(',', Strings::backticks($column));
		} else {
			$cols = "`$column`";
		}

		$this->commands[] = "ALTER TABLE `{$this->tb_name}` ADD INDEX($cols);";
		return $this;
	}

	function dropIndex(string $name){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` DROP INDEX `$name`;";
		return $this;
	}

	// https://stackoverflow.com/questions/1463363/how-do-i-rename-an-index-in-mysql
	function renameIndex(string $ori, string $final){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` RENAME INDEX `$ori` TO `$final`;";
		return $this;
	}

	// alias
	function renameIndexTo(string $final){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` RENAME INDEX `{$this->current_field}` TO `$final`;";
		return $this;
	}


	function addPrimary(string $column){
		$pks = static::getPKs($this->tb_name);

		foreach ($this->commands as $ix => $command){
			if (preg_match('/ ADD PRIMARY KEY\(([^)]+)\)/', $command, $matches)){
				$new_pk = $matches[1];
				unset($this->commands[$ix]);
			}
		}

		$cols = '';
		if (!empty($pks) || isset($new_pk)){
			$cols = implode(',', Strings::backticks($pks));
	
			if (isset($new_pk)){
				if (!empty($cols)){
					$cols .= ', ';	
				}

				$cols .= $new_pk;
			}

			if (!empty($cols)){
				$cols .= ', ';	
			}

			$cols .= "`$column`";

			// Kill duplicates
			$cols_ay = explode(',', $cols);			
			$cols_ay = Strings::trimArray($cols_ay);
			$cols_ay = array_unique($cols_ay, SORT_REGULAR);
			$cols = implode(',', $cols_ay);

			$drop_old_pk = !empty($pks) ? 'DROP PRIMARY KEY,' : '';

			$this->commands[] = "ALTER TABLE `{$this->tb_name}` $drop_old_pk ADD PRIMARY KEY($cols);";
		} else {
			$this->commands[] = "ALTER TABLE `{$this->tb_name}` ADD PRIMARY KEY(`$column`);";
		}
		
		return $this;	
	}
		
	// implica primero remover el AUTOINCREMENT sobre el campo !
	// ej: ALTER TABLE `super_cool_table` CHANGE `id` `id` INT(11) NOT NULL;
	function dropPrimary(){
		$auto = static::getAutoIncrementField($this->tb_name);

		if (!empty($auto)){
			$sc = new Schema($this->tb_name);
			$sc
			->dontExec()
			->dropAuto()
			->alter();
	
			$this->commands[] = $sc->dd();
		}

		$this->commands[] = "ALTER TABLE `{$this->tb_name}` DROP PRIMARY KEY;";
		return $this;
	}

	/*
		Permite crear definir UNIQUEs de uno o varios campos

		@param string|Array
	*/
	function addUnique($column){
		if (is_array($column)){
			$cols = implode(',', Strings::backticks($column));
		} else {
			$cols = "`$column`";
		}
		
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` ADD UNIQUE($cols);";
		
		return $this;
	}

	// setea el campo actual como UNIQUE (de forma solitaria)
	function setUnique(){		
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` ADD UNIQUE(`{$this->current_field}`);";
		return $this;
	}
		
	function dropUnique(string $constraint_name){
		$this->commands[] = $this->dropIndex($constraint_name);
		return $this;
	}

	function addSpatial(string $column){
		$this->commands[] = "ALTER TABLE ADD SPATIAL INDEX(`$column`);";
		return $this;
	}
		
	function dropSpatial(string $name){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` DROP `$name`;";
		return $this;
	}	

	function addFullText(string $column){
		$this->commands[] = "ALTER TABLE ADD FULLTEXT INDEX(`$column`);";
		return $this;
	}

	function dropForeign(string $constraint_name){
		$this->commands[] = "ALTER TABLE `{$this->tb_name}` DROP FOREIGN KEY `$constraint_name`";
		return $this;
	}

	// alias
	function dropFK(string $constraint_name){
		return $this->dropForeign($constraint_name);
	}


	// From DB 
	//
	protected function fromDB(){
		if (!in_array($this->tb_name, $this->tables)){
			return;
		}

		$table_def = $this->showTable();

		if ($table_def == NULL){
			throw new \Exception("[ Fatal error ] Table definition could not be recovered");
		}

		$lines = explode("\n", $table_def["Create Table"]);
		$lines = array_map(function($l){ return trim($l); }, $lines);
		
		$last_line     = $lines[count($lines) -1];
		$this->prev_schema['engine']  = Strings::slice($last_line, '/ENGINE=([a-zA-Z][a-zA-Z0-9_]+)/');
		$this->prev_schema['charset'] = Strings::slice($last_line, '/CHARSET=([a-zA-Z][a-zA-Z0-9_]+)/');

		$fields = [];
		$cnt = count($lines)-1;
		for ($i=1; $i<$cnt; $i++){
			$str = $lines[$i];

			if ($lines[$i][0] == '`')
			{
				$field 		= NULL;
				$type  		= NULL;
				$array		= NULL;				
				$len   		= NULL;
				$charset  	= NULL;
				$collation 	= NULL;
				$nullable	= NULL;
				$default	= NULL;
				$auto 		= NULL;
				$check 		= NULL;
				
				$field      = Strings::slice($str, '/`([a-z_]+)`/i');
				$type       = Strings::slice($str, '/([a-z_]+)/i');

				$this->raw_lines[$field] = $lines[$i];

				if ($type == 'enum' || $type == 'set'){
					$array = Strings::slice($str, '/\((.*)\)/i');
				}else{
					$len = Strings::slice($str, '/\(([0-9,]+)\)/');					
				}

				$to_lo = function($s){ return empty($s) ? '' : strtolower($s); };
				$to_up = function($s){ return empty($s) ? '' : strtoupper($s); };


				$charset    = Strings::slice($str, '/CHARACTER SET ([a-z0-9_]+)/');
				$collation  = Strings::slice($str, '/COLLATE ([a-z0-9_]+)/');
				
				$default    = Strings::slice($str, '/DEFAULT ([a-zA-Z0-9_\(\)]+)/');
				//dd($default, "DEFAULT($field)");

				$nullable   = Strings::slice($str, '/(NOT NULL)/') == NULL;
				$auto       = Strings::slice($str, '/(AUTO_INCREMENT)/') == 'AUTO_INCREMENT';
				//dd($nullable, "NULLABLE($field)");


				/*
					 Attributes
				*/
				$unsigned = Strings::slice($str, '/(unsigned)/i', $to_lo) == 'unsigned';
				$zerofill = Strings::slice($str, '/(zerofill)/i', $to_lo) == 'zerofill';
				$binary   = Strings::slice($str, '/(binary)/i'  , $to_lo) == 'binary';
	
				$attr = [];

				if ($unsigned != null){
					$attr[] = 'unsigned'; 
				}

				if ($zerofill != null){
					$attr[] = 'zerofill';
				}

				if ($binary != null){
					$attr[] = 'binary';
				}

					
				//if (strlen($str)>1)
				//	throw new \Exception("Parsing error!");				
				
				/*
				dd($field, 'FIELD ***');
				dd($lines[$i], 'LINES');
				dd($type, 'TYPE');
				dd($array, 'ARRAY / SET');
				dd($len, 'LEN');
				dd($charset, 'CHARSET');
				dd($collation, 'COLLATION');
				dd($nullable, 'NULLBALE');
				dd($default, 'DEFAULT');
				dd($auto, 'AUTO');
				dd($check, 'CHECK');
				echo "-----------\n";
				*/
								

				$this->prev_schema['fields'][$field]['type'] = strtoupper($type);
				$this->prev_schema['fields'][$field]['auto'] = $auto; 
				$this->prev_schema['fields'][$field]['attr'] = $attr;  // recién integrado 11-dic-2021
				$this->prev_schema['fields'][$field]['len'] = $len;
				$this->prev_schema['fields'][$field]['array'] = $array;
				$this->prev_schema['fields'][$field]['nullable'] = $nullable;
				$this->prev_schema['fields'][$field]['charset'] = $charset;
				$this->prev_schema['fields'][$field]['collation'] = $collation;
				$this->prev_schema['fields'][$field]['default'] = $default;
				// $this->prev_schema['fields'][$field]['after'] =  ...
				// $this->prev_schema['fields'][$field]['first'] = ...

			}else{
				// Son índices de algún tipo
				//dd($str, 'STR');
				
				$constraint = Strings::slice($str, '/CONSTRAINT `([a-zA-Z0-9_]+)` /', function($s){
					return ($s != null) ? $s : 'DEFAULT';
				});

				/*

					PRI KEY Simple:
						PRIMARY KEY (`id`),

					PRI KEY Compuesta:
						PRIMARY KEY (`id`,`co`) USING BTREE

					PRI KEY Con nombre: 
						CONSTRAINT `pk_id` PRIMARY KEY (`id`,`co`) USING BTREE

					
					https://stackoverflow.com/a/3303836/980631

				*/
				$primary = Strings::slice($str, '/PRIMARY KEY \(`([a-zA-Z0-9_]+)`\)/');	// revisar

				/*
			
					Compuesto:
						UNIQUE KEY `correo` (`correo`,`hora`) USING BTREE,

				*/
				$unique  = Strings::sliceAll($str, '/UNIQUE KEY `([a-zA-Z0-9_]+)` \(`([a-zA-Z0-9_]+)`\)/');  // revisar				
				
				/*
					Indices
				*/
		
				$spatial  = Strings::sliceAll($str, '/SPATIAL KEY `([a-zA-Z0-9_]+)` \(([a-zA-Z0-9_`,]+)\)/');

				$fulltext = Strings::sliceAll($str, '/FULLTEXT KEY `([a-zA-Z0-9_]+)` \(([a-zA-Z0-9_`,]+)\)/');
		
				/*
					IDEM

					https://dev.mysql.com/doc/refman/8.0/en/create-index.html
				*/
				$indexs  = Strings::sliceAll($str, '/KEY `([a-zA-Z0-9_]+)` \(`([a-zA-Z0-9_]+)`\)/'); // revisar

				$ix_type 			= Strings::slice($str, '/USING (BTREE|HASH)/');
				$algorithm_option	= Strings::slice($str, '/ALGORITHM[ ]?[=]?[ ]?(DEFAULT|INPLACE|COPY)/');
				$lock_option		= Strings::slice($str, '/LOCK[ ]?[=]?[ ]?(DEFAULT|NONE|SHARED|EXCLUSIVE)/');
				
				
				/*
					CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

					--[ CONSTRAINT ]-- 
					'facturas_ibfk_1'


					--[ FK ]-- 
					'user_id'


					--[ REFERENCES ]-- 
					array (
					0 => 'users',
					1 => 'id',
					)

					--[ ON UPDATE ]-- 
					NULL

					--[ ON DELETE ]-- 
					'CASCADE'

				*/

				$fk            = Strings::slice($str, '/FOREIGN KEY \(`([a-zA-Z0-9_]+)`\)/');
				$fk_ref        = Strings::sliceAll($str, '/REFERENCES `([a-zA-Z0-9_]+)` \(`([a-zA-Z0-9_]+)`\)/');
				$fk_on_update  = Strings::slice($str, '/ON UPDATE (RESTRICT|NO ACTION|CASCADE|SET NULL)/');
				$fk_on_delete  = Strings::slice($str, '/ON DELETE (RESTRICT|NO ACTION|CASCADE|SET NULL)/');

				/*
				if ($fk != null){
					dd($fk, 'FK');
					dd($fk_ref, 'REFERENCES');
					dd($fk_on_update, 'ON UPDATE');
					dd($fk_on_delete, 'ON DELETE'); 
				}
				*/

				// [CONSTRAINT [symbol]] CHECK (expr) [[NOT] ENFORCED]	
			$check   = Strings::sliceAll($str, '/CHECK \((.*)\) (ENFORCED|NOT ENFORCED)/');
			
			/*
					Sin probar (req. MySQL 8.0+)

					array(1) {
						["checks"]=>
						array(1) {
							["post_content_check"]=>
							array(1) {
							[0]=>
							array(2) {
								[0]=>
								string(94) " CASE WHEN DTYPE = 'Post' THEN CASE WHEN content IS NOT NULL THEN 1 ELSE 0 END ELSE 1 END = 1 "
								[1]=>
								string(12) "NOT ENFORCED"
							}
							}
						}
					}

					https://stackoverflow.com/questions/7522026/how-do-i-add-a-custom-check-constraint-on-a-mysql-table
				*/	
				if ($check != null){
					$prev_schema['checks'] [$constraint] [] = $check;   
				} else {	
					$check   = Strings::slice($str, '/CHECK \((.*)\)/');
				
					if ($check != null){
						$prev_schema['checks'] [$constraint] [] = [$check]; 
					}	
				}			

				$fn_rl = function($str){
					return str_replace('`', '', $str);
				};


				/*
				dd($constraint, 'CONSTRAINT', function($val){
					return ($val != null);
				});
				*/
				

				//dd($str, "RESIDUO DE STR for {$lines[$i]}");					

				
				if ($primary != NULL){
					$this->prev_schema['indices'][$primary] = 'PRIMARY';
				} elseif ($unique != NULL){
					foreach ($unique as $u){
						$this->prev_schema['indices'][$u] = 'UNIQUE';	
					}
				}if ($indexs != NULL){
					foreach ($indexs as $index){
						$this->prev_schema['indices'][$index] = 'INDEX';
					}
				}

				// Probar en lugar del bloque anterior:
				
				// if ($primary != NULL){	
				// 	$tmp = explode(',',$primary);
				// 	$this->prev_schema['indices']['PRIMARY'] [$constraint ] = [
				// 		'fields' =>	array_map($fn_rl, $tmp)
				// 	];
				// } elseif ($unique != NULL){
				// 	$tmp = explode(',',$unique[1]);
				// 	$this->prev_schema['indices']['UNIQUE']  [$unique[0]  ] = [
				// 		'fields' => array_map($fn_rl, $tmp),
				// 		'index_type' => $ix_type,
				// 		'algorithm_option' => $algorithm_option,
				// 		'lock_option' => $lock_option
				// 	];
				// } elseif ($spatial != NULL){
				// 	$tmp = explode(',',$spatial[1]);
				// 	$this->prev_schema['indices']['SPATIAL'] [$spatial[0] ] = [
				// 		'fields' => array_map($fn_rl, $tmp),
				// 		'index_type' => $ix_type,
				// 		'algorithm_option' => $algorithm_option,
				// 		'lock_option' => $lock_option
				// 	];	
				// } elseif ($fulltext != NULL){
				// 	$tmp = explode(',',$fulltext[1]);
				// 	$this->prev_schema['indices']['FULLTEXT'][$fulltext[0]] = [
				// 		'fields' => array_map($fn_rl, $tmp),
				// 		'index_type' => $ix_type,
				// 		'algorithm_option' => $algorithm_option,
				// 		'lock_option' => $lock_option
				// 	];	
				// } elseif ($index != NULL){
				// 	$tmp = explode(',',$index[1]);
				// 	$this->prev_schema['indices']['INDEX']   [$index[0]   ] = array_map($fn_rl, $tmp);
				// } elseif ($fk != null){	
				// 	$this->fks[$fk]['references'] = $fk_ref[1];	
				// 	$this->fks[$fk]['on'] = $fk_ref[0];
				// 	$this->fks[$fk]['on_delete'] = 	$fk_on_delete ?? 'NO ACTION';
				// 	$this->fks[$fk]['on_update'] = 	$fk_on_update ?? 'NO ACTION';		
				// }

				

			}
		}
		
	}

	function dd(bool $sql_formatter = false){
		return Model::sqlFormatter(Strings::removeMultipleSpaces($this->query), $sql_formatter);
	}
	
	function change()
	{	
		// dd($this->indices, 'INDICES');
		// dd($this->prev_schema['indices'], 'PREVIOUS INDICES');

		foreach ($this->fields as $name => $field)
		{
			if (isset($this->prev_schema['fields'][$name])){
				$this->fields[$name] = array_merge($this->prev_schema['fields'][$name], $this->fields[$name]);
			} 		
			
			$field = $this->fields[$name];

			//dd($this->fields[$name]);
			//exit;

			$charset   = isset($field['charset']) ? "CHARACTER SET {$field['charset']}" : '';
			$collation = isset($field['collation']) ? "COLLATE {$field['collation']}" : '';
			
			$def = "{$this->fields[$name]['type']}";		
			if (in_array($field['type'], ['SET', 'ENUM'])){
				$values = implode(',', array_map(function($e){ return "'$e'"; }, $field['array']));	
				$def .= "($values) ";
			}else{
				if (isset($field['len'])){
					$len = implode(',', (array) $field['len']);	
					$def .= "($len) ";
				}else
					$def .= " ";	
			}
			
			// if (isset($field['attr'])){
			// 	$def .= "{$field['attr']} ";
			// }
			
			if (in_array($field['type'], ['CHAR', 'VARCHAR', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT', 'JSON', 'SET', 'ENUM'])){
				$def .= "$charset $collation ";	
			}		
			
			if (isset($field['nullable']) && $field['nullable'] == 'NULL'){  
				$def .= "NULL ";
			} else {		
				$def .= "NOT NULL ";
			}	

			/*			
			dd($field['nullable'], "NULLABLE ($name)");
			dd($field['default'], "DEFAULT ($name)");
			exit;
			*/

			if (isset($field['nullable']) && !$field['nullable'] && isset($field['default']) && $field['default'] == 'NULL'){
				throw new \Exception("Column `$name` can not be not nullable but default 'NULL'");
			}
				
			if (isset($field['default'])){
				$def .= "DEFAULT {$field['default']} ";
			}
			
			if (isset($field['auto'])){
				if ($field['auto'] == false){
					$def = str_replace('AUTO_INCREMENT', '', $def);
				} else {
					$def .= ' AUTO_INCREMENT';
				}				
			}
			
			if (isset($field['after'])){  
				$def .= "AFTER {$field['after']}";
			} elseif (isset($field['first'])){
				$def .= "FIRST ";
			}

			$def = trim(preg_replace('!\s+!', ' ', $def));
			

			if (isset($this->prev_schema['fields'][$name])){
				$this->commands[] = "ALTER TABLE `{$this->tb_name}` CHANGE `$name` `$name` $def;";
			} else {
				$this->commands[] = "ALTER TABLE `{$this->tb_name}` ADD `$name` $def;";
			}	
		
		}

		//dd($this->indices, 'INDICES');

		foreach ($this->indices as $name => $type){			
			switch($type){
				case "INDEX":
					$this->addIndex($name);
				break;
				case "PRIMARY":
					$this->addPrimary($name);
				break;
				case "UNIQUE": 
					$this->addUnique($name);
				break;
				case "SPATIAL": 
					$this->addSpatial($name);
				break;
				case "FULLTEXT": 
					$this->addFullText($name);
				break;
			}
		}

		// FKs
		$this->addFKs();


		$this->query = implode("\r\n",$this->commands);
	
		if (!$this->exec){
			return;
		}

		$conn = DB::getConnection();   
		
		DB::beginTransaction();
		try{
			//dd($this->commands, 'SQL STATEMENTS');
			
			foreach($this->commands as $change){
				DB::statement($change);
			}		
			
			DB::commit();
		} catch (\PDOException $e) {
			DB::rollback();
			dd($change, 'SQL');
			dd($e->getMessage(), "PDO error");
			throw $e;		
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollback();     
			throw $e;       
        }     
	}	


	function dontExec(){
		$this->exec = false;
		return $this;
	}

	// alias
	function alter(){
		$this->change();
	}
	
	// reflexion
	
	function getSchema(){
		return [
			'engine'	=> $this->engine,
			'charset'	=> $this->charset,
			'collation'	=> $this->collation,
			'fields'	=> $this->fields,
			'indices'	=> $this->indices,
			'fks'		=> $this->fks
		];
	}

	function getCurrentSchema(){
		return $this->prev_schema;
	}

	/*
		Return array of all schema files
	*/
	static function getSchemaFiles($conn_id = null){
		if ($conn_id === null){
			$conn_id = DB::getCurrentConnectionId();
		}

		$files = Files::glob(SCHEMA_PATH . $conn_id, '*.php');

        $excluded = ['Pivots.php', 'Relations.php'];
        
        foreach ($files as $ix => $file){
            foreach ($excluded as $exclude){
                if (Strings::endsWith($exclude, $file)){
                    unset($files[$ix]);
                }
            }            
        }

		return $files;
	}

}
