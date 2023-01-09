<?php
require_once("core". SLASH. "utils.php");

class STATE
{
	public static $tenant_id;
	public static $login_id;
	public static $role_type_id;
	public static $token;
	public static $language;
	public static $access_right = array();
	public static $configuration = array();
	public static $state;
	
	public function init() 
	{
        self::$tenant_id = -1;
		self::$login_id = -1;
		self::$role_type_id = -1;
		self::$token = FALSE;
		self::$state = 0;
		self::$language = "EN";
    }
	
		
	
	/**
	 * load_configuration
	 */
	public static function loadConfiguration()
	{
		global $db;
		//	id,	tenant_id,	created, created by, status, key, value 
		$confs = $db->query('SELECT * FROM core_configuration WHERE tenant_id = ? AND status = ?', array(self::$tenant_id, 1))->fetchArray();
		foreach($confs as $conf)
		{
			self::$configuration[$conf['key']] = $conf['value'];
		}
	}
	
	public static function getState($state)
	{
		switch($state)
		{
			case "tenant_id":
			return self::$tenant_id;
			break;
		}
	}
	
	
	/**
	 *	get_config
	 *	@param $key
	 */
	public static function getConfig($key)
	{
		UTILS::debug(false, self::$configuration);
		
		return (!empty(self::$configuration[$key])) ? self::$configuration[$key] : false;
	}

	
	
	public static function accessRight($feature)
	{
		return (!empty(self::$access_right[$feature])) ? self::$access_right[$feature] : 0;
	}

	
	public static function obtainToken($user_name , $token)
	{
		$sql = "SELECT * FROM token WHERE user_name = ? AND token = ?";
		$row = $db->query($sql ,  $user_name, $token)->fetchArray();
		if ($row)
		{
			self::$token = $token;
		}
		else
		{
			self::$state = -1;
		}
	}
	
	
	/**
	 *	Login
	 *
	 *	@Param $username
	 *	@Param $login_id
	 *
	 */
	public static function validateToken( $token, $login_id)
	{
		global $db;
		
		$sql = "SELECT * FROM ".TBL_TOKEN." WHERE token = ? AND login_id = ?";
		$row = $db->query($sql, $token, $login_id )->fetchArray();
		if ($row)
		{
			self::$token = $token;
		}
		else
		{
			self::$state = -1;
			return;
		}
		
		$sql = "SELECT * FROM ".TBL_PERSON." WHERE id = ? AND status = ?";
		$result = $db->query($sql , array($login_id , 1))->fetchArray();
		
		if ($result)
		{
			self::$tenant_id = $result["tenant_id"];
			self::$login_id = $login_id;
			self::$role_type_id = $result["role_type_id"];
			self::$state = 1;
			
			// Write access log success	{id, login_id, username, datetime, result}
			$sql = "INSERT INTO ".TBL_LOGIN_HISTORY." ( login_id, username, created, result) VALUES (?,?,?,?) ";
			$db->query($sql, array( $result['id'], $result['email_address'], date("Y-m-d h:i:s"), 1 ));
			
			for ($f=1;$f<32;$f++)
			{
				self::$access_right[$f] = 10;
			}
			
			// Access rights: id, status, created, role_type_id, feature, level
			$sql = "SELECT * FROM ".TBL_ACCESS_RIGHTS." WHERE tenant_id = ? AND role_type_id = ?";
			
			$access_rows = $db->query($sql , self::$tenant_id, self::$role_type_id )->fetchArray();
			if($access_rows)
			{
				foreach($access_rows as $access_row)
				{
					self::$access_right[$access_row["feature"]] = $access_row["level"];
				}
			}
			
			return true;
		}
		else
		{
			self::$state = 0;
			
			$sql = "INSERT INTO ".TBL_LOGIN_HISTORY." (login_id, username, created, result) VALUES (?,?,?,?) ";
			$db->query($sql, array( 0, $username, date("Y-m-d h:i:s"), 0 ));
		}
		
		return false;
	}
}
STATE::init();