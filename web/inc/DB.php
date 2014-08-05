<?php
class DB 
{
	private static $_instance = null;
	private $debug = false;
	private $query_string = "";

	public static function getInstance()
	{
		if(self::$_instance == null)
			self::$_instance = new DB();
		return self::$_instance;
	}

	protected function __construct()
	{
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASS);
		mysql_select_db(DB_DB,$link);
		mysql_query("SET NAMES 'utf8'");
	}
	public function query($query)
	{
		$this->query_string = $query;
		// All querys return valid data
		$result = mysql_query($query);
		if(!$result)
		{
			if($this->debug)
			{
				echo mysql_error()."\n";
				$this->showQuery();
			}
			error_log("mysql error: ".mysql_error());
			error_log("mysql error: ".$this->query_string);
			
		}

		return $result;
	}

	public function showQuery()
	{
		echo $this->query_string;
	}

	public function debug($enabled)
	{
		$this->debug = $enabled;
	}
}

?>
