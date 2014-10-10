<?php
class Table
{
	/*
	 * Name of the table inside the database
	 * @var string
	 */
	private $table;

	/* TODO static & remove */
	private $pdo;
	
	/**
	 * Constructor
	 * Look for the PDO or create it. Name the table variable.
	 *
	 * @param Main $main - Main object
	 * @param string $table - Name of the table in the database
	 */
	public function __construct($main,$table)
	{
		if(!ctype_alpha($table))
			throw new InvalidArgumentException("Argument 2 passed to Table constructor must be a alphabetic string, ".gettype($table)." given");
		else if(!is_a($main,"Main"))
			throw new InvalidArgumentException("Argument 1 passed to Table constructor must be a Main object, ".get_class($main)." given");
		else
		{
			/* Connexion to the database */
			$host = 'localhost';
			$port = '';
			$bdd = 'bronydays';
			$user = 'root';
			$password = 'AppleJack';
			$charset = 'utf8';
			
			// /* TODO */
			// $host = $main->getConfig("host");
			// $port = $main->getConfig("port");
			// $bdd = $main->getConfig("bdd");
			// $user = $main->getConfig("user");
			// $password = $main->getConfig("password");
			// $charset = $main->getConfig("charset");

			$this->pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$bdd.';charset='.$charset, $user, $password);
			/* Return exception as error */
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			/* TODO Force name field to lowercase */
			$this->pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

		
			/* Name of the table */
			$this->table = $table;
		}
	}
	
	/**
	 * Insert a line into the database
	 *
	 * Transform the $line array into a string and send the SQL query
	 *
	 * @param array $line - Line to add to the DataBase - array("field1"=>"value1","field2"=>"value2")
	 */
	public function insert(array $line)
	{
		/* Secure the fields */
		$this->checkFields(array_keys($line));
		
		/* Add id if not included */
		if(!array_key_exists("id",$line))
			$line["id"] = uniqid("",true);
		else
			$this->checkId($line["id"]);

		/* Prepare the value */
		$i = 0;
		$stockedvalue = array();
		foreach($line as $key=>$value)
		{
			$stockedvalue[":param".$i] = $value;
			$line[$key] = ":param".$i;
			$i++;
		}
	
		/* Associative Array $line > 2 String */
		$field = [];
		$value = [];
		foreach ($line as $onefield => $onevalue)
		{
			$field[] = $onefield;
			$value[] = $onevalue;
		}

		/* Query */
		$query = "INSERT INTO `".$this->table."` (`".implode("`,`",$field)."`) VALUES (".implode(",",$value).");";
		$sth = $this->pdo->prepare($query);
		$sth->execute($stockedvalue);
	}
	
	/**
	 * Insert lines into the database
	 *
	 * Transform the $line array into a string
	 * Loops to send one INSERT with 1000 rows max
	 * 
	 * @param array $lines - Lines to add to the DataBase - array( [0] => array("field1"=>"value1","field2"=>"value2"), [1] => array("field2"=>"value4"))
	 */
	public function insertBatch(array $lines)
	{
		/* SQL can't insert more than 1000 rows with one insert */
		if(count($lines)>1000)
			$maxarray = array_chunk($lines,1000);
		else
			$maxarray = [$lines];
		
		$prepare = array();
		foreach($maxarray as $lines)
		{
			/* Secure the fields lines */
			foreach($lines as $line)
				$this->checkFields(array_keys($line));
			
			/* Add id if not included */
			foreach($lines as &$derp1)
			{
				if(!array_key_exists("id",$derp1))
					$derp1["id"] = uniqid("",true);
				else
					$this->checkId($derp1["id"]);
			}
			
			/* Prepare the value */
			$i = 0;
			$stockedvalue = array();
			foreach($lines as &$derp2)
			{
				foreach($derp2 as $key=>$value)
				{
					$stockedvalue[":param".$i] = $value;
					$derp2[$key] = ":param".$i;
					$i++;
				}
			}

			/* Create the fields SQL string */
			$fields = array();
			foreach($lines as $line)
				$fields = array_merge($fields,$line);
			$fields = array_keys($fields);
			$sqlfields = "(`".implode("`,`",$fields)."`)";

			/* Create the values SQL string */
			$sqlvalues = array();
			foreach($lines as $line)
			{
				$sqlline = array();
				foreach($fields as $field)
				{
					if(array_key_exists($field,$line))
						$sqlline[] = $line[$field];
					else
						$sqlline[] = "DEFAULT";
				}
				/* Prepare the value */
				$sqlvalues[] =  "(".implode(",",$sqlline).")";
			}
			$sqlvalues = implode(",",$sqlvalues);

			/* Query */
			$prepare[] = array("sql"=>"INSERT INTO `".$this->table."` ".$sqlfields." VALUES ".$sqlvalues.";","values"=>$stockedvalue);
		}
		
		
		/* Execute each prepared query */
		foreach($prepare as $insert)
		{
			$sth = $this->pdo->prepare($insert["sql"]);
			$sth->execute($insert["values"]);
		}
		/* TODO Insert ARRAY() */
		/* TODO Delete if exception */
	}

	/**
	 * Get line(s) from the database
	 *
	 * Select line(s) from the database with WHERE, which FIELDS and INNER JOIN options
	 *
	 * @param array $where - WHERE condition(s), return all if ["all"] - array("field1"=>"value1","field2"=>"value2")
	 * @param array $fields - *optional - Which field(s) to get, return all if NULL - array("field1","field2")
	 * @param array $join - *optional - Table to JOIN - array("other table","field table 1","field table 2")
	 *
	 * @return array - Return Line(s) selected - array( [0]=> array("field1"=>"value1","field2"=>"value2"), [1]=> array("field1"=>"value3","field2"=>"value4"))
	 */
	public function get(array $where, array $fields = NULL, array $join = NULL)
	{
		/* Secure the fields */
		if($fields != NULL)
			$this->checkFields($fields);
			
		/* Secure the join */
		if($join != NULL)
			$this->checkFields($join);
		
		/* Array to string, if $fields is null : Return all fields */
		if(empty($fields))
			$fields = "*";
		else
			$fields = "`".implode("`,`",$fields)."`";

		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);
		$stockedvalue = $sqlwhere["values"];
		$sqlwhere = $sqlwhere["sql"];
			
		/* Assemble the Join to string format */
		if(!empty($join) && (count($join) == 3))			
			$sqljoin = " INNER JOIN ".$join[0]." ON `".$this->table."`.`".$join[1]."` = `".$join[0]."`.`".$join[2]."`";
		else
			$sqljoin = "";
		
		/* Query */
		$query = "SELECT ".$fields." FROM ".$this->table.$sqlwhere.$sqljoin;
		$sth = $this->pdo->prepare($query);
		$sth->execute($stockedvalue);
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Update line(s) from the database
	 *
	 * Transform the array to string and send the SQL query
     * 
     * @param array $data - Field(s) to replace - array("field1"=>"newvalue1","field2"=>"newvalue2")
	 * @param array $where - WHERE condition(s), return all if ["all"] - array("field1"=>"value1","field2"=>"value2")
     */
	public function update(array $data, array $where)
	{
		/* Check the data fields */
		$this->checkFields(array_keys($data));

		/* Prepare the value */
		$i = 0;
		$stockedvalue = array();
		foreach($data as $key=>$value)
		{
			$stockedvalue[":param".$i] = $value;
			$data[$key] = ":param".$i;
			$i++;
		}
		
		/* Assemble the data to string format */
		$setdata = [];
		foreach ($data as $onefield => $onevalue)
			$setdata[] = "`".$onefield."` = ".$onevalue;
		$setdata = implode(",",$setdata);
		
		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);
		$stockedvalue = array_merge($stockedvalue,$sqlwhere["values"]);
		$sqlwhere = $sqlwhere["sql"];

		/* Query */
		$query = "UPDATE ".$this->table." SET ".$setdata.$sqlwhere;
		$sth = $this->pdo->prepare($query);
		$sth->execute($stockedvalue);
	}
	
    /**
     * Delete line(s) from the database
	 *
	 * Transform the array to string and send the SQL query
     * 
	 * @param array $where - WHERE condition(s), return all if ["all"] - array("field1"=>"value1","field2"=>"value2")
     */
	public function delete(array $where)
	{
		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);
		$stockedvalue = $sqlwhere["values"];
		$sqlwhere = $sqlwhere["sql"];

		/* Query */
		$query = "DELETE FROM `".$this->table."`".$sqlwhere;
		$sth = $this->pdo->prepare($query);
		$sth->execute($stockedvalue);
	}
	
    /**
     * Check if a line exist inside the table
	 *
	 * Send a SELECT query with the condition(s) given and check if at least one row is returned
     * 
     * @param array|int $ids - Id to check, int or  WHERE condition(s) - int OR array("field1"=>"value1","field2"=>"value2")
     * 
     * @return bool - True is exist, False if not
     */
	public function exists($ids)
	{
		/* Assemble the Where condition */
		if(is_string($ids))
		{
			$sqlwhere = " WHERE `id` = :param";
			$stockedvalue = ["param"=>$ids];
		}
		else if (is_array($ids))
		{
			$sqlwhere = $this->arrayToStringWhere($ids);
			$stockedvalue = $sqlwhere["values"];
			$sqlwhere = $sqlwhere["sql"];
		}
		else
			SparkleLogger::error(2000, "Argument 1 passed to exists() must be an string or an array", "",buildContext(get_defined_vars()));

		/* Query */
		$query = "SELECT `id` FROM `".$this->table."`".$sqlwhere." LIMIT 1";
		$sth = $this->pdo->prepare($query);
		$sth->execute($stockedvalue);
		
		if($sth->fetchColumn() > 0)
			return true;
		else
			return false;
	}
	
    /**
     * Prepared special request dependent of the table, see descriptions below
     * 
     * @param mixed $args
     * 
     * @return mixed
     */
	public function specialFunction($id, array $args = NULL)
	{
		if(!is_int($id))
			SparkleLogger::error(2000, "Argument 1 passed to specialFunction() of ".$this->table." must be an integer", "",buildContext(get_defined_vars()));
		if(!is_array($args))
			SparkleLogger::error(2000, "Argument 2 passed to specialFunction() of ".$this->table." must be an array", "",buildContext(get_defined_vars()));

		switch($id)
		{
			/**
			 * id=1 - Sessions special function
			 * 
			 * Clean the sessions older than $time
			 *
			 * @param int - Time 
			 */
			case 1:
			{
				if($this->table != "sessions")
					SparkleLogger::error(2020, "Can't clean sessions with the table ".$this->table, "",buildContext(get_defined_vars()));
				if(!array_key_exists("time",$args) || !is_int($args["time"]))
					SparkleLogger::error(2010, "Argument 2 passed to specialFunction() of ".$this->table." must be array('time'=>integer)", "",buildContext(get_defined_vars()));

				/* Query */
				$query = "DELETE FROM `sessions` WHERE `timestamp` < FROM_UNIXTIME(UNIX_TIMESTAMP() - :param);";
				$sth = $this->pdo->prepare($query);
				$sth->execute(["param"=>$args["time"]]);
				break;
			}
			default:
				SparkleLogger::error(2010, "No specialFunction with id ".$id, "",buildContext(get_defined_vars()));
		}
	}
	
    /**
     * Put the Where array to string format
     * 
	 * Construct the string to insert as WHERE inside the SQL query
	 *
     * @param array $where 
     * @param string $param - Secure Variable
     * 
     * @return array - "sql"=>WHERE query, "values"=>Security values
     */
	private function arrayToStringWhere(array $where,$param = "whereparam")
	{
		if((count($where) == 0) || (isset($where[0]) && $where[0] == "all"))
			return(["sql"=>"","values"=>array()]);
		else
		{
			if(!ctype_alpha($param))
				SparkleLogger::error(2100, "Argument 2 passed to arrayToStringWhere() must be an alphabetic string", "",buildContext(get_defined_vars()));
			
			/* Check the fields */
			$this->checkFields(array_keys($where));

			/* Prepare the value */
			$i = 0;
			$stockedvalue = array();
			foreach($where as $key=>$value)
			{
				$stockedvalue[":".$param.$i] = $value;
				$where[$key] = ":".$param.$i;
				$i++;
			}			

			$sqlwhere = [];
			foreach ($where as $onefield => $onevalue)
			{
				$sqlwhere[] = "`".$onefield."` = ".$onevalue;
			}
			return(["sql"=>" WHERE ".implode(" AND ",$sqlwhere),"values"=>$stockedvalue]);
		}
	}
	
    /**
     * Security fonction
	 *
	 * Throw an Exception if a field is non alphabetic
     * 
     * @param array $fields - Fields to check
     */
	private function checkFields(array $fields)
	{
		foreach($fields as $value)
		{
			if(!ctype_alpha($value))
				SparkleLogger::error(2010, "Field with illegal character detected (non alphabetic)", "",buildContext(get_defined_vars()));
		}
	}
	
	/* TODO */
	private function checkId($id)
	{
		if(false)
			SparkleLogger::error(2010, "Bad id format", "",buildContext(get_defined_vars()));
	}
}
?>