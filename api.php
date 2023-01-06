<?php
define ("SLASH", "/");
include_once("config" .SLASH. "define.php");
include_once("config".SLASH. "config.php");
include_once("core".SLASH. "db.php");
require_once("core". SLASH. "state.php");
require_once("core". SLASH. "utils.php");
require_once("core". SLASH. "keys.php");
require_once("core". SLASH. "db_helper.php");
#require_once("core". SLASH. "tracer.php");
#require_once(__CORE__DIR__ . "validation.php");

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

	// ################################################################################################################
	
	private function __GET_HTML_TEMPLATE()
	{
		$html = TEMPLATE::get_template($this->get_post['page'], $this->get_post['language']);
		echo $html;
		die();
	}
	
	
	// Location ########################################################################################################
	
	/**
	 *	Location List
	 *
	 */
	private function __LOCATION_LIST()
	{
		global $db;
		if ( $this->access_right(FEATURE_LOCATION_LIST) == ACCESS_ADMIN )
		{		
			$sql = "SELECT * FROM ".TBL_VENUE." WHERE tenant_id = ? AND status = ?";
			$locations = $db->query($sql, STATE::get_state("tenant_id"), 1  )->fetchAll();
		 
			if ($locations)
			{
				$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "LOCATION_LIST", "COMMENT" => "Success", "locations" => $locations ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "LOCATION_ADD", "COMMENT" => "Failed to obtain list of locations" ));	
	}
	
	
	/**
	 *	Location Add
	 *
	 */
	private function __LOCATION_ADD()
	{
		global $db;
		if ($this->access_right(FEATURE_LOCATION_ADD) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("venue_name","venue_address","venue_email_address","venue_manager_id")))
			{
				$sql = 'INSERT INTO '.TBL_VENUE.' (tenant_id, created,	created_by,	status,	venue_name,	address_id,	email_address,	manager_id)  VALUES (?,?, ?,?,?, ?,?,?)';
				$insert = $db->query($sql, STATE::get_state("tenant_id") , date("Y-m-d h:i:s"),  1,1, $this->get_post('venue_name'), $this->get_post('venue_address'), $this->get_post('venue_email_address'),1  );
				
				if ($insert->affectedRows())
				{
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "LOCATION_ADD", "COMMENT" => "Success" ));
				}
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "LOCATION_ADD", "COMMENT" => "Failed to add new venue" ));
	}
	
	
	/**
	 * location update
	 */
	private function __LOCATION_UPDATE()
	{
		global $db;
		if ($this->access_right(FEATURE_LOCATION_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("location_id")))
			{
				$allowed_fields = array("venue_name","venue_address","venue_email_address","venue_manager_id");
				$params = array();
				$fields = array();
				$qs = array();
				
				$post_data = $this->post;

				foreach($post_data as $field => $value)
				{
					if (in_array($field, $allowed_fields))
					{
						$fields[] = $field.'=?';
						$params[] = $value;
					}
				}
				
				if ($params)
				{
					$params[] = $post_data['location_id'];

					$sql = 'UPDATE '.TBL_VENUE.' SET '.join(",", $fields).' WHERE id=?';			
					$insert = $db->query($sql, $params);
				
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "LOCATION_UPDATE", "COMMENT" => "Success" ));
				}
			
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "LOCATION_UPDATE", "COMMENT" => "Failed to update location" ));
	}	
	
	
	/**
	 *
	 *
	 */
	private function __LOCATION_DELETE()
	{
		global $db;
		if (1) //($this->access_right(FEATURE_LOCATION_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("location_id")))
			{
				$post_data = $this->post;
				
				$params = array($post_data['location_id'],0, STATE::get_state("TENANT_ID"));

				$sql = 'UPDATE '.TBL_VENUE.' SET status = ? WHERE tenant_id = ? AND id=?';			
				$db->query($sql, $params);
				
				$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "LOCATION_DELETE", "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "LOCATION_DELETE", "COMMENT" => "Failed to update location" ));
	}
	
	
	// chat ############################################################################################################
	
	private function unread_messages($conversations)
	{
		$qs = array();
		$params = array();
		$answer = array();
		
		foreach($conversations as $conservation)
		{
			$qs[] = '?';
			$params[] = $conversation['conversation_id'];
		}
		
		$params[] = STATE::Get_state("TENANT_ID");
		$params[] = 0;
		$sql = "SELECT COUNT(*) AS 'COUNT', conversation_id FROM conversation_chat WHERE conservation_id IN (".$qs.") AND tenant_id = ? AND read_message = ? GROUP BY conversation_id";
		$result = $db->query($sql, $params)->fetchArray();
		
		foreach($result as $r)
		{
			$answer[$r['conversation_id']] = $r['count'];
		}
		
		return $answer;
	}
	
	
	private function __CHAT_LIST()
	{
		if (1) // ($this->access_right(FEATURE_LOCATION_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("login_id")))
			{
				// get a list of conversations
				$login_id = $this->get_post('login_id');
				$sql = "SELECT * FROM conversation_header WHERE tenant_id = ? AND status = ? AND (speaker_id = ? OR listener_id = ?)";
				$params = array(STATE::Get_state("TENANT_ID"), 1, $login_id, $login_id );
				$result = $db->query($sql , $params)->fetchArray();
				
				// get a count of unread messages for each conversation
				$unread_conversations = $this->unread_messages($result);
				
				$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "CHAT_LIST", "CONVERSATIONS" => $result, "UNREAD_MESSAGES" => $unread_conversations, "COMMENT" => "Success" ));
			}
		}	
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "CHAT_LIST", "COMMENT" => "Failed to update location" ));
	}
	
	
	
	private function part_of_conversation($conversation_id, $login_id)
	{
		$sql = "SELECT * FROM conversation_header WHERE conversation_id = ?";
		$result = $this->db->query($sql, $conversation_id)->fetchArray();
		
		if ($result)
		{
			if (($result['listener_id']==$login_id) || ($result['speaker_id']==$login_id)) return true;
		}
		
		return false;
	}
	
	
	private function __CHAT_LISTEN()
	{
		global $db;
		if (1) //($this->access_right(FEATURE_LOCATION_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("login_id", "conversation_id", "talking_point")))
			{
				// Does conversation belong to login id?
				if ($this->part_of_conversation($this->get_post("login_id")))
				{
					// Get a list of conversations
					$sql = "SELECT * conversation_chat WHERE conversation_id = ?  AND tenant_id = ? OFFSET ?";
					$conversasion = $db->query($sql, $this->get_post("conversation_id"), $this->get_post("conversation_id"), $this->get_post("offset")  );
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "CHAT_LISTEN", "CONVERSATION" => $conversation ,"COMMENT" => "Success" ));
				}
			}
		}		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "CHAT_LISTEN", "COMMENT" => "Failed : cannot access conversation" ));
	}
	
	
	private function speaker_exist($login_id)
	{
		global $db;
		$sql = "SELECT COUNT(*) AS 'count' FROM person WHERE tenant_id = ? AND id = ? AND status = ?";
		$params = array(STATE::get_state("TENANT_ID"), $login_id, 1);
		$result = $db->query($sql, $params)->fetchArray();
		return $result['count'];
	}
	
	private function does_conversation_exist($login_id, $speaker_id)
	{
		global $db;
		$sql = "SELECT * FROM conversation_header WHERE tenant_id = ? AND speaker_id IN (?,?)";
		$params = array(STATE::Get_state("TENANT_ID"), $login_id, $speaker_id);
		$result = $db->query($sql, $params )->fetchArray();
		return $result;
	}
	
	private function create_a_new_conversation($speaker_id, $listener_id)
	{
		global $db;
		$sql = "INSERT INTO conversation_header (id, created, status, tenant_id, speaker_id, listener_id) VALUES (NULL, ?,?,?,?,?)";
		$params = array(date("Y-m-d h:i:s") , 1, STATE::get_state("TENANT_ID"), $speaker_id, $listener_id );
		$insert = $db->query($sql, $params);
		return $insert->lastInsertID();
	}
	
	private function __CHAT_HELLO()
	{
		global $db;
		if (1) // ($this->access_right(FEATURE_LOCATION_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("login_id", "listener_id")))
			{
				if (!$this->speaker_exist($this->get_post("login_id")))
				{
					// Does speaker and listener already have a conversation?
					if ($this->does_conversation_exist($this->get_post("login_id"), $this->get_post("listener_id")))
					{
						// Get a list of conversations
						$sql = "SELECT * conversation_chat WHERE conversation_id = ?  AND tenant_id = ? OFFSET ?";
						$conversation = $db->query($sql, $this->get_post("conversation_id"), $this->get_post("conversation_id"), $this->get_post("offset")  );
						$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "CHAT_HELLO", "CONVERSATION_ID" =>$conversation["conversation_id"]  ,"CONVERSATION" => $conversation ,"COMMENT" => "Success" ));
					}
					else
					{
						$speaker_id = $this->get_post("login_id");
						$listener_id =  $this->get_post("speaker_id");
						$conversation_id = $this->create_a_new_conversation($speaker_id, $listener_id);
						$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "CHAT_HELLO", "CONVERSATION_ID" =>$conversation_id ,"CONVERSATION" => array() ,"COMMENT" => "Success" ));
					}
				}
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "CHAT_HELLO", "COMMENT" => "Failed to start chat" ));
	}
	
	
	private function __CHAT_SPEAK()
	{
		global $db;
		if ($this->access_rights(FEATURE_CHAT_SPEAK) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("login_id", "conversation_id", "talking_point")))
			{
				// Does conversation belong to login id?
				if ($this->part_of_conversation($this->get_post("login_id")))
				{
					// Get a list of conversations
					$sql = "SELECT * conversation_chat WHERE conversation_id = ?  AND tenant_id = ? OFFSET ?";
					$conversasion = $db->query($sql, $this->get_post("conversation_id"), $this->get_post("conversation_id"), $this->get_post("offset")  );
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "CHAT_SPEAK", "CONVERSATION" => $conversation ,"COMMENT" => "Success" ));
				}
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "LOCATION_DELETE", "COMMENT" => "Failed to update location" ));
	}

	
	private function __CHAT_ADDRESS_BOOK()
	{
		global $db;
		$address_book = array();
		
		if ($this->access_right(FEATURE_CHAT_ADDRESS_BOOK) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("venue_id" , "account_id" )))
			{
				// get contacts from venue
				$sql = "SELECT * FROM person as 'p', venue as 'v' WHERE p.venue_id = v.venue_id AND (p.venue_id = ? OR p.chat_book =?) AND p.status = ?";	   
				$params = array($this->get_post('venue_id'), 1, 1);
				$result = $db->query($sql , $params)->fetchAll();
				foreach($result as $row)
				{
					if ($row['id'] != $account_id)
					{
						$address_book[$row['id']] = $row['first_name'].' '.$row['last_name'].'('.$row['venue_name'].')';
					}
				}
				
				$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "__CHAT_ADDRESS_BOOK", "ADDRESS_BOOK" => $address_book, "COMMENT" => "Success" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "__CHAT_ADDRESS_BOOK", "ADDRESS_BOOK" => $address_book, "COMMENT" => "Failed to update location" ));
		}
	}
	
	
	// Collection #######################################################################################################
	
	
	/**
	 *	This action is used to commence the creating new collection
	 */
	private function __COLLECTION_TYPE_NEW()
	{
		global $db;
		if (1) //($this->access_right(COLLECTION_TYPE_NEW) == ACCESS_ADMIN )
		{
			// Get pre-deined types 
			$this->dispatch(array("OUTCOME"=> 1,  
								  "ACTION"=> "COLLECTION_TYPE_NEW", 
								  "key_type_period" => KEYS::get_key(8),
								  "key_type_field" => KEYS::get_key(7),
								  "COMMENT" => "Success" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_NEW", "COMMENT" => "Failed to update location" ));
	}
	
	
	/**
	 *
	 */
	private function __COLLECTION_TYPE_NEW_CREATE()
	{
		global $db;
		if (1) //($this->access_right(COLLECTION_TYPE_NEW) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "key_type_period" , "title",  "description" )))
			{
				// Get pre-deined types
				$period = KEYS::get_key(8);
				$field_types = KEYS::get_key(8);
			
				$this->dispatch(array("OUTCOME"=> 1,  
									  "ACTION"=> "COLLECTION_TYPE_NEW_SAVE", 
								      "COLLECTION_TYPE_ID" => $collection_type_id,
								      "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_NEW_SAVE", "COMMENT" => "Failed to update location" ));
	}
	
	
	
	/**
	 *
	 */
	private function __COLLECTION_TYPE_NEW_UPDATE()
	{
		global $db;
		if (1) //($this->access_right(COLLECTION_TYPE_NEW) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id", "key_type_period" , "title",  "description" )))
			{
				// Get pre-defined types
					$params = array();
				$period = KEYS::get_key(8);
				if (in_array( $this->get_post("key_type_period"), $period ))
				{
					$collection_type_id = $this->get_post("collection_type_id");
					
					$sql = "UPDATE ".TBL_COLLECTION_TYPE." SET title = ?, description =  ?, period_id = ?";
					$sql.= "WHERE id = ? AND tenant_id = ?";
					$params[] = $title;
					$params[] = $description;
					$params[] = $key_type_period;
					$params[] = $collection_type_id;
					$params[] = STATE::get_state("TENANT_ID");
					$db->query($sql , $params);
					
					$this->dispatch(array("OUTCOME"=> 1,  
										  "ACTION"=> "__COLLECTION_TYPE_NEW_UPDATE", 
										  "COLLECTION_TYPE_ID" => $collection_type_id,
										  "COMMENT" => "Success" ));
				}
				
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_NEW_SAVE", "COMMENT" => "Failed to update location" ));
	}
	
	
	private function __COLLECTION_TYPE_DELETE()
	{
		global $db;
		
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id" )))
			{
				$sql = "UPDATE ".TBL_COLLECTION_TYPE." SET status = ? WHERE tenant_id = ? AND id =? ";
				$params = array(0, STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"));
				$this->db->query();

				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "COLLECTION_TYPE_DELETE", 
									  "COMMENT" => "Success" ));
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_DELETE", "COMMENT" => "Failed : access denied" ));
	}	
	
	
	private function __COLLECTION_TYPE_GET()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			// Is there a username field given?
			$person_id = array();
			$qs = array();
			
			$person_name = $this->get_post("user_name");
			if ($person_name)
			{
				$person_name = "%".$person_name."%";
				$sql = "SELECT * FROM ".TBL_PERSON." WHERE tenant_id = ? AND (first_name LIKE ? OR last_name LIKE ?) AND status = ?";
				$params = array(STATE::Get_state("TENANT_ID"), $person_name, $person_name );
				$result = $db->query($sql, $params)->fetchAll();
				
				if ($result)
				{
					foreach($result as $indice => $r)
					{
						$person_id[] = $r["id"];
						$qs[] = "?";
					}
				}
			}
	
			$status = $this->get_post("status");
			$status = ($status) ? $status : 0;
			
			// Now build main query
			$sql = "SELECT * FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND status = ? ";
			$p = array(STATE::Get_state("TENANT_ID"), $status);
			
			$search_text = $this->get_post("search_text");
			if ($search_text)
			{
				$sql.= " AND ( title LIKE ? OR  description LIKE ?)";
				$p[] = "%".search_text."%";
				$p[] = "%".search_text."%";
			}
			
			$period_id = $this->get_post("period_id");
			if ($period_id)
			{
				$sql.=" AND period_id = ? ";
				$p[] = $period_id;
			}
			
			// order
			
			// limit and offset
			
			$records = $db->query($sql, $params)->fetchArray();
			
			$this->dispatch(array("OUTCOME" => 1,  
								  "ACTION" => "COLLECTION_TYPE_GET", 
								  "" => $records,
								  "COMMENT" => "Success" ));

		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_GET", "COMMENT" => "Failed : access denied" ));
	}	
	
	
	// Collection field structure ############################################################################################################
	
	
	// Delete field type
	private function __COLLECTION_STRUCTURE_DELETE_FIELD()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id", "field_id" )))
			{
				$sql = "UPDATE ".TBL_COLLECTION_TYPE." SET status = ? WHERE tenant_id = ? AND collection_type_id =? AND id = ?";
				$params = array(0, STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"), $this->get_post("field_id"));
				$this->db->query($sql , $params);

				$this->dispatch(array("OUTCOME" => 1,
									  "ACTION" => "COLLECTION_STRUCTURE_DELETE_FIELD", 
									  "COMMENT" => "Success" ));
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_STRUCTURE_DELETE_FIELD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	// Move field type up or down one
	private function __COLLECTION_STRUCTURE_MOVE_FIELD()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id", "field_id", "position_relative" )))
			{
				$old_postion = 0;
				$new_postion = 0;
				
				$sql = "SELECT * FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND collection_type_id = ? AND id =? ";
				$params = array(STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"), $this->get_post("field_id") );
				$r1 = $db->query($sql , $params)->fetchArray();
				
				$old_postion = $r1['position'];
				$new_position = $old_postion + (intval($this->get_post("position_relative")));
				$sql = "SELECT * FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND collection_type_id = ? AND position =? ";
				$params = array(STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"), $new_position );
				$r2 = $db->query($sql , $params)->fetchArray();
				
				if ( ($r1) && ($r2) )
				{
					// Get current field 
					$sql = "UPDATE ".TBL_COLLECTION_FIELD_TYPE." SET position = ? WHERE tenant_id = ? AND id = ? ";
					$params1 = array($new_position, STATE::Get_state("TENANT_ID"), $r1["id"]  );
					$db->query($sql, $params1);
			
					// Get other field
					$sql = "UPDATE ".TBL_COLLECTION_FIELD_TYPE." SET position = ? WHERE tenant_id = ? AND id = ? ";
					$params2 = array($old_position, STATE::Get_state("TENANT_ID"), $r2["id"]);
					$db->query($sql, $params2);
					
					$this->dispatch(array("OUTCOME" => 1,
										  "ACTION"  => "COLLECTION_STRUCTURE_MOVE_FIELD", 
									      "COMMENT" => "Success" ));					  
				}
				else
				{
					$this->dispatch(array("OUTCOME" => 0,  
										  "ACTION"  => "COLLECTION_STRUCTURE_MOVE_FIELD", 
										  "COMMENT" => "Failed" ));
				}
			}
		}
		
		$this->dispatch(array("OUTCOME" => 0,  
							  "ACTION"  => "COLLECTION_STRUCTURE_MOVE_FIELD", 
							  "COMMENT" => "Failed : access denied" ));
	}	
	
	
	
	// Add field type
	private function __COLLECTION_STRUCTURE_ADD_FIELD()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id", "title", "type", "options", "position" )))
			{
				// Does collection_type exist to this tenant_id?
				$sql = "SELECT ".TBL_COLLECTION_TYPE." WHERE tenant_id = ? AND id = ?";
				$params = array( STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"));
				$c = $this->db->query($sql, $params)->fetchArray();
				
				if ($c)
				{
					// How many current status fields are there?
					$sql =  "SELECT COUNT(*) AS 'count' FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND collection_type_id = ?";
					$result = $db->query($sql, $params)->fetchArray();
					$total_positions = $result['count'];
					
					// what is the last field position number?
					$sql =  "SELECT * FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND collection_type_id = ?";
					$result = $db->query($sql, $params)->fetchArray();
					$total_positions = $result['position'];
					//ORDER BY position DESC
					
					// increase position by 1 for current status where position = post['position']

					// insert
					$sql = "INSERT INTO ".TBL_COLLECTION_FIELD_TYPE." (id, created, status, tenant_id, speaker_id, listener_id) VALUES (NULL, ?,?,?,?,?)";
					$params = array();
					$db->query($sql, $params);
					
					$this->dispatch(array("OUTCOME" => 1,
										  "ACTION" => "COLLECTION_TYPE_DELETE", 
										  "COMMENT" => "Success" ));
				}
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_TYPE_DELETE", "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function do_collection_records_exist()
	{
		global $db;
		$sql = "SELECT COUNT(*) AS 'count' FROM ".TBL_COLLECTION_RECORD." WHERE tenant_id = ? AND collection_type_id =? ";
		$result = $db->query($sql , array())->result();
		return $result;
	}
	
	
	// Update field type
	private function __COLLECTION_STRUCTURE_UPDATE_FIELD()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id", "field_id" )))
			{
				$sql = "UPDATE ".TBL_COLLECTION_TYPE." SET status = ? WHERE tenant_id = ? AND collection_type_id =? AND id = ?";
				$params = array(0, STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"), $this->get_post("field_id"));
				$this->db->query($sql , $params);

				$this->dispatch(array("OUTCOME" => 1,
									  "ACTION" => "COLLECTION_STRUCTURE_UPDATE_FIELD", 
									  "COMMENT" => "Success" ));
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "__COLLECTION_STRUCTURE_UPDATE_FIELD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	/**
	 *
	 ***/
	private function  __COLLECTION_STRUCTURE_GET_FIELDS()
	{
		global $db;
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array( "collection_type_id")))
			{
				$sql = "SELECT * FROM ".TBL_COLLECTION_FIELD_TYPE." WHERE tenant_id = ? AND collection_type_id = ? AND status = ? ORDER BY position";
				$params = array( STATE::Get_state("TENANT_ID"), $this->get_post("collection_type_id"), 1);
				$data_structure = $this->db->query($sql , $params)->fetchAll();

				$this->dispatch(array("OUTCOME" => 1,
									  "ACTION" => "COLLECTION_STRUCTURE_GET_FIELDS", 
									  "DATA_STRUCTURE" => $data_structure,
									  "COMMENT" => "Success" ));
			}
		}
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_STRUCTURE_GET_FIELDS", "DATA_STRUCTURE" => array(), "COMMENT" => "Failed : access denied" ));
	}

	// =============================================
	
	/*
	private function validate_collection_fields($field_structure)
	{
		$valid = true;
		$fields = json_decode($field_structure);
		$structure_fields = array();
		
		foreach($fields as $field)
		{
			if (empty($field['id'])) return false;//$valid = false;
			if (empty($field['title'])) return false;
			
			if (empty($field['field_type'])) return false;
			if (!empty($field['field_type']))
			{
				if (! in_array( $field['field_type'] , array(1,2,3,4,5))) return false;
				
				// Does it have options?
				if ($field['field_type'] == 5)
				{
					if ( (!empty($field['field_options'])) && (is_array($field['field_options'])))
					{
						foreach($field['field_options'] as $v)
						{
							// key, value, action, id
							$parts = explode("¦", $v );
							if (count($parts)!=4) return false;
							if ($parts[0]="") return false;
							if ($parts[1]="") return false;
							if (!in_array( $parts[2] , "new", "update", "delete"))
							{
								return false;
							}
							if (!intval($parts[3])) return false;
						}
					}
					else 
					{
						return false;
					}
				}
			}
		}
		
		return $fields;
	}
	*/
	
		
	// collection use ###################################################################################################################
	
	private function dc_type_allowed($collection_type_id)	
	{
		global $db;
		
		$sql = "SELECT * FROM ".TBL_COLLECTION_TYPE." WHERE tenant_id = ? AND status = ? AND id = ?";
		$param = array(STATE::Get_state("TENANT_ID"), 1, $collection_type_id);
		$result =$db->query($sql, $param)->fetchArray();
	
		return $result;
	}
	
		
	private function collection_record_and_structure($collection_type_id, $record_id)
	{
		global $db;
				
		$sql = "SELECT * FROM collection_field_type as cr ".
		       "LEFT JOIN collection_record_field AS cfr cr.collection_type_field_id =  ".
			   "WHERE cr.status = ? AND cr.tenant_id = ? AND cr.collection_type_id = ? "; 
		$param = array(1, STATE::Get_state("TENANT_ID"), $collection_type_id);
		$result = $this->db->query($sql, $param)->fetchAll();
		
		return $result;
	}
	
	private function __COLLECTION_RECORD_LIST()
	{
		global $db;
		
		if (1) // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_READ )
		{
			if (!$this->required_exist( $this->post, array("collection_type_id", "title", "description", "period", "field_structure")))
			{
				
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "COLLECTION_RECORD_LIST", 
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_RECORD_LIST", "COMMENT" => "Failed : access denied" ));
	}
	
	
	
	private function  __COLLECTION_RECORD_GET()
	{
		global $db;
		
		if (1) //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_READ )
		{
			if ($this->required_exist( $this->post, array("collection_type_id", "record_id")))
			{
				$this->dispatch(array("OUTCOME"=> 0,  
								      "ACTION"=> "COLLECTION_RECORD_GET", 
									  "COMMENT" => "Failed : access denied" ));	
			}
			
			$collection_type_id = $this->get_post("collection_type_id");
			$record_id = $this->get_post("record_id");
				
			$collection_type = $this->dc_type_allowed($collection_type_id);
			if (!$collection_type)
			{
				$this->dispatch(array("OUTCOME" => 0,  
									  "ACTION" => "COLLECTION_RECORD_GET", 
									  "COMMENT" => "Failed" ));	
			}
			
			$collection_record = $this->collection_field_structure($collection_type_id, $record_id);		
			if (!$collection_record)
			{
				$this->dispatch(array("OUTCOME" => 0,  
									  "ACTION" => "COLLECTION_RECORD_GET",  
									  "COMMENT" => "Failed" ));
			}
				
			$this->dispatch(array("OUTCOME" => 1,  
								  "ACTION" => "COLLECTION_RECORD_GET", 
								  "COLLECTION_RECORD" => $collection_record,
								  "COMMENT" => "Success" ));
		}	
	}
	
	
	private function __COLLECTION_RECORD_ADD()
	{
		global $db;
		
		if (1) //if ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("collection_type_id", "data_delimited")))
			{
				$data = $this->get_post("data_delimited");
				$row_end_char = '|';
				$col_end_char = '#';	
				$rows = explode($row_end_char, $data);
				
				// Does this collection type exist and belong to tenant_id?
				if ((1) && ($rows))
				{
					// Set all previous entries as latest as false;
					$sql = "UPDATE ".TBL_COLLECTION_RECORD." SET latest = ? WHERE tenant_id = ? collection_type_id = ? AND venue = ?";
					$params = array(0, STATE::Get_state("TENANT_ID"), $venue_id);
					$db->query($sql, $params);
					
					// Create record entry
					$sql = "INSERT INTO ".TBL_COLLECTION_RECORD." (id,collection_type_id,tenant_id,venue_id,created,created_by,status,next_due) VALUES (?,?,?,?,?,?,?,?)";
					$params = array(NULL, $collection_type_id, STATE::Get_state("TENANT_ID"), $venue_id, date("Y-m-d h:i:s"), STATE::Get_state("USER_ID"), 1, $next_due  );
					$db->query($sql, $params);
					$record_id = $db->lastInsertID();
					
					// Pull record type structure
					$structure = 'SELECT * FROM '.'collection_type_field'.' WHERE status = ? AND tenant_id = ? AND collection_type_id = ?';
					$params = array(1, STATE::Get_state("TENANT_ID"), $collection_type_id);
					$result = $this->db->query($sql , $params)->fetchAll();
					$structure_map = array();
					if ($result)
					{
						foreach($result as $indices => $r)
						{
							$structure_map[$r["id"]] = $r;
						}
					}
					
					$sql_template = "INSERT INTO (id, tenant_id, collection_type_id, status, collection_type_field_id, value) VALUES (NULL,?,?,?,?,?)";
					foreach($rows as $row)
					{
						$cells = explode($col_end_char, $row);
						$params = array(NULL, STATE::Get_state("TENANT_ID"), 1, $collection_type_id, $cells[0], $cells[1] );
						$db->query($sql_template, $params);
					}
					
					$this->dispatch(array("OUTCOME"=> 1,  "ACTION"=> "COLLECTION_RECORD_ADD", "COMMENT" => "Success" ));	
				}
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_RECORD_ADD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function __COLLECTION_RECORD_DELETE()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("collection_type_id", "title", "description", "period", "field_structure")))
			{
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "COLLECTION_RECORD_ADD", 
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_RECORD_ADD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function __COLLECTION_RECORD_UPDATE()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("collection_type_id", "title", "description", "period", "field_structure")))
			{
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "COLLECTION_RECORD_ADD", 
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "COLLECTION_RECORD_ADD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	// Meta Table #######################################################################################################

	
	private function __META_TABLE_DEFINE()
	{
		global $db;
		if (1)  //($this->access_right(META_TABLE_DEFINE) == ACCESS_SUPER_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("table_definition")))
			{
				$table_definition = $this->get_post("table_definition");
				
				if ($table_definition)
				{
					$line = 0;
					
					$tenant_id = -1;
					$table_name = '';
					$meta_table_id = 0;
					
					$valid = 1;
					$comment = 'Success';
					
					$rows = explode(PHP_EOL, $table_definition);
					$structure = array();
					
					foreach($rows as $row)
					{
						if (!$valid) break;
						$structure_object = new stdclass();
						
						$columns = explode("," , $row);
						switch($line)
						{
							case 0:
							$table_name = $columns[0];
							break;
							
							case 1:
							$tenant_id = $columns[0];
							if (intval($tenant_id) == 0) 
							{
								$valid = 0;
								$comment = "invalid tenant id";
							}
							// ignore this the field names row
							break;
							break;
							
							case 2:
							
							default:
							/* columns :
								0 A) field_name, 
								1 B) field caption, 
								2 C) Type,
								3 D) Mandatory,
								4 E) validation, 
								5 F) default value, 
								6 G) position, 
								7 H) linked_to, 
								8 I) report_col_caption, 
								9 J) report_col_width, 
							   10 K) report_postion
							*/
							
							if (count($columns) >= 10)
							{
								$structure_object->field_name = $columns[0];
								$structure_object->caption = $columns[1];
								$structure_object->type = $columns[2];
								$structure_object->mandatory = $columns[3];
								$structure_object->validation = $columns[4];
								$structure_object->default_value = $columns[5];
								$structure_object->position = $columns[6];
								$structure_object->linked_to = $columns[7];
								$structure_object->report_col_caption = $columns[8];
								$structure_object->report_col_width = $columns[9];
								$structure_object->report_postion = isset($columns[10]) ? $columns[10] : '';
							
								$structure[] = $structure_object;
							}
							break;
						}
						$line++;
					}
echo '<xmp>';
echo '<h1>'.$table_name.'</h1>';
echo '<h1>'.$tenant_id.'</h1>';
print_r($structure);
echo '</xmp>';				
die();
					if (!$valid)
					{
						$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "META_TABLE_DEFINE", "COMMENT" => "CSV definition is invalid" ));	
					}
					
					// Create meta table 
					$sql = "INSERT INTO meta_table (id,tenant_id,created,created_by,status, table_name ) VALUE (?,?,?,?,?,?)";
					$params = array(NULL, STATE::Get_state("TENANT_ID"), date("Y-m-d h:i:s"), $this->get_post("login_id"), 1, $table_name );
					$db->query($sql, $params);
					$meta_table_id = $db->lastInsertID();
					
					foreach($structure_object as $indices => $obj)
					{
						$sql = "INSERT INTO meta_table_field (id, meta_table_id, field_name, field_type, caption, validation_type, default_value, link_field, sequence, mandatory) ".
							   "VALUES (?,?,?,?,?,?,?,?,?,?)";
						$param = array(NULL, $meta_table_id, $obj->field_name, $obj->field_type, $obj->caption, '', $obj->sequence, $obj->mandatory );
						$db->query($sql, $param);
					}

					$this->dispatch(array("OUTCOME" => $valid,  
										 "ACTION" => "META_TABLE_DEFINE",
										 "META_TABLE_ID" => $meta_table_id, 
										 "COMMENT" => $comment ));
				}
			}
		
			$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "META_TABLE_DEFINE", "COMMENT" => "Failed : access denied" ));		
		}
	}
	
	
	private function create_table($meta_table, $meta_table_fields)
	{
		global $db;
		
		// Create the database
		$sql1 = "ALTER TABLE `".$meta_table['table_name']."` ADD PRIMARY KEY (`id`)";
		$db->query($sql);

		$sql2 = "ALTER TABLE `".$meta_table['table_name']."` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1";
		$db->query($sql);
		
		$sql3 ="COMMIT";
		$db->query($sql);
		
		$parts = array();
		foreach($meta_table_fields as $meta_table_field)
		{
			$type_map["int"] = "int(11) NOT NULL";
			$type_map["datetime"] = "datetime NOT NULL";
			$type_map["tinytext"] = "tinytext NOT NULL";
			$type_map["text"] = "text NOT NULL";
			$type_map["bool"] = "tinyint(4) NOT NULL";
			
			$parts[] = "`".$meta_table_field['field_name']."` ".$field_type;
		}
		
		$sql4 = "CREATE TABLE `".$meta_table['table_name']."` (".PHP_EOL;
		$sql.= join(",".PHP_EOL , $parts).") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		
		// Create the database script
		$contents = $sql1.PHP_EOL.$sql2.PHP_EOL.$sql3.PHP_EOL.$sql4;
		file_put_contents("", $contents);
		
	}
	
	
	private function model_template()
	{
$template = <<<EOL
<?php
class <%CLASS%>_CONTROLLER
{
	private static function get_base_query()
	{
		<%DOLLAR>sql = "<%SELECT_QUERY%>";
		return <%DOLLAR>sql;
	}
	
	public static function get(<%DOLLAR%>criteria, <%DOLLAR%>offset, <%DOLLAR%>limit)
	{
		global <%DOLLAR%>db;
		<%DOLLAR%>sql = self::get_base_query();
		
		<%DOLLAR%>result = DB_HELPER::add_criteria(<%DOLLAR%>sql,<%DOLLAR%>criteria,0,0);
		return <%DOLLAR%>result;
	}
	
	public static function get_by_id(<%DOLLAR%>id)
	{
		global <%DOLLAR%>db;
		<%DOLLAR%>sql = self::get_base_query();
		<%DOLLAR%>criteria[] = array("id", EQUAL, <%DOLLAR%>id);
		<%DOLLAR%>result = DB_HELPER::add_criteria(<%DOLLAR%>sql,<%DOLLAR%>criteria,0,0);
		
		return <%DOLLAR%>result;
	}
	
	public static function insert(<%DOLLAR%>fields)
	{
		global <%DOLLAR%>db;
		<%DOLLAR%>field_list = array();
		<%DOLLAR%>qs = array();
		
		foreach(<%DOLLAR%>fields as <%DOLLAR%>field)
		{
			<%DOLLAR%>qs[] ='?';
			<%DOLLAR%>field_list[] = "'".<%DOLLAR%>field."'";
		}
		
		<%DOLLAR%>sql = "INSERT INTO <%TABLE%> (".join(",", <%DOLLAR%>field_list).") VALUES (".<%DOLLAR%>qs.")";
		<%DOLLAR%>db->query(<%DOLLAR%>sql, <%DOLLAR%>params);
	}
	
	public static function update(<%DOLLAR%>update, <%DOLLAR%>id)
	{
		global <%DOLLAR%>db;
		<%DOLLAR%>sql = "";
		
	}
	
	public static function delete_record(<%DOLLAR%>id)
	{
		global <%DOLLAR%>db;
		<%DOLLAR%>sql = "UPDATE <%TABLE%> WHERE tenant_id = ? AND id = ? ";
		<%DOLLAR%>params = array(STATE::get_state("TENANT_ID"), <%DOLLAR%>id);
		<%DOLLAR%>db->query(<%DOLLAR%>sql, <%DOLLAR%>params);
	}
	
	
	public static function csv(<%DOLLAR%>table_name, <%DOLLAR%>criteria)
	{
		global <%DOLLAR%>db;
				
		// Load the meta_table 
		<%DOLLAR%>meta_table = <%DOLLAR%>db->query("SELECT * FROM meta_table WHERE meta_table_name LIKE ? AND status = ?", array("%".<%DOLLAR%>table_name."%", 1))->fetchArrat();
		
		// Load meta table fields
		<%DOLLAR%>meta_table_fields = <%DOLLAR%>db->query("SELECT * FROM meta_table_field WHERE meta_field_id = ? ", array())->fetchAll();
		
		// Create header
		<%DOLLAR%>header = array();
		<%DOLLAR%>field_format = array();
		<%DOLLAR%>field_list = array();
		<%DOLLAR%>content = '';
		
		foreach(<%DOLLAR%>meta_table_fields as <%DOLLAR%>meta_field)
		{
			<%DOLLAR%>header[] = <%DOLLAR%>meta_field[];
		}
		
		<%DOLLAR%>data_set = self::get(<%DOLLAR%>criteria, 0, 10000);
		
		<%DOLLAR%>rows = '';
		foreach(<%DOLLAR%>data_set as <%DOLLAR%>data_row )
		{
			<%DOLLAR%>row = array();
			foreach(<%DOLLAR%>field_list as <%DOLLAR%>field )
			{
				<%DOLLAR%>rows[] = <%DOLLAR%>data_row[<%DOLLAR%>field ];
			}
		}
		
		<%DOLLAR%>now = date("Y_m_d_h_i_s");
		<%DOLLAR%>filename = <%DOLLAR%>meta_table."_".<%DOLLAR%>now.'.csv';
		
		
	}
}
?>
EOL;
	
		return $template;
	}
	
	
	private function create_model_script($meta_table, $meta_table_id, $meta_table_fields)
	{
		$template = self::model_template();
		$template =  str_replace("<%DOLLAR%>", "$", $template);
		
		// Build base get query
		$sql = 'SELECT <%FIELDS%> FROM '.$meta_table.' ';
		
		$field_list = array();
		$links_list = array();
		
		foreach($meta_table_fields as $meta_table_field)
		{
			$field_list[] = $meta_table_field;
			if ($meta_table_field['link_field'] != '')
			{
				$link_element = explode(",", $meta_table_field['link_field']);
				$links_list[$link_element[0]] = $link_element[1];
			}
		}
		
		$field_list = join( ",", $field_list);
		$sql = str_replace( "<%%FIELDS>", $field_list, $sql);
		
		foreach($links_list as $table => $join_field)
		{
			$sql.= "LEFT JOIN ".$table." ON ".$meta_table.".".$join_field." = ".$table.".".$join_field." ";
		}
		
		$template = str_replace("<%SELECT_QUERY%>", $sql, $template);
				
		// Save model 
		$model_filename =  $meta_table."_model.php";
		file_put_contents($model_filename, $template);
		
	}
	
	private function html5_template($fragements)
	{
		$title = (isset($fragements['title'])) ? $fragements['title'] : '';
		
		return "<!DOCTYPE html>".PHP_EOL.
			   "<html>".PHP_EOL.
			   "<head>".PHP_EOL.
			   "<title>".$title."</title>".
			   "</head>".PHP_EOL.
			   "<body>".PHP_EOL."<%BODY%>".
			   "</body>".PHP_EOL.
			   "</html>";
	}
	
	private function create_entry_view($meta_table, $meta_table_id, $meta_table_fields)
	{
		$content = $this->html5_template($meta_table.' entry');
		$outer_html = '<table>'.
					  '<%BODY%>'.
					  '</table>'.
					  '<div>'.
					  '<button></button>'.
					  '<button></button>'.
					  '</div>';
					  
		
		$body = '';
		
		foreach($meta_table_fields as $meta_field)
		{
			$html_form_element = '';
			switch($meta_field)
			{
				// text
				case "INPUT":
				$html_form_element = '<INPUT type="text" id="'.$meta_field['field_name'].'" name="'.$meta_field['field_name'].'" class="form_element"></INPUT>';
				break;
				
				// select
				case "SELECT":
$html_form_element = '<SELECT id="'.$meta_field['field_name'].'" name="'.$meta_field['field_name'].'"></SELECT>';
				break;
				
				// text area
				case "TEXTREA":
				$html_form_element = '<TEXTAREA id="'.$meta_field['field_name'].'" name="'.$meta_field['field_name'].'"></TEXTAREA>';
				break;
				
			}
			
			$body.='<tr><td style="40%">'.$meta_field['caption'].'</td><td style="60%">'.$html_form_element.'<span id="'.$meta_field[field_name].'_error"></span></td></tr>'.PHP_EOL;
		}
		
		$outer_html = str_replace("<%BODY%>", $body, $outer_html);
		$content = 	str_replace("<%BODY%>", $outer_html, $content);
		
		$filename = $meta_table.'_list_view.tpl';
		file_put_contents($flename, $content);
	}
	
	private function create_entry_list_view($meta_table, $meta_table_id, $meta_table_fields)
	{
		$html = $this->html5_template('');
		
		$outer_html = '<table>'.
					  '<%BODY%>'.
					  '</table>'.
					  '<div>'.
					  '<button></button>'.
					  '<button></button>'.
					  '</div>';
		
		$body = '';
		
		// build list row
		foreach($meta_table_fields as $meta_table_field)
		{
			
		}
	}
	
	
	
	private function create_entry_js($meta_table, $meta_table_id, $meta_table_fields)
	{
		
	}
	
	private function create_list_js($meta_table, $meta_table_id, $meta_table_fields)
	{
$template = <<<EOL
		// Populate all options
		
		// search 
			
		// reset option
			
			
			
		// Delete option
		function)	
		
		// Get pull option
EOL;
			
	}
	
	/**
	 *
	 */
	private function __META_TABLE_DEPLOY()
	{
		global $db;
		$comment = "Success";
		
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_SUPER_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("meta_table_id", "meta_table_name")))
			{
				$table_deployed = $db->query("SELECT * FROM meta_table_field WHERE id = ? AND deployed = ?", array($this->get_post("meta_table_id"), 0))->fetchArray();
				if (!$table_deployed)
				{
					$sql = "SELECT * ".
						   "FROM information_schema.tables ".
						   "WHERE table_schema = '".DB_NAME."' ".
						   "AND table_name = '".$table_name."' ".
						   "LIMIT ? ";
					$result = $db->query($sql, array(1))->fetchArray();	 
					
					if ($result)
					{
						$meta_table_id = $this->get_post("meta_table_id");
						$meta_table = $db->query("SELECT * FROM meta_table WHERE id = ?", array($meta_table_id) )->fetchArray();
						$meta_fields = $db->query("SELECT * FROM meta_table WHERE meta_table_id = ?", array($meta_table_id) )->fetchArray();
				
						$this->create_table($meta_table, $meta_table_id, $meta_fields);	
						$this->create_model_script($meta_table, $meta_table_id, $meta_fields);
						
					$this->create_list_view($meta_table, $meta_table_id, $meta_fields);
					$this->create_entry_view($meta_table, $meta_table_id, $meta_fields);
					$this->create_entry_js($meta_table, $meta_table_id, $meta_fields);
					$this->create_list_js($meta_table, $meta_table_id, $meta_fields);
						
						// Set the deloyed flag to 1
						$sql = "UPDATE ".$table_name." SET deployed = ? WHERE id = ?";
						$param = array(1, $this->get_post("meta_table_id"));
						
						$this->dispatch(array("OUTCOME" => 1,  
											  "ACTION" => "META_TABLE_DEPLOY", 
											  "COMMENT" => $comment ));
					}
				}
				else
				{
					$this->dispatch(array("OUTCOME"=> 0,  
										  "ACTION"=> "META_TABLE_DEPLOY", 
										  "COMMENT" => "Meta table '.$table_name.' already deployed" ));
				}
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "META_TABLE_DEPLOY", "COMMENT" => "Failed : access denied" ));
	}
	
	
	
	private function __META_TABLE_LIST()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_SUPER_ADMIN )
		{
			$sql = "SELECT * FROM meta_table WHERE ?";
			$result = $this->db->query($sql, array(1));
			
			$tabular_output = '<TABLE>'.PHP_EOL.
							   '<TH><TD>Table Name</TD>TD>Tenant ID</TD><TD>Date Defined</TD><TD>Deployed?</TD></TH>';
			foreach($result as $r)
			{
				$deployed = ($r["deployed"] == 1) ? "Y" : "N";
				$tabular_output.="<TR><TD>".$r['table_name']."</TD>TD>".$r["instance_id"]."</TD><TD>".date($r["created"],"d/mY")."</TD><TD>".$deployed."</TD></TR>".PHP_EOL;
			}
			
			$tabular_outout.='</TABLE>';
			
			$this->dispatch(array("OUTCOME" => 1,  
								  "ACTION" => str_replace("__", "" , __FUNCTION__),
								  "TABULAR_OUTPUT" => $tabular_output,
								  "COMMENT" => "Success" ));
		}
		
		$this->dispatch(array("OUTCOME"=> 0, 
							  "ACTION"=> str_replace("__", "" , __FUNCTION__), 
							  "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function __META_TABLE_STRUCTURE()
	{
		global $db;
		if (1)  // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_SUPER_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("meta_table_id")))
			{
				$meta_table_id  = $this->post_field("meta_table_id");
				
				// Get meta_table header
				$meta_table_header = $db->query("SELECT * FROM meta_table WHERE id = ?", array($meta_table_id))->fetchArray();
				
				// Get meta_table fields
				$sql = "SELECT * FROM meta_table_field WHERE meta_table_id = ?";
				$meta_table_fields = $db->query($sql, array($meta_table_id) )->fetchAll();
				
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "META_TABLE_HEADER" => $meta_table_header,
									  "META_TABLE_FIELDS" => $meta_table_fields,
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  
							  "ACTION"=> str_replace("__", "" , __FUNCTION__),
							  "COMMENT" => "Failed : access denied" ));
	}
	
	private function __META_TABLE_DISABLE()
	{
		global $db;
		if (1)  // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_SUPER_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("meta_table_name")))
			{
				$meta_table_name  = $this->post_field("meta_table_name");
				$db->query("UPDATE meta_table SET status = ? WHERE table_name = ?", array(0,$meta_table_id))->fetchArray();

				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "TABLE_NAME" => $meta_table_name,
									  "COMMENT" => "Success" ));
			}

		}
	}
	
	// Meta table use features
	private function meta_record_validation($meta_table_id, $data)
	{
		global $db;
		
		$mandatory_fields = array();
		$default_fields = array();
		
		$email_type = array();
		$date_type = array();
		$datetime_type = array();
		$int_type = array();
		
		// Pull validation information from 
		$sql = "SELECT * FROM meta_field_id WHERE meta_table_id = ?";
		$params = array($meta_field_id);
		$field_descriptions = $db->query($sql, $params)->fetchAll();
		
		// Loop through each record
		foreach(field_descriptions as $field_description)
		{
			if ($field_description->mandatory == 1)
			{
				$mandatory_fields[] = $field_description->field_name;
			}
			
			if ($field_description->default != "")
			{
				$default_fields[$field_description->field_name] = $field_description->default;
			}
			
			/// validation_type	default_value
			switch($field_description->type)
			{
				case "INT":
				case "BOOLEAN":
				$int_type[] = $field_description->field_name;
				break;
				
				case "DATETIME":
				if ( $field_description->validation_type == "DATETIME")
				{
					$email_type[] = $field_description->field_name;
				}

				if ( $field_description->validation_type == "DATE")
				{
					$email_type[] = $field_description->field_name;
				}
				break;
				
				
				case "TINYSTRING":
				if ( $field_description->validation_type == "EMAIL")
				{
					$email_type[] = $field_description->field_name;
				}
				
				if ( $field_description->validation_type == "PHONE" )
				{
					$email_type[] = $field_description->field_name;
				}
				break;
				
				case "STRING":
				break;	
			}
		}
	
		return $validation;
	}
	
	
	
	private function validation_definition($id)
	{
		global $db;
		$keyed_validator = array();
		
		// id	meta_table_id	field_name	field_type	caption	validation_type	default_value	link_field	sequence	mandatory
		$sql = "SELECT id, field_name, field_type, caption, validation_type, default_value, link_field,mandatory ".
			   "FROM meta_table_field WHERE meta_table_id = ? ORDER BY sequence";
		$records = $db->query($sql, array($meta_table_id))->fetchArray();
			
		foreach($records as $record)
		{
			$keyed_validator[$record['field_name']] = $record;
		}
				
		return $keyed_validator;
	}
	
	
	private function __META_TABLE_ADD()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("table_name", "meta_table_id", "data")))
			{
				// get field definition
				$meta_table_id = $this->get_post("meta_table_id");
				
				$sql = "SELECT * FROM meta_table_field WHERE meta_field_id = ? AND status = ?";
				$params = array($meta_table_id, 1);
				$field_definitions = $db->query($sql, $params)->result();
				if (!field_definitions)
				{
					$this->dispatch(array("OUTCOME" => 0,  
										  "ACTION" => str_replace("__", "" , __FUNCTION__), 
										  "COMMENT" => "meta table definition not found" ));
				}
				
				$validation = $this->meta_record_validation($meta_table_id, $field_definitions);
				if (!$validation)
				{
					$place_holders = array();
					$varlist = array();
					$data = $this->get_post("data");
					foreach($data as $key => $value)
					{
						$varlist[] = $key;
						$place_holders[] = ($key != "id") ? " NULL" : "?";
					}
					
					// make insert sql
					$sql = "INSERT INTO ".$table_name."(".join("," , $varlist).") VALUES (".join(",",$place_holders).")";
					$db->query($sql, $param);
					
					$insert_id = $db->lastInsertID();
					
					$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "META_TABLE_ADD", 
									  "INSERT_ID" => $insert_id,
									  "COMMENT" => "Success" ));
				}
				
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "META_TABLE_ADD", 
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> "META_TABLE_ADD", "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function __META_TABLE_UPDATE()
	{
		global $db;
		if (1)  //($this->access_right(META_TYPE_UPDATE) == ACCESS_WRITE )
		{
			
			if (!$this->required_exist( $this->post, array("table_name", "meta_table_id", "row_id", "data_structure")))
			{
				// Get field definition
				$meta_table_id = $this->get_post("meta_table_id");
				//$meta_table_id = $this->get_post("meta_table_id");
				$row_id			 = $this->get_post("row_id");
				$data_structure = $this->get_post("data_structure");
				
				$sql = "SELECT * FROM meta_table_field WHERE meta_table_id = ? AND status = ?";
				$params = array($meta_table_id, 1);
				$field_definitions = $db->query($sql, $params)->result();
				if (!field_definitions)
				{
					$this->dispatch(array("OUTCOME" => 0,  
										  "ACTION" => str_replace("__", "" , __FUNCTION__), 
										  "COMMENT" => "meta table definition not found" ));
				}
				
				$validation = $this->meta_record_validation($meta_table_id, $data_structure);
				if (!$validation)
				{
					$place_holders = array();
					$varlist = array();
					$data = $this->get_post("data");
					foreach($data as $key => $value)
					{
						$varlist[] = $key;
						$place_holders[] = ($key != "id") ? " NULL" : "?";
					}
					
					// make insert sql
					$sql = "INSERT INTO ".$table_name."(".join("," , $varlist).") VALUES (".join(",",$place_holders).")";
					$db->query($sql, $param);
					
					$insert_id = $db->lastInsertID();
					
					$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => "META_TABLE_ADD", 
									  "INSERT_ID" => $insert_id,
									  "COMMENT" => "Success" ));
				}
			if (!$this->required_exist( $this->post, array("collection_type_id", "title", "description", "period", "field_structure")))
			{
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  
							  "ACTION"=> str_replace("__", "" , __FUNCTION__), 
							  "COMMENT" => "Failed : access denied" ));
		}
	}
	
	
	private function __META_TABLE_GET()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array( "meta_table_id", "criteria" )))
			{
				
				// Get validator info...
				// Get 
				
				$records = $this->meta_get_model($meta_table, $criteria);
				
				$this->dispatch(array("OUTCOME" => 1,
									  "RECORDS" => $records,
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> str_replace("__", "" , __FUNCTION__) , "COMMENT" => "Failed : access denied" ));
	}
	

	private function __META_TABLE_GET_BY_ID()
	{
		global $db;
		if (1)  // ($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("meta_table_name", "id")))
			{
				$sql = "SELECT * FROM ".$this->get_post("meta_table_name")." WHERE tenant_id = ? AND id = ? AND status = ?";
				$params = array(STATE::Get_state("TENANT_ID"), $this->get_post("id"), 1);
				$meta_table = $db->query($sql , $params)->fetchArray();
				if ($meta_table)
				{
					$meta_type_id = $meta_table['id'];
					$criteria = array( "id" =>  array(EQUAL, $id));
					$record = $this->meta_get_model($meta_table, $criteria);
					
				
					$this->dispatch(array("OUTCOME" => 1,  
										  "ACTION" => str_replace("__", "" , __FUNCTION__),
										  "TABLE" => $this->get_post("meta_table_name"),
										  "ID" => $this->get_post("id"),
										  "META_TABLE" => 	$meta_table,
										  "VALIDATION_RULES" => $this->validation_definition($meta_type_id),
										  "COMMENT" => "Success" ));
				}
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> str_replace("__", "" , __FUNCTION__) , "COMMENT" => "Failed : access denied" ));
	}

	
	private function __META_TABLE_VALIDATION_RULES()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("meta_table_id")))
			{	
				$this->dispatch(array("OUTCOME" => 1, 
									  "VALIDATION_RULES" => $this->validation_definition( $this->get_post("TENANT_ID")),
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> str_replace("__", "" , __FUNCTION__) , "COMMENT" => "Failed : access denied" ));
	}
	

	private function person_key()
	{
		$person_keys = array();
		$persons = $db->query("SELECT * FROM person WHERE tenant_id = ? ", array($tenant_id))->fetchAll();
		foreach($persons as $id => $person)
		{
			$person_keys[$person['id']] = $person['first_name'].' '.$person['last_name'];
		}
		
		return $person_keys;
	}
	
	private function venue_key()
	{
		$venue_keys = array();
		$venues = $db->query("SELECT * FROM venue WHERE tenant_id = ? AND status = ? ", array($tenant_id, 1))->fetchAll();
		foreach($venues as $venue)
		{
			$venue_keys[$venue['id']] = $venue['venue_name'];
		}
		
		return $venue_keys;
	}
	
	
	private function __META_TABLE_CSV_EXPORT()
	{
		global $db;
		if (1)  //($this->access_right(COLLLECTION_TYPE_UPDATE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("meta_table_id", "clause")))
			{				
				$meta_table_id = $this->get_post("meta_table_id");
				$tenant_id = STATE::Get_state("TENANT_ID");
				$clauses = $this->get_post("clauses");
				
				// Get meta field validation
				$meta_fields = $this->validation_definition($meta_table_id);
				
				$person_keys = $this->person_keys();
				$venue_keys = $this->venue_key();
				
				$meta_rows = array();
				$meta_structure = array();
				$person_field_link = array();
				$venue_field_link = array();
				$venue_field_link = array();
				
				$c = 0;
				$meta_table_header = $db->query("SELECT * FROM meta_table WHERE id = ? AND tenant_id IN (?,?) ", 
												array( $meta_table_id, $tenant_id, -1))->fetchArray();
												
				$sql = "SELECT * FROM ".$meta_table_header['table_name']." WHERE tenant_id = ? ";
				$sqlparts = array();
				
				$parameters[] = $tenant_id;

				$sql = DB_HELPER::add_criteria($sql, $clauses);
				
				$records = $db->query($sql, $parameters)->fetchAll();								
												
				if (($meta_table_header) && ($records))
				{
					foreach($meta_fields as $id => $meta_field)
					{
						$fields_order[$c++] = $meta_field['field_name'];
						if ($meta_field['link_field'] != "")
						{
							switch($meta_field['link_field'])
							{
								case "PERSON":
								$person_field_link[] = $meta_field['field_name'];
								break;
								
								case "VENUE":
								$venue_field_link[] = $meta_field['field_name'];
								break;
								
								/*
								case "DOCUMENT":
								$document_field_link[] = $meta_field['field_name'];
								break;
								*/
							}
						}
					}
					
					$sql = "SELECT * FROM ".$meta_table_header['table_name']." WHERE tenant_id = ? AND status = ?";
					$params = array($tenant_id , 1);
					$rows = $db->query($sql, $params)->result();
					$cells = array();
					$csv = '';
					foreach($rows as $row)
					{
						foreach($fields_order as $field_order)
						{
							if (in_array($field_order['field_name'],$person_field_link))	
							{
								$cells[] = $person_field_link[$field_order['field_value']];
							}
							elseif	(in_array($venue_field_link['field_name'], $person_field_link))	
							{
								$cells[] = $venue_field_link[$field_order['field_value']];
							}
							else
							{
								$cells[] = $row[$field_order['field_name']];
							}
						}
						
						$csv.= join(",", $cells).PHP_EOL;
						$cells = array();
					}
					
				}
				
				$this->dispatch(array("OUTCOME" => 1,  
									  "ACTION" => str_replace("__", "" , __FUNCTION__),
									  "CSV" => $csv,
									  "COMMENT" => "Success" ));
			}
		}
		
		$this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> str_replace("__", "" , __FUNCTION__) , "COMMENT" => "Failed : access denied" ));
	}
	
	
	private function meta_table_exist($table_name)
	{
		global $db;
		
		$sql = "SELECT * FROM meta_table WHERE meta_table_name = ?";
		$found = $db->query($sql, array($table_name))->result();
		
		return $found;
	}
	
	private function __META_TABLE_DELETE()
	{
		global $db;
		if (1)  //($this->access_right(META_TABLE_DELETE) == ACCESS_WRITE )
		{
			if (!$this->required_exist( $this->post, array("meta_table_name", "id")))
			{
				$meta_table_name = $this->get_post("meta_table_name");
				if ($this->meta_table_exist($meta_table_name))
				{
					$sql = "UPDATE ".$meta_table_name." SET status = ? WHERE tenant_id = ?  AND id = ?";
					$param = array(1, STATE::Get_state("TENANT_ID"),$this->get_post("id") );
					$db->query($sql, $param);
				
					$this->dispatch(array("OUTCOME" => 1,  
										  "ACTION" => str_replace("__", "" , __FUNCTION__),
										  "COMMENT" => "Success" ));
				}
			}
		
		 $this->dispatch(array("OUTCOME"=> 0,  "ACTION"=> str_replace("__", "" , __FUNCTION__) , "COMMENT" => "Failed : access denied" ));
		}
	}

	
	// -----------------------------------------------------------------------------------------------------------------------------------------------
	
	private function course_exist($course_code)
	{
		global $db;
		$sql = "SELECT * FROM ".TBL_COURSE." WHERE tenant_id = ? AND course_code  = ? AND status = ? ";
		$params = array( STATE::Get_state("TENANT_ID") , $course_code ,1);
		$result = $this->db->query($sql, $params)->result();
		return $result;
	}
	
		// course : id, tenant_id, created, created_by, status, course_code, course_name, course_description, subject_id, level_id, qualifcation_id
	// module:      id, tenant_id, created, created_by, status, course_id, module_number, module_name
	// sub_module:	id, tenant_id, created, created_by, status, course_id, module_id, sub_module_name, order
	
	// add_course_outline
	private function __COURSE_ADD_OUTLINE()
	{
		global $db;
		
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_title", "course_code", "subject_id", "module_definition")))
			{
				$course_title = $this->get_post("course_title");
				$course_code = $this->get_post("course_code");
				$course_description = $this->get_post("course_description");
				
				$subject_id = $this->get_post("subject_id");
				$level_id = $this->get_post("subject_id");
				$qualification_id = $this->get_post("subject_id");
				
				$module_definition = $this->get_post("module_definition");
				
				// Does course exist?
				if (!$this->course_exist($course_code))
				{
					// Create course
					// id, tenant_id, status, created_by, created, course code, course name, $corse_desciption, subject_id, $qualification_id
					$sql = "INSERT INTO course (id, tenant_id, created,	created_by,	status,	course_code, course_title,	course_description,	subject_id,	level_id,	qualification_id) ".
						   "VALUES (?,?,?, ?,?,?, ?,?,?,?,?)";
					$param = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $course_code, $course_title,	$course_description,	$subject_id,	$level_id,	$qualification_id   );
					$db->query($sql, $param);
					$insert_id = $db->lastInsertID();

					// Insert modules outline
					$module_number = 1;
					forech($module_definition as $module_name => $submodules)
					{
						$sql = "INSERT INTO ".TBL_COURSE_MODULE." (id, tenant_id,	created, created_by,	status,	course_id,	module_number,	module_name) VALUES (?,?,?,?,?,?,?,?)";
						$params = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $insert_id, $mobile_number, $module_name );
						$db->query($sql, $params);
						$module_id = $db->lastInsertID();
					
						$sub_module_number = 1;
						foreach($sub_modules as $sub_module)
						{
							$sql = "INSERT INTO ".TBL_COURSE_SUB_MODULE." (id,	tenant_id,	created,	created_by,	status,	course_id,	module_id,	sub_module_name,	sub_module_order) VALUES (?,?,? , ?,?,? , ?,?,? )";
							$params = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $sub_module_number );
							$db->query($sql, $params);
							
							$sub_module_number++;
						}
					}
				}

			}
		}
	}
	
	
	private function __COURSE_INSTALL()
	{
		global $db;
		
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_title", "course_code", "subject_id", "module_definition")))
			{
				$course_title = $this->get_post("course_title");
				$course_code = $this->get_post("course_code");
				$course_description = $this->get_post("course_description");
				
				$subject_id = $this->get_post("subject_id");
				$level_id = $this->get_post("subject_id");
				$qualification_id = $this->get_post("subject_id");
				
				$module_definition = $this->get_post("module_definition");
				
				// Does course exist?
				if (!$this->course_exist($course_code))
				{
					// Create course
					// id, tenant_id, status, created_by, created, course code, course name, $corse_desciption, subject_id, $qualification_id
					$sql = "INSERT INTO course (id, tenant_id, created,	created_by,	status,	course_code, course_title,	course_description,	subject_id,	level_id,	qualification_id) ".
						   "VALUES (?,?,?, ?,?,?, ?,?,?,?,?)";
					$param = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $course_code, $course_title,	$course_description,	$subject_id,	$level_id,	$qualification_id   );
					$db->query($sql, $param);
					$insert_id = $db->lastInsertID();

					// Insert modules outline
					$module_number = 1;
					forech($module_definition as $module_name => $submodules)
					{
						$sql = "INSERT INTO ".TBL_COURSE_MODULE." (id, tenant_id,	created, created_by,	status,	course_id,	module_number,	module_name) VALUES (?,?,?,?,?,?,?,?)";
						$params = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $insert_id, $mobile_number, $module_name );
						$db->query($sql, $params);
						$module_id = $db->lastInsertID();
					
						$sub_module_number = 1;
						foreach($sub_modules as $sub_module)
						{
							$sql = "INSERT INTO ".TBL_COURSE_SUB_MODULE." (id,	tenant_id,	created,	created_by,	status,	course_id,	module_id,	sub_module_name,	sub_module_order) VALUES (?,?,? , ?,?,? , ?,?,? )";
							$params = array(NULL, STATE::Get_state("TENANT_ID"), date(), STATE::Get_state("TENANT_ID"), 1, $sub_module_number );
							$db->query($sql, $params);
							
							$sub_module_number++;
						}
					}
				}

			}
		}
	}
	
	// get coutse_outline
	private function __COURSE_GET_OUTLINE()
	{
		global $db;
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_code")))
			{
				$sql = "SELECT * FROM ".TBL_COURSE." WHERE tenant_id = ? AND status = ? AND course_code = ? ";
				$params = array( STATE::Get_state("TENANT_ID") ,1, $course_code);
				$course = $db->query($sql, $params)->fetchArray();
				
				$module_strucuture = array();
				if ($course)
				{
					$sql = "SELECT * FROM ".TBL_COURSE_MODULE." WHERE tenant_id = ? AND status = ? AND course_id = ? ";
					$param = array( STATE::Get_state("TENANT_ID") ,1, $course['course_id']);
					$modules = $db->query($sql, $params)->fetchAll();
					
				}
			}
		}
	}
	
	// disable_course
	private function __COURSE_STATUS()
	{
		global $db;
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_name")))
			{
				if (!$this->required_exist( $this->post, array("status")))
				{
					// update status
					$sql = "UPDATE ".TBL_COURSE." SET status = ? WHERE tenant_id = ?";
					$param = array(1, STATE::Get_state("TENANT_ID"));
					$db->query($sql, $params);
				}
				
				//
			}
		}
		
		//
	}	
	
	
	private function __COURSE_GET_LESSON()
	{
		global $db;
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_name")))
			{
				if (!$this->required_exist( $this->post, array("status")))
				{
					// update status
					$sql = "UPDATE ".TBL_COURSE." SET status = ? WHERE tenant_id = ?";
					$param = array(1, STATE::Get_state("TENANT_ID"));
					$db->query($sql, $params);
				}
				
				//
			}
		}
		
		//
	}
	
	// get slide
	private function __COURSE_GET_LESSON_SLIDE()
	{
		global $db;
		if (1)  //($this->access_right(COURSE_ACCESS) == ACCESS_ADMIN )
		{
			if (!$this->required_exist( $this->post, array("course_name")))
			{
				if (!$this->required_exist( $this->post, array("status")))
				{
					// update status
					$sql = "UPDATE ".TBL_COURSE." SET status = ? WHERE tenant_id = ?";
					$param = array(1, STATE::Get_state("TENANT_ID"));
					$db->query($sql, $params);
				}
				
				//
			}
		}
		
		//
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
		
		$login_id = $this->get_post('login_id');
		$token = $this->get_post('token');
		
		if (STATE::validate_token( $token, $login_id))
		{
			STATE::load_configuration();
			
			if (!method_exists($this, '__'.$action ))
			{
				$this->dispatch();
			}
			
			//	invoke method	
			$result = $this->{'__'.$action}();
		}
		else
		{	

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
