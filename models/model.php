<?php
class Table
{
	protected $host;
	protected $port;
	protected $bdd;
	protected $user;
	protected $password;
	protected $pdo;
	
	protected $table;

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
	
	/**
	* Get line(s) from the database
	* #in 1 : where condition(s), array("field1"=>"value1","field2"=>"value2")
	*	can be NULL (get all lines)
	* #in 2 : field(s) to get, array("field1","field2")
	*	can be NULL (get all fields)
	* #out : lines selected, array( [0]=> array("field1"=>"value1","field2"=>"value2") , [1]=> array("field1"=>"value3","field2"=>"value4"))
	**/
	public function get(array $where = NULL, array $fields = NULL)
	{
		/* Array to string, if $fields is null : Return all fields */
		if(empty($fields))
			$fields = "*";
		else
			$fields = "`".implode("`,`",$fields)."`";
		
		/* Assemble the Where to string format */
		if(!empty($where))
		{
			$sqlwhere = [];
			foreach ($where as $onefield => $onevalue)
				$sqlwhere[] = "`".$onefield."` = '".$onevalue."'";
			$sqlwhere = " WHERE ".implode(" AND ",$sqlwhere);
		}
		else
			$sqlwhere = "";
		
		/* Query */
		$query = "SELECT ".$fields." FROM ".$this->table.$sqlwhere;
		$result = $this->pdo->query($query);
		
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	* Update line(s) from the database
	* #in 1 : field(s) to replace, array("field1"=>"newvalue1","field2"=>"newvalue2")
	* #in 2 : where condition(s), array("field1"=>"value1","field2"=>"value2")
	*	can be NULL  (update all lines)
	* #out : true
	**/
	public function update(array $data, array $where = NULL)
	{
		/* Assemble the data to string format */
		$setdata = [];
		foreach ($data as $onefield => $onevalue)
			$setdata[] = "`".$onefield."` = '".$onevalue."'";
		$setdata = implode(",",$setdata);
		
		/* Assemble the Where to string format */
		if(!empty($where))
		{
			$sqlwhere = [];
			foreach ($where as $onefield => $onevalue)
				$sqlwhere[] = "`".$onefield."` = '".$onevalue."'";
			$sqlwhere = " WHERE ".implode(" AND ",$sqlwhere);
		}
		else
			$sqlwhere = "";

		/* Query */
		$query = "UPDATE ".$this->table." SET ".$setdata.$sqlwhere;
		$result = $this->pdo->query($query);
		
		return true;
	}
	
	public function delete(array $where)
	{
		
	}
}
?>