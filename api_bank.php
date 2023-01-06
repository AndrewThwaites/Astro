<?php
define ("SLASH", "/");
include_once("config" .SLASH. "define.php");
include_once("config".SLASH. "config.php");
include_once("core".SLASH. "db.php");

require_once("core". SLASH. "state.php");
require_once("core". SLASH. "utils.php");
require_once("core". SLASH. "keys.php");
//require_once("core". SLASH. "db_helper.php");

class APP
{
	private $db = false;
	private $command = false;
	private $post = array();
	
	 function __construct($post_data) 
	 {
		global $db;
		
		$this->set_post ($post_data) ? $post_data : $_POST;
		
        $db = new db(LOCAL_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }
	
	
	private function set_post($post)
	{
		if ($post)
		{
			foreach($post as $name => $value)
			{
				$this->post[$name] = $value;	
			}
		}
	}
	
	private function get_post($var_name)
	{
		return (!empty($this->post[$var_name])) ?  $this->post[$var_name] : ''; 
	}
	
	/**
	 *
	 */
	private function required_exist($data, $fields )
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
	
	private function access_right($access_feature)
	{
		return STATE::access_right($access_feature);
	}
	
	## Account ##########################################################################################################################
	
	private function __VERSION()
	{
		echo 'VERSION 1.0';
		
		if (!$this->required_exist($this->post, array("test_string") ))
		{
			$this->dispatch(array("OUTCOME"=> 1,  
								  "ACTION" => str_replace("__", "", __FUNCTION__),
								  "TEST" => $this->get_post("test_string")."  ".date("Y-m-d H:i:s"),
								  "COMMENT" => "Account successfully logged off" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "VERSION", "COMMENT" => "required fields missing" ));
	}
	
	private function __ACCOUNT_LOGOFF()
	{
		global $db;
		
		if (!$this->required_exist($this->post, array("login_id", "token") ))
		{
			$sql = "DELETE FROM core_token WHERE login_id = ? AND token = ?";
			$db->query($sql, $this->get_post("login_id"), $this->get_post("token"));
			
			$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "ACCOUNT_LOGOFF", "COMMENT" => "Account successfully logged off" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "ACCOUNT_LOGOFF", "COMMENT" => "required fields missing" ));
	}
	
	
	/**
	 *	ACCOUNT_LOGIN
	 */
	private function __ACCOUNT_LOGIN()
	{
		global $db;

		// if required_exist returns an empty array, this means no missing field names where found
		if (!$this->required_exist($this->post, array('email_address', 'password') ))
		{
			$sql = "SELECT id,status,tenant_id FROM ".TBL_PERSON." WHERE email_address = ? AND password = ?";
			$result = $db->query($sql, $this->get_post('email_address'), $this->get_post('password') )->fetchArray();

			if ($result)
			{
				// TODO : check if there have been mulitple failed login attempts recently

				// create a token of 32 char length
				$token = UTILS::Create_Token(32);
				
				$sql = 'INSERT INTO '.TBL_TOKEN.' (tenant_id,login_id,created,token) VALUES (?,?,?,?)';
				$insert = $db->query($sql, 
									 $result['tenant_id'], 
									 $result['id'],
									 date("Y-m-d h:i:s"), 
									 $token );
							
				// return {user_name, token}
				$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "ACCOUNT_LOGIN", "COMMENT" => "Account successfully logged ON", "login_id" => $result['id'], "token" => $token ));
			}
			else
			{
				// TODO : add log of failed login attempt
			}
		}
		
		// log failed login attempt
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "ACCOUNT_LOGIN", "COMMENT" => "account logon failed" ));
	}
	

	/**
	 *
	 */
	private function __ACCOUNT_FORGOT_PASSWORD()
	{
		global $db;
		
		$sql = "SELECT * FROM ".TBL_PERSON." WHERE status = ? AND email_address = ?";
		$person = $db->query($sql , array(1, $this->get_post('email_address')))->fetch();
		
		// Now create a token
		$password_reset_token = UTILS::create_token(32);
		
		// Insert into db
		// id, created, status, password_reset_token, person_id, tenant_id, 
		$sql = "INSERT INTO password_reset (id, password_reset_token, person_id, tenant_id) VALUES (?,?,?,?,?)";
		$params = array(NULL, $password_reset_token, $person['id'], $person['tenant_id'] );
		$db->query($sql, $params);
			
		// Retreive  password reset email template
		$sql = "SELECT * FROM view_template WHERE tenant_id = ? AND id = ? AND language = ?";
		$params = array(-1, TEMPLATE_FORGOT_PASSWORD_EMAIL, "EN");
		$template_row = $db->query($sql , $params)->fetchone();
		
		// populate template
		$template = str_replace("{FIRST_NAME}", $person['first_name'], $template);
		$template = str_replace("{LAST_NAME}", $person['last_name'], $template);
		$template = str_replace("{RESET_PASSWORD_TOKEN}", password_reset_token, $template);
		
		// send email
		mail($person['email_address'], "Password Reset", $template);
	}
	
	

	/**
	 *
	 */
	private function __ACCOUNT_FORGOT_PASSWORD_RESPONSE()
	{
		global $db;
		
		$password_reset_token = $this->get_post['password_reset_token'];
		$login_id = $this->get_post['user_id'];
		
		if ($password_reset_token)
		{
			$sql = "SELECT * password_reset WHERE login_id  = ? AND password_reset_token = ? AND status = ?";
			$params = array($login_id, $password_reset_token, 1);
			$result = $db->query($sql , $params )->fetch();
			if($result)
			{
				$person_id = $result['person_id'];
				$tenant_id = STATE::tenant_id;
			
				// change password
				$sql = "UPDATE person SET password = ? WHERE person_id = ? AND tenant_id = ?";
				$params = array($this->get_post('new_password'), $person_id, STATE::tenant_id);
				$db->query($sql , $params);
			
				// make sure reset token cannot be used again
				$sql = "UPDATE password_reset SET status = ? WHERE tenant_id = ? AND person_id = ? AND password_reset_token = ?";
				$param = array(0, STATE::tenant_id, $person_id, $password_reset_token);
				
				$this->dispatch();
			}
		}
		
		$this->dispatch_error();
	}
	
	
	/**
	 *	ACCOUNT_CREATE
	 */
	private function __ACCOUNT_CREATE()
	{
		global $db;
		echo $this->access_right(FEATURE_ACCOUNT_CREATE);
		if (1) // ($this->access_right(FEATURE_ACCOUNT_CREATE) == ACCESS_ADMIN )
		{
			$data["OUTCOME"] = 1;
			$data["ACTION"] ="ACCOUNT_CREATE";
			$data["titles"] = KEYS::get_key(KEY_TYPE_TITLES);
			$data["gender"] = KEYS::get_key(KEY_TYPE_GENDER);
			$data["marital"] = KEYS::get_key(KEY_TYPE_MARITAL_STATUS);
			$data["countries"] = KEYS::get_key(KEY_TYPE_COUNTRIES);
			
			$this->dispatch($data);
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_CREATE", "COMMENTS" => "Failed : insufficent access privilages"));
	}
	
	
	/**
	 *	ACCOUNT_CREATE_SAVE
	 */
	private function __ACCOUNT_CREATE_SAVE()
	{
		global $db;
		
		if (1)	//($this->access_right(FEATURE_ACCOUNT_CREATE_SAVE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist($this->post, array('first_name','last_name','title','marital_status','email_address', 'mobile_number') ))
			{
				$set["titles"] = 	KEYS::get_key(KEY_TYPE_TITLES);
				$set["gender"] = 	KEYS::get_key(KEY_TYPE_GENDER);
				$set["marital"] = 	KEYS::get_key(KEY_TYPE_MARITAL_STATUS);
				$set["countries"] = KEYS::get_key(KEY_TYPE_COUNTRIES);
				
				$allowed_fields = array('first_name', 'middle_name', 'last_name', 'title', 
										'marital_status', 'address', 'mobile_number', 'email_address', 
										'venue_id', 'role_type_id', 'password', 'default_venue' );
				$data = array();
				foreach($allowed_fields as $value)
				{
					$data[$value] = $this->get_post($value);
				}
				

				$sql = 'INSERT INTO '.TBL_PERSON.' (id, created_by, created, status, tenant_id, first_name, '.
					   'middle_name, last_name, title, marital_status, address_id, mobile_number, '.
					   'email_address, password, role_type_id, post_type_id, default_venue)  VALUES (?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?) ';
					  
				$params = array(NULL, 1, date("Y-m-d h:i:s"),
									 1, 
									 STATE::get_state("tenant_id"),
									 
										$data['first_name'],
										$data['middle_name'], 
										$data['last_name'], 
										$data['title'], 
										
										$data['marital_status'], 
									 $data['address'],
										$data['mobile_number'],
										$data['email_address'], 
									 $data['password'], 
									 $data['role_type_id'], 
									 $data['role_type_id'],
									 $data['default_venue']);
				
				$insert = $db->query($sql, $params);

				if ($insert->affectedRows())
				{
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "ACCOUNT_CREATE_SAVE"  ));
				}
			}		
		}
		
		$this->dispatch( array("OUTCOME"  => 0, 
							   "ACTION"	  => "ACCOUNT_CREATE_SAVE" , 
							   "COMMENT"  => "Account could not be created" ));
	}
	
	
	/**
	 *
	 */
	private function __ACCOUNT_UPDATE()
	{
		global $db;
		
		if (1)//($this->access_right(FEATURE_ACCOUNT_LIST) == ACCESS_ADMIN )
		{
			if (!$this->required_exist($this->post, array('account_id','first_name','last_name','title','marital_status','email_address', 'mobile_number') ))
			{
				$set["titles"] = 	KEYS::get_key(KEY_TYPE_TITLES);
				$set["gender"] = 	KEYS::get_key(KEY_TYPE_GENDER);
				$set["marital"] = 	KEYS::get_key(KEY_TYPE_MARITAL_STATUS);
				$set["countries"] = KEYS::get_key(KEY_TYPE_COUNTRIES);
				
				$allowed_fields = array('first_name', 'middle_name', 'last_name', 'title', 
										'marital_status', 'address', 'mobile_number', 'email_address', 
										'venue_id', 'role_type_id' );
					
				$set = array();
				$params = array();
				foreach($allowed_fields as $allowed_field)
				{
					$value = $this->get_post($allowed_field);
					if ($value)
					{
						$set[] = $allowed_field."= ?";
						$params[] = $value;
					}
				}
				$params[] = $this->get_post("account_id");
				$sql = "UPDATE person SET ".join(", " , $set). " WHERE id = ?";
				
				$db->query($sql, $params);
				$this->dispatch( array("OUTCOME" => 1, 
									   "ACTION" => "ACCOUNT_UPDATE", 
									  "COMMENT" => "account updated successfully"));
			}
		}
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_UPDATE", "COMMENT" => "Account NOT updated"));
	}	
	
	
	/**
	 * account list
	 */
	private function __ACCOUNT_LIST()
	{
		global $db;
		if (1) //($this->access_right(FEATURE_ACCOUNT_LIST) == ACCESS_ADMIN )
		{
			$name = $this->get_post('name');
			$location = $this->get_post('location`');
			$mobile = $this->get_post('mobile_number');
			$address = $this->get_post('address');
			$email_address = $this->get_post('email_address');
			$venue_id = $this->get_post('venue_id');
			
			$offset = $this->get_post('offset');
			$limit = $this->get_post('limit');
			
			$sql = "SELECT * FROM person WHERE tenant_id = ? ";
			$params[] = STATE::get_state("tenant_id");
			
			if ($venue_id)
			{
				$sql.=" AND venue_id = ?";
				$params[] = $venue_id;
			}
			
			if ($location)
			{
				$sql.=" AND default_venue = ?";
				$params[] = $location;
			}
			
			if ($name)
			{
				$sql.= " first_name LIKE ? OR last_name LIKE ? ";
				$params[] = "%".$name."%";
				$params[] = "%".$name."%";
			}
			
			if ($offset)
			{
				$sql.=" OFFSET ?";
				$params[] = $offset;
			}
			
			if ($limit)
			{
				$sql.=" LIMIT ?";
				$params[] = $limit;
			}
			
			$result = $db->query($sql, $params)->fetchAll();
			
			$this->dispatch(array("OUTCOME" => 1, "ACTION" => "ACCOUNT_LIST", "ACCOUNT_LIST" => $result, "COMMENT" => "Success"));
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_LIST", "ACCOUNT_LIST" => array(), "COMMENT" => "Access not allowed"));
	}

	
	/**
	 * account_delete
	 */
	private function __ACCOUNT_DELETE()
	{
		global $db;
		if ($this->access_right(FEATURE_ACCOUNT_DELETE) == ACCESS_ADMIN )
		{
			$account_id = intval($this->get_post['account_id']);
			$sql = "UPDATE person SET status = ? WHERE id = ? AND tenant_id = ?";
			$db->query($sql, 0, $account_id, STATE::Get_state("TENANT_ID"));
			$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_DELETE", "COMMENT" => "Access not allowed"));
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_DELETE", "COMMENT" => "Access not allowed"));
	}	
	
	
	/**
	 *	account security get
	 */
	private function  __ACCOUNT_SECURITY_GET()
	{
		global $db;
		if ($this->access_right(ACCOUNT_SECURITY_GET) == ACCESS_ADMIN )
		{
			$account_id = intval($this->get_post['account_id']);
			$sql = "UPDATE person SET status = ? WHERE id = ? AND tenant_id = ?";
			$db->query($sql, 0, $account_id, STATE::Get_state("TENANT_ID"));
			$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_SECURITY_GET", "COMMENT" => "Access not allowed"));
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_SECURITY_GET", "COMMENT" => "Access not allowed"));
	}

	
	private function __ACCOUNT_SECURITY_COPY()
	{
		global $db;
		if ( $this->access_right(FEATURE_ACCOUNT_DELETE) == ACCESS_ADMIN )
		{
			//$account_id = intval($this->get_post['role_type_id'], $this->get_post['role_type_name'] );
			if (!$this->required_exist( $this->post, array("venue_name","venue_address","venue_email_address","venue_manager_id")))
			{
				// does role type id exist? and belong to this tenant_id?
				$sql = "SELECT count(*) as 'count' FROM role_type_id WHERE tenant_id = ? AND role_type_id = ?";
				$params = array(STATE::Get_state("TENANT_ID"), $this->get_post['role_type_id'] );
				$result = $db->query($sql, $params)->fetchArray();
				if (($result) && ($result['count']==1))
				{
				
				}
			
				$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_SECURITY_COPY", "COMMENT" => "Access not allowed"));
			}
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_SECURITY_COPY", "COMMENT" => "Access not allowed"));
	}
	
	
	/**
	 *	account security update
	 */
	private function __ACCOUNT_SECURITY_UPDATE()
	{
		if ( $this->access_right(ACCOUNT_SECURITY_UPDATE) == ACCESS_ADMIN )
		{
			$account_id = intval($this->get_post['account_id']);
			$sql = "UPDATE person SET status = ? WHERE id = ? AND tenant_id = ?";
			$db->query($sql, 0, $account_id, STATE::Get_state("TENANT_ID"));
			$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_DELETE", "COMMENT" => "Access not allowed"));
		}
		
		$this->dispatch(array("OUTCOME" => 0, "ACTION" => "ACCOUNT_SECURITY_UPDATE", "COMMENT" => "Access not allowed"));	
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

*/
