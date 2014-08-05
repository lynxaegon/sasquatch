<?php
class Renderer
{
	private static $status = 200;
	private static $mode = "json";
	
	public static function ThrowError($statusCode, $data = array())
	{
		$config = Config::getInstance();
		$execution_time = microtime(true) - $config->execution_start;

		header("Content-type: application/json; charset=utf-8");
		echo json_encode(array(
			"status" => $statusCode['ID'],
			"error" => $statusCode['message'],
			"error_data" => $data,
			"execution_time" => (float)number_format($execution_time,6)
		));
		exit();
	}

	public static function setMode($mode)
	{
		self::$mode = $mode;
	}

	public static function setStatus($status)
	{
		if(!is_object($status))
			self::$status = $status;
		else
			self::$status = $status['ID'];
	}

	public static function render($data)
	{
		$config = Config::getInstance();
		$execution_time = microtime(true) - $config->execution_start;

		if(self::$mode == "json")
		{
			header("Content-type: application/json; charset=utf-8");	
			if($data === false)
			{
				echo json_encode( 
					array( 
						"status" => StatusCodes::$INTERNAL_ERROR,
						"execution_time" => $execution_time,
					) 
				);
				return false;
			}
			$tmp = array(
				"status" => self::$status
			);
			$tmp['data'] = $data;
			$tmp['execution_time'] = $execution_time;
			echo json_encode( $tmp );
		}
		else
		{
			echo $data;
		}
	}
}

class StatusCodes
{
	static $NOT_MODIFIED = array(
		"ID" => 304,
		"message" => "Not modified"
	);

	static $INVALID_REQUEST = array(
		"ID" => 305,
		"message" => "Invalid Request"
	);

	static $INTERNAL_ERROR = array(
		"ID" => 306,
		"message" => "Internal Request Error"
	);
	
	static $DBLINK_ERROR = array(
		"ID" => 401,
		"message" => "Cannot establish DB connection"
	);

	static $DBQUERY_ERROR = array(
		"ID" => 402,
		"message" => "Database query error"
	);

	static $REQUEST_FAILED = array(
		"ID" => 600,
		"message" => "Request Failed"
	);	

	static $INVALID_DATA = array(
		"ID" => 601,
		"message" => "Invalid data sent to server"
	);	
}