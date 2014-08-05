<?php
class Config {
	private static $_instance = null;
	private $config = array();

	public static function getInstance()
	{
		if(self::$_instance == null)
			self::$_instance = new Config();
		return self::$_instance;
	}

	private function __construct()
	{
		$this->execution_start = microtime(true); 
	}

	public function __set($name,$value)
	{
		$this->config[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }
        return null;
	}
}