<?php
class Table
{

	protected $host = 'localhost';
	protected $port = '';
	protected $bdd = 'bronydays';
	protected $user = 'root';
	protected $password = '';
	protected $pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$bdd, $user, $password);
	
	protected $table = "";

	public function __construct($table)
	{
		$this->table = $table;
	}
	
	public function insert(array $line)
	{
		$tmp = separateArrayLine($line);
		$field = $tmp[0];
		$value = $tmp[1];

		$result = "INSERT INTO `".$this->table."` (".$field.") VALUES (".$value.")";

		return;
	}
	
	public function get(array $where, array $fields = 'all')
	{
		
	}
	
	public function update(array $where, array $line)
	{
		$tmp = separateArrayLine($line);
		$field = $tmp[0];
		$value = $tmp[1];
	
		$result = "UPDATE  `".$this->table."` SET (".$value.")";
		
		return;
	}
	
	public function delete(array $where)
	{
	
	}
	
	protected separateArrayLine(array $line)
	{
		foreach ($line as $onefield => $onevalue)
		{
			$field = $field.$onefield.",";
			$value = $value."'".$onevalue."',";
		}
		$field = substr($field,0,-1);
		$value = substr($field,0,-1);
		
		return [$field,$value];
	}
}
?>