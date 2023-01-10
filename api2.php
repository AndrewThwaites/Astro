<?php
define ("SLASH", "/");
include_once("config/define.php");
include_once("config/onfig.php");
include_once("core".SLASH. "db.php");
require_once("core". SLASH. "state.php");
require_once("core". SLASH. "utils.php");
require_once("core". SLASH. "keys.php");
require_once("core". SLASH. "db_helper.php");
#require_once("core". SLASH. "tracer.php");
#require_once(__CORE__DIR__ . "validation.php");


class APP
{
	private $db;
	private $command;
	private $post = array();
	
	 public function __construct($post_data) 
	 {
		global $db;
		
		$this->set_post ($post_data) ? $post_data : $_POST;
		
        $db = new db(LOCAL_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }
	
	
	private function setPost($post)
	{
		if ($post)
		{
			foreach($post as $name => $value)
			{
				$this->post[$name] = $value;	
			}
		}
	}
	
	private function getPost($var_name)
	{
		return (!empty($this->post[$var_name])) ?  $this->post[$var_name] : ''; 
	}
	
	/**
	 *
	 */
	private function requiredExist($data, $fields )
	{
		$result = array();
		foreach($fields as $field)
		{
			if (empty($data[$field])) $result[] = $field;
		}
		
		return $result;
	}
	
	private function dispatch($data)
	{
		echo json_encode($data);
		die();
	}
	
	private function accessRight($access_feature)
	{
		return STATE::accessRight($access_feature);
	}
	

	private function __VERSION()
	{
		echo 'VERSION 1.0';
		
		if (!$this->requiredExist($this->post, array("test_string") ))
		{
			$this->dispatch(array("OUTCOME"=> 1,  
								  "ACTION" => str_replace("__", "", __FUNCTION__),
								  "TEST" => $this->get_post("test_string")."  ".date("Y-m-d H:i:s"),
								  "COMMENT" => "Account successfully logged off" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "VERSION", "COMMENT" => "required fields missing" ));
	}
	
	
	
	/**
	 * exec
	 *
	 */
	public function exec()
	{
		$action = (!empty($this->post['action'])) ? $this->post['action'] : '';
			
		if (in_array($action, array("ACCOUNT_LOGIN", "ACCOUNT_FORGOT_PASSWORD", "ACCOUNT_FORGOT_PASSWORD_RESPONSE", "ACCOUNT_LOGOFF" )  ))
		{
			echo 'Non token action...';
			//	invoke method	
			$result = $this->{'__'.$action}();
			$this->dispatch($result);
		}
		
		$login_id = $this->getPost('login_id');
		$token = $this->getPost('token');
		
		if (STATE::validateToken( $token, $login_id))
		{
			STATE::loadConfiguration();
			
			if (!method_exists($this, '__'.$action ))
			{
				$this->dispatch();
			}
			
			//	invoke method	
			$result = $this->{'__'.$action}();
		} else {	
			echo '... invalid token';
		}
		
	}
}


$feature_id = '';
$action_id = '';

if (isset($_GET['feature_id']))
{
	$feature_id = $_GET['feature_id'];
}

if (isset($_GET['action_id']))
{
	$action_id = $_GET['action_id'];
}


//$post_data = UTILS::test_plan_action( $feature_id , $action_id );

/* columns :
	0 A) field_name, 
	1 B) field caption, 
	2 C) Type,
	3 D) Mandatory,
	4 E) formtype
	5 E) validation, 
	6 F) default value, 
	7 G) position, 
	8 H) linked_to, 
	9 I) report_col_caption, 
	10 J) report_col_width, 
	11 K) report_postion
*/							

/*
$post_data = array("action" => "META_TABLE_DEFINE", 
				   "login_id" => "1", 
				   "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD",
				   "table_definition" =>  "public_holiday".PHP_EOL.
										  "-1".PHP_EOL.
										  "field_name,field caption,type,mandatory,formtype,validation,default value, postition, linked_to, csv col title, csv col width, csv col position".PHP_EOL.
										  "id,,1,TEST_ONLY_FORMAT,,1,HIDDEN,,HOLIDAY NAME,25,1".PHP_EOL.
										  "holiday_name,Holiday Name,1,TEXT,TEST_ONLY_FORMAT,,1,,HOLIDAY NAME,25,1".PHP_EOL.
										  "holiday_date,Holiday Date,1,TEXT,DMY_FORMAT,,1,,HOLIDAY DATE,10,2".PHP_EOL.
										  "location_id,Location,,1,SELECT_SINGLE,,3,LOCATION,10,2".PHP_EOL.
										  "status,Status,1,SELECT_SINGLE,1,4,,".PHP_EOL.
										  "created,Created,INT,1,HIDDEN,-1,6,PERSON,,-1,,".PHP_EOL.
										  "created_by,Created By,INT,1,HIDDEN,-1,6,PERSON,,-1,,".PHP_EOL.
										  "tenant_id,Tenant Name,INT,1,HIDDEN,-1,6,PERSON,,-1,,".PHP_EOL);
*/

$post_data = array("action" => "VERSION", "test_string" => "test 123 abc", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD" );

$app = new APP($post_data);
$app->exec();



## ACCOUNT 
// UTILS::add_test_plan_action($_GET['feature_id'], $_GET['action_id'] );

## ACCOUNT

// Login 
// { login fail | login success }
//$post_data = array("action" => "ACCOUNT_LOGIN", "email_address" => "andrewthwaites@hotmail.com", "password" => "Burgman400!");

// Log off
//$post_data = array("action" => "ACCOUNT_LOGOFF", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD");

// account create
//$post_data = array("action" => "ACCOUNT_CREATE", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD");

// account create save
/*$post_data = array("action" => "ACCOUNT_CREATE_SAVE", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD",
				   'first_name' => 'James', 
				   'middle_name' => 'T', 
				   'last_name' => 'Kirk', 
				   'title' => 1, 
				   'marital_status' => '1', 
				   'address' => 'galaxy way', 
				   'mobile_number' => '07853070021', 
				   'email_address' => 'james.t.kirk@outlook.com', 
				   'venue_id' => 32, 
				   'role_type_id' => -1,
				   'password' => 'password123', 'default_venue' => 'kendal');*/

// account list
//$post_data = array("action" => "ACCOUNT_LIST", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD" );


// account update
/*
$post_data = array("action" => "ACCOUNT_UPDATE", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD",
				   "account_id" => 35,
				   "first_name" => 'Peter',
				   "last_name" => 'Pan',
				   "title" =>2,
				   "marital_status" => 2,
				   "email_address" => 'peterpan@outlook.com', 
				   "mobile_number" => '01539728745');
*/

// account reset password


## LOCATIONS

// Location add
/*
$post_data = array("action" => "LOCATION_ADD",	
				   "login_id"=> "1", 
				   "token" => "B3ZNTJ03S9NRQSHJUU8UNWZJ6XZWD1RN", 
				   "venue_name"=> "Kendal", 
				   "venue_address" => "6 Hillswood avenue, vicarage park, Kendal, Cumbria, LA9 5BT" ,
				   "venue_email_address" => "kendal@company.co.uk" ,
				   "venue_manager_id" => 1);
*/

// Location list
/*
$post_data = array("action" => "LOCATION_LIST",	
				   "login_id"=> "1", 
				   "token" => "B3ZNTJ03S9NRQSHJUU8UNWZJ6XZWD1RN");
*/

// location update
/*$post_data = array("action" => "LOCATION_UPDATE",	
				   "login_id"=> "1", 
				   "token" => "B3ZNTJ03S9NRQSHJUU8UNWZJ6XZWD1RN",
				   "venue_name" => "lancaster", "location_id" =>2);
*/	

// location delete
/*$post_data = array("action" => "LOCATION_DELETE",	
				   "login_id"=> "1", 
				   "token" => "B3ZNTJ03S9NRQSHJUU8UNWZJ6XZWD1RN",
				   "location_id" => 32);*/


##	chat

// chat hello
//$post_data = array("action" => "CHAT_HELLO", "login_id" => "1", "token" => "ABCDABCDABCDABCDABCDABCDABCDABCD" );	   
	   
	  
## collection

// Collection initalise


/*
core_tenant, "core_configuration_type",	"core_configuration_type_option", "core_dynamic_route",
*/

/*
$t_list = array(
				"core_token",
				"core_login_history",
				"view_language",
				"person",
				"venue",
				"venue_department",
				"address",
				"meta_table",
				"meta_table_field",
				"collection_record",
				"collection_record_field",
				"collection_venue_assigned",
				"nodes",
				"document_type",
				"document",
				"collection_type_field");
foreach($t_list as $t)
{
	UTILS::make_a_table($t , $tables[$t]);
}
*/
