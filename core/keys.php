<?php

/*
		$tables["key"] = array("id,int,,hidden,yes,intval", 
						   "status,tinyint,status,select,yes,none", 
						   "created,datetime,Created,hidden,yes,none", 
						   "closed_set_type_id,int,,hidden,yes,intval", 
						   "tenant_id,int,,hidden,yes,intval", 
						   "resolved_role_type_id,,", 
						   "role_id,int,,hidden,yes,intval", 
						   "data_key,tinytext,Data key,text,yes,none", 
						   "data_value,tinytext,Data Value,text,yes,none");								
								
	$tables["key_type_role"] = array("id, int,,hidden,yes,intval", 
									 "tenant_id,int,,hidden,yes,intval", 
									 "key_type_id,int,,hidden,yes,intval", 
									 "resolved_role_type_id,int,,hidden,yes,intval");
*/


class KEYS
{
	
	/**
	 *	add_key_type
	 *
	 *	@Param string key_type_name
	 */
	private static function add_key_type($key_type_name)
	{
		global $db;
		$sql = "INSERT INTO key_type (id, status, key_type_name) VALUES (NULL, 1, '$key_type_name')";
		$db->query($sql);
		return $db->lastInsertID();
	}
	
	
	/**
	 *	add_key_value
	 *
	 *	@Param	int tenant_id
	 *	@Param	int key_type_id
	 *	@Param	int resolved_role_id
	 *	@Param	string data_key
	 *	@Param	string data_value
	 */
	private static function add_key_value( $tenant_id, $key_type_id, $resolved_role_id, $data_key, $data_value)
	{
		global $db;
		$created = date("y-m-d h:i:s");
		$data_key = addslashes($data_key);
		$data_value = addslashes($data_value);
		
		$sql = "INSERT INTO key_entry (id, created, created_by, modified, modified_by, status, tenant_id, key_type_id, resolved_type_id, data_key, data_value ) ".
			   " VALUES (null, '$created', '1', '$created','1', '1', '$tenant_id', '$key_type_id', '$resolved_role_id', '$data_key', '$data_value' )";
		$db->query($sql);
	}
	
	
	/**
	 *	bulk_insert
	 *
	 *	@Param	string key_name
	 *	@Param	string key_description
	 *	@Param	int tenant_id
	 *	@Param	int	resolved_role_type_id
	 *  @Param	array $data
	 */
	public static function bulk_insert($key_name, $key_description, $tenant_id, $role_id, $data)
	{
		global $db;
		
		// Create key type
		$key_type_id = self::add_key_type($key_name, $key_description);
		
		// Create key options
		$data = explode("," , $data);
		$data_key = 1;
		
		UTILS::debug($data);
		
		foreach($data as $indice => $datum)
		{
			self::add_key_value( $tenant_id, $key_type_id, -1, $data_key, $datum);
			$data_key++;
		}
	}
	
	
	/**
	 *	bulk_key_insert
	 *
	 */
	public static function bulk_key_insert($key_name, $key_description, $tenant_id, $role_id, $data)
	{
		global $db;
		
		// Create key type
		$key_type_id = self::add_key_type($key_name, $key_description);
		
		// Create key options
		$data = explode("," , $data);
				
		//$key_val = 1;
		foreach($data as $datum)
		{
			$cells = explode(":", $datum);
			
			UTILS::debug($cells);
			
			self::add_key_value( $tenant_id, $key_type_id, -1, trim($cells[0]), trim($cells[1]));
			//$data_key++;
		}	
	}

	
	/**
	 * get_key
	 * @param int tenant_id
	 * @param string key
	 * @param int role_type_id 
	 *
	 */
	public static function get_key($key_id)
	{
		global $db;
		$key = array();
		$resolved_role_type_id = -1;
		
	/*	if (STATE::$role_type_id != -1)
		{
			$sql = "SELECT * FROM role_key WHERE tenant_id = ? AND key_id = ? AND role_id = ? ";
			$result = $db->query($sql, array(STATE::$tenant_id, $key_id, STATE::$role_type_id))->fetch();
			if ($result)
			{
				$resolved_role_type_id = $result[0]->resolved_role_type_id;
			}
			else
			{
				// log bug of missing role type
				// file, function, login_id, vars
			}
		}*/
	
		$sql = "SELECT * FROM ".TBL_KEY." WHERE tenant_id = ? AND key_type_id = ? AND lang = ?";	
		$attempts = array(/*array(STATE::$tenant_id, $resolved_role_type_id, STATE::$language), 
						  array(STATE::$tenant_id, -1, STATE::$language ), 
						  array(-1,$key_id, STATE::$language),*/
						  array(-1, $key_id, "EN") );
						  
		foreach($attempts as $attempt)
		{
			$rows = $db->query($sql, $attempt)->fetchAll();#
			
			if ($rows)
			{
				foreach($rows as $indice => $row)
				{				
					$key[$row['data_key']] = $row['data_value'];
				}
				
			}
		}
	
		return $key;
	}
}

