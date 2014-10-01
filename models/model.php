<?php
class Table
{
	protected $host;
	protected $port;
	protected $bdd;
	protected $user;
	protected $password;
	protected $pdo;
	
	private $table;

	/**
	 * Constructor
	 * Look for the PDO or create it. Name the table variable.
	 *
	 * @param string $table - Name of the table in the database
	 */
	public function __construct($table)
	{
		/* Connexion to the database */
		$this->host = 'localhost';
		$this->port = '';
		$this->bdd = 'bronydays';
		$this->user = 'root';
		$this->password = 'AppleJack';
		$this->pdo = new PDO('mysql:host='.$this->host.';port='.$this->port.';dbname='.$this->bdd, $this->user, $this->password);
	
		/* Name of the table */
		$this->table = $table;
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
		/* Associative Array $line > 2 String */
		$field = [];
		$value = [];
		foreach ($line as $onefield => $onevalue)
		{
			$field[] = $onefield;
			$value[] = $onevalue;
		}
		$field = "`".implode("`,`",$field)."`";
		$value = "'".implode("','",$value)."'";

		/* Query */
		$query = "INSERT INTO `".$this->table."` (".$field.") VALUES (".$value.")";
		$result = $this->pdo->query($query);
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
			
		foreach($maxarray as $lines)
		{
			/* Create the fields SQL string */
			$fields = array();
			foreach($lines as $line)
				$fields = array_merge($fields,$line);
			$fields = array_keys($fields);
			$sqlfields = "(".implode(",",$fields).")";

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
				$sqlvalues[] = "(".implode(",",$sqlline).")";
			}
			$sqlvalues = implode(",",$sqlvalues);
			
			/* Query */
			$query = "INSERT INTO `".$this->table."` ".$sqlfields." VALUES ".$sqlvalues;
			$result = $this->pdo->query($query);
		}
		
		/* Insert null */
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
		/* Array to string, if $fields is null : Return all fields */
		if(empty($fields))
			$fields = "*";
		else
			$fields = "`".implode("`,`",$fields)."`";

		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);
			
		/* Assemble the Join to string format */
		if(!empty($join) && (count($join) == 3))			
			$sqljoin = " INNER JOIN ".$join[0]." ON `".$this->table."`.`".$join[1]."` = `".$join[0]."`.`".$join[2]."`";
		else
			$sqljoin = "";
		
		/* Query */
		$query = "SELECT ".$fields." FROM ".$this->table.$sqlwhere.$sqljoin;
		$result = $this->pdo->query($query);
		
		return $result->fetchAll(PDO::FETCH_ASSOC);
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
		/* Assemble the data to string format */
		$setdata = [];
		foreach ($data as $onefield => $onevalue)
			$setdata[] = "`".$onefield."` = '".$onevalue."'";
		$setdata = implode(",",$setdata);
		
		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);

		/* Query */
		$query = "UPDATE ".$this->table." SET ".$setdata.$sqlwhere;
		$result = $this->pdo->query($query);
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

		/* Query */
		$query = "DELETE FROM `".$this->table."`".$sqlwhere;
		$result = $this->pdo->query($query);
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
		if(is_int($ids))
			$sqlwhere = " WHERE `id` = ".$ids;
		else if (is_array($ids))
			$sqlwhere = $this->arrayToStringWhere($ids);
		else
			throw new InvalidArgumentException("Argument 1 passed to exists() must be an integer or an array, ".gettype($ids)." given");

		/* Query */
		$query = "SELECT `id` FROM `".$this->table."`".$sqlwhere." LIMIT 1";
		$result = $this->pdo->query($query);
		
		if($result->fetchColumn() > 0)
			return true;
		else
			return false;
	
		// /* Close code for existsBatch() */
		// /* Array to format String */
		// $sqlwhere = " WHERE `id` = ".implode("OR `id` = ",$ids);
	
		// /* Query */
		// $query = "SELECT `id` FROM `".$this->table."`".$sqlwhere;
		// $result = $this->pdo->query($query);
		// $result = $result->fetchAll(PDO::FETCH_ASSOC);
		
		// /* Create a comparable array */
		// $resultids = array();
		// foreach($result as $value)
			// $resultids[] = $value["id"];
		
		// /* Create the array returned */
		// $return = array();
		// foreach($ids as $value)
			// $return[$value] = true;
		
		// /* Search each id */
		// $result = array_diff($ids,$resultids);
		
		// /* If result is not null */
		// foreach($result as $value)
			// $return[$value] = false;

		// return $return;
	}
	
    /**
     * Prepared special request dependent of the table, see descriptions below
     * 
     * @param mixed $args
     * 
     * @return mixed
     */
	public function specialFunction($args)
	{
		switch($this->table)
		{
            /**
             * Sessions special function
			 * 
			 * Clean the sessions older than $time
             *
             * @param int - Time 
             */
			case "sessions":
			{
				if(!is_int($args))
					throw new InvalidArgumentException("Argument 1 passed to specialFunction() of ".$this->table." must be an integer, ".gettype($args)." given");
				else
				{
					/* Query */
					$query = "DELETE FROM `sessions` WHERE `timestamp` < (UNIX_TIMESTAMP() - ".$args.")";
					$result = $this->pdo->query($query);
				}
				break;
			}
			default:
			{
				/* TODO default exception */
			}	
		}
	}
	
    /**
     * Put the Where array to string format
     * 
	 * Construct the string to insert as WHERE inside the SQL query
	 *
     * @param array $where 
     * 
     * @return string
     */
	private function arrayToStringWhere(array $where)
	{
		if((count($where) == 0) || (isset($where[0]) && $where[0] == "all"))
			return "";
		else
		{
			$sqlwhere = [];
			foreach ($where as $onefield => $onevalue)
				$sqlwhere[] = "`".$onefield."` = '".$onevalue."'";
			return " WHERE ".implode(" AND ",$sqlwhere);
		}
	}
	
	
}
?>