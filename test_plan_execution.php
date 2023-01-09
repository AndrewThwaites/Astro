<?php
define ("SLASH", "/");
include_once("config" .SLASH. "define.php");
include_once("config".SLASH. "config.php");
include_once("core".SLASH. "db.php");
require_once("core". SLASH. "state.php");
require_once("core". SLASH. "utils.php");
require_once("core". SLASH. "keys.php");
//require_once(__CORE__DIR__ . "validation.php");

$db = new db(LOCAL_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$i = 0;
echo '<h1>'.__FILE__.'</h1>';

$sql = "SELECT * FROM tp_program WHERE status = ? AND program_code = ?";
$test_program = $db->query($sql, array(1, "meta_test_01"))->fetchArray();
//print_r($test_program);

if($test_program)
{
	$test_program_actions = explode(",", $test_program['program_actions']);
	//print_r($test_program_actions);
	
	foreach($test_program_actions as $test_program_action)
	{
		$start_time = time();
		$i++;
		
		// Pull test
		$sql = "SELECT * FROM tp_feature_test WHERE id = ? AND status = ? ";
		$test = $db->query($sql, array($test_program_action, 1))->fetchArray();
		if (!$test)
		{
			echo 'Test '.$test_program_action.' not found';
			die();
		}
		
		$postRequest = json_decode($test['input_data']);
		//print_r($postRequest);

		$tp_label =  $test['id'] .' - '. date("Y_m_d_h_i_s");
		
		// Create testplan place holder
		$sql = "INSERT INTO tp_entry (id, feature_id, tp_label, execution_time,	created,	input_data,	output_data) VALUES (?,?,?,?,?,?,?)";
		$params = array(1, $test['feature_id'], $tp_label, -1, date("Y-m-d h:i:s"), $test['input_data'], ''  );
		$db->query($sql, $params);
		$insert_id = $db->lastInsertID();
		
		
		$cURLConnection = curl_init("http://localhost/app/");
		$fp = fopen("app.php", "w");

		curl_setopt($cURLConnection, CURLOPT_FILE, $fp);
		curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
		curl_setopt($cURLConnection, CURLOPT_HEADER, 0);
		curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
		
		$output_data = curl_exec($cURLConnection);
		print_r($output_data);
		if(curl_error($cURLConnection)) 
		{
			fwrite($fp, curl_error($ch));
		}
		
		fclose($fp);

		// $apiResponse - available data from the API request
		// $jsonArrayResponse - json_decode($apiResponse);
		
		$end_time = time();
		$duration = $end_time - $start_time;
		
		// Update 
		$sql = "UPDATE tp_entry SET output_data = ? WHERE id = ?";
		$params = array($output_data, $insert_id);
		$db->query($sql, $params);
		
		// evaluate outcome
		// if failed and test plan action does not allow for continuation halt
		if (1)
		{
			exit();
		}
	}
}