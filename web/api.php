<?php
require_once("inc/config.php");
require_once("inc/renderer.php");
require_once("inc/DB.php");
define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASS","");
define("DB_DB","test__sasquatch");
$db = DB::getInstance();

$request = $_GET['request'];
unset($_GET['request']);
$_REQUEST = escape($_REQUEST);

switch ($request) {
	case 'getAllCrons':
		$required = array();
		$optional = array();
		check($required, $optional);

		Renderer::render( getAllCrons() );
		break;
	case 'getLogsForCron':
		$required = array("cronID");
		$optional = array("runID","lastRowID");
		check($required, $optional);

		Renderer::render( getLogsForCron($_REQUEST['cronID'],$_REQUEST['runID'],$_REQUEST['lastRowID']) );
		break;

	default:
		Renderer::ThrowError(StatusCodes::$INVALID_REQUEST);
		break;
}


function getAllCrons()
{
	$db = DB::getInstance();
	$cronList = array();
	$result = $db->query("SELECT *, 
				IF(isRunning = 0,
					0,
					(SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(startDateTime) FROM cron_runs WHERE cron_runs.cronID = crons.ID ORDER BY startDateTime DESC LIMIT 1)
				) as runningTime
			FROM crons");
	while($item = mysql_fetch_assoc($result))
	{
		$item['runningTime'] = (int)$item['runningTime'];
		$item['lastDuration'] = (int)$item['lastDuration'];
		$cronList[] = $item;
	}
	return $cronList;
}


function getLogsForCron($cronID, $runID = "", $lastRowID = "")
{
	$db = DB::getInstance();
	$logger = array();



	if($runID == "")
	{
		$result = $db->query("SELECT * FROM cron_runs WHERE cronID = '".$cronID."' ORDER BY startDateTime DESC LIMIT 1");
		$item = mysql_fetch_assoc($result);
		$runID = $item['runID'];
	}

	$offset = "";
	if($lastRowID != "")
		$offset = "LIMIT ".$lastRowID.",18446744073709551615"; // the big number is for the freaking offset to work ...

	$result = $db->query("SELECT * FROM crons WHERE ID = ".$cronID);
	$cronData = mysql_fetch_assoc($result);

	$result = $db->query("SELECT * FROM logger WHERE runID = '".$runID."' ORDER BY dateTimeAdded ".$offset);
	$lastRowID = mysql_num_rows($result);
	while($item = mysql_fetch_assoc($result))
	{
		$logger[] = $item;
		// $tmp = explode("\n",$item['output']);
		// foreach($tmp as $line)
		// {
		// 	$tempItem = $item;
		// 	unset($tempItem['runID']);
		// 	$tempItem['output'] = $line;
		// 	$logger[] = $tempItem;
		// }
	}

	return array(
		"logs" => $logger,
		"isRunning" => $cronData['isRunning'],
		"lastRowID" => $lastRowID
	);
}




function check($required, $optional)
{
	foreach($required as $param)
	{
		if( !isset($_REQUEST[$param]) )
			Renderer::ThrowError(StatusCodes::$INVALID_DATA);
	}
	foreach($optional as $param)
	{
		if( !isset($_REQUEST[$param]) )
			$_REQUEST[$param] = "";
	}
}

function escape($array){
	foreach($array as $key=>$value) {
      if(is_array($value)) { escape($value); }
      else { $array[$key] = mysql_real_escape_string($value); }
   }
   return $array;
}

?>
