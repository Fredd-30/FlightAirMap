<?php
require_once(dirname(__FILE__).'/settings.php');

class Connection{
	public $db = null;
	public $dbs = array();
	public $latest_schema = 17;
	
	public function __construct($dbc = null,$dbname = null) {
	    global $globalDBdriver;
	    if ($dbc === null) {
		if ($this->db === null && $dbname === null) {
		    $this->createDBConnection();
		} else {
		    $this->createDBConnection($dbname);
		}
	    } elseif ($dbname === null || $dbname === 'default') {
		$this->db = $dbc;
		if ($this->connectionExists() === false) {
			/*
			echo 'Restart Connection !!!'."\n";
			$e = new \Exception;
			var_dump($e->getTraceAsString());
			*/
			$this->createDBConnection();
		}
	    } else {
		//$this->connectionExists();
		$this->dbs[$dbname] = $dbc;
	    }
	}


	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/

	public function createDBConnection($DBname = null)
	{
		global $globalDBdriver, $globalDBhost, $globalDBuser, $globalDBpass, $globalDBname, $globalDebug, $globalDB, $globalDBport, $globalDBTimeOut, $globalDBretry, $globalDBPersistent;
		if ($DBname === null) {
			$DBname = 'default';
			$globalDBSdriver = $globalDBdriver;
			$globalDBShost = $globalDBhost;
			$globalDBSname = $globalDBname;
			$globalDBSuser = $globalDBuser;
			$globalDBSpass = $globalDBpass;
			if (!isset($globalDBport) || $globalDBport == NULL) $globalDBSport = 3306;
			else $globalDBSport = $globalDBport;
		} else {
			$globalDBSdriver = $globalDB[$DBname]['driver'];
			$globalDBShost = $globalDB[$DBname]['host'];
			$globalDBSname = $globalDB[$DBname]['name'];
			$globalDBSuser = $globalDB[$DBname]['user'];
			$globalDBSpass = $globalDB[$DBname]['pass'];
			if (isset($globalDB[$DBname]['port'])) $globalDBSport = $globalDB[$DBname]['port'];
			else $globalDBSport = 3306;
                }
                if (!isset($globalDBretry) || $globalDBretry == '' || $globalDBretry == null) $globalDBretry = 5;
		$i = 0;
		while (true) {
			try {
				$this->dbs[$DBname] = new PDO("$globalDBSdriver:host=$globalDBShost;port=$globalDBSport;dbname=$globalDBSname;charset=utf8", $globalDBSuser,  $globalDBSpass);
				$this->dbs[$DBname]->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
				$this->dbs[$DBname]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->dbs[$DBname]->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
				if (!isset($globalDBTimeOut)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,200);
				else $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,$globalDBTimeOut);
				if (!isset($globalDBPersistent)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,true);
				else $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,$globalDBPersistent);
				$this->dbs[$DBname]->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
				$this->dbs[$DBname]->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
				break;
			} catch(PDOException $e) {
				$i++;
				if (isset($globalDebug) && $globalDebug) echo $e->getMessage()."\n";
				//exit;
				if ($i > $globalDBretry) return false;
				//return false;
			}
		}
		if ($DBname === 'default') $this->db = $this->dbs['default'];
		return true;
	}

	public function tableExists($table)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SHOW TABLES LIKE '".$table."'";
		} elseif ($globalDBdriver == 'pgsql') {
			$query = "SELECT * FROM pg_catalog.pg_tables WHERE tablename = '".$table."'";
		}
		if ($this->db == NULL) return false;
		try {
			//$Connection = new Connection();
			$results = $this->db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	public function connectionExists()
	{
		global $globalDBdriver, $globalDBCheckConnection;
		if (isset($globalDBCheckConnection) && $globalDBCheckConnection === FALSE) return true;
		$query = "SELECT 1 + 1";
		if ($this->db == NULL) return false;
		try {
			$sum = @$this->db->query($query);
			if ($sum instanceof \PDOStatement) {
				$sum = $sum->fetchColumn(0);
			} else $sum = 0;
			if (intval($sum) !== 2) {
			     return false;
			}
			
		} catch(PDOException $e) {
			if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
            			throw $e;
	                }
	                //echo 'error ! '.$e->getMessage();
			return false;
		}
		return true; 
	}

	/*
	* Check if index exist
	*/
	public function indexExists($table,$index)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema=DATABASE() AND table_name='".$table."' AND index_name='".$index."'";
		} elseif ($globalDBdriver == 'pgsql') {
			$query = "SELECT 1 FROM   pg_class c JOIN   pg_namespace n ON n.oid = c.relnamespace WHERE c.relname = '".$index."' AND n.nspname = '".$table."'";
		}
		try {
			//$Connection = new Connection();
			$results = $Connection->$db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	/*
	* Get schema version
	* @return integer schema version
	*/
	public function check_schema_version() {
		$version = 0;
		if ($this->tableExists('aircraft')) {
			if (!$this->tableExists('config')) {
	    			$version = '1';
	    			return $version;
			} else {
				$Connection = new Connection();
				$query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage()."\n";
				}
				$result = $sth->fetch(PDO::FETCH_ASSOC);
				return $result['value'];
			}
		} else return $version;
	}
	
	/*
	* Check if schema version is latest_schema
	* @return Boolean if latest version or not
	*/
	public function latest() {
	    if ($this->check_schema_version() == $this->latest_schema) return true;
	    else return false;
	}

}
?>
