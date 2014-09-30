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
	* #in : name of the table
	**/
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
	* #in : line to add, array("field1"=>"value1","field2"=>"value2")
	* #out : true
	**/
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

		return true;
	}
	
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
	* #in 1 : where condition(s), array("field1"=>"value1","field2"=>"value2")
	*	can be NULL (get all lines)
	* #in 2 : field(s) to get, array("field1","field2")
	*	if $where = array(0=>"all") : get all fields
	* #out : lines selected, array( [0]=> array("field1"=>"value1","field2"=>"value2") , [1]=> array("field1"=>"value3","field2"=>"value4"))
	**/
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
		echo $query;
		$result = $this->pdo->query($query);
		
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	* Update line(s) from the database
	* #in 1 : field(s) to replace, array("field1"=>"newvalue1","field2"=>"newvalue2")
	* #in 2 : where condition(s), array("field1"=>"value1","field2"=>"value2")
	*	if $where = array(0=>"all") : update all lines
	* #out : true
	**/
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
		
		return true;
	}
	
	/**
	* Delete line(s) from the database
	* #in 1 : where condition(s), array("field1"=>"value1","field2"=>"value2")
	*	if $where = array(0=>"all") : Delete all lines
	* #out : true
	**/
	public function delete(array $where)
	{
		/* Assemble the Where condition */
		$sqlwhere = $this->arrayToStringWhere($where);

		/* Query */
		$query = "DELETE FROM `".$this->table."`".$sqlwhere;
		$result = $this->pdo->query($query);

		return true;
	}
	
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
	
	public function cleanSessions($time)
	{
		/* Test argument */
		if(!is_int($time))
			throw new InvalidArgumentException("Argument 1 passed to cleanSessions() must be an integer, ".gettype($time)." given");
		else
		{
			/* Query */
			$query = "DELETE FROM `".$this->table."` WHERE `timestamp` < (UNIX_TIMESTAMP() - ".$time.")";
			$result = $this->pdo->query($query);
			
			return true;
		}
	}
	
	/* Put the Where array to string format */
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