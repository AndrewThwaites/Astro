<?php

class UTILS
{
	/*
	 *
	 */
	public static function create_token($length)
	{
		$characters = "0123456789ABCDEFGHIJKLMNOQRSTUVWXYZ";
		$token = "";
		
		while (strlen($token)<$length)
		{ 
			$index = rand(0, strlen($characters) - 1); 
			$token .= $characters[$index]; 
		} 
		
		return $token;
	}
	
	
	/*
	 *
	 */
	public static function is_super_admin($account_id)
	{
		global $db;
		$admin = false;
		
		$sql = "SELECT * FROM person WHERE tenant_id = ? AND id = ?";
		$params = array(STATE::tenant_id , $account_id);
		$result = $db->query($sql, $params)->fetchone();
		if ($result) {
			
			if ($result['access_role'] == 1) {
				$admin = true;
			}				
		}
		
		return $admin;
	}
	
	
	/**
	 *
	 */
	public static function debug($title, $vars)
	{
		global $db;
		
		if ($title)
		{
			echo '<h1>'.$title.'</h1>';
		}
		
		echo '<xmp>';
		print_r($vars);
		echo '</xmp>';
	}


	public static function test_plan_action($action_id)
	{
		global $db;
		
		$sql = "SELECT * FROM tp_entry WHERE id = ?";
		$result = $db->query($sql, array($action_id))->fetchAll();
		
		$post_data = ($result) ? '' : json_encode($result['output_data']);
		return $post_data;
	}

	public static function add_test_plan_action($feature_id, $tp_label, $test_title, $test_description, $input_data, $output_data, $sequence, $tp_sequence )
	{
		global $db;
		
		$sql = "INSERT INTO tp_feature_test (id, feature_id, tp_label,execution_time, created, input_data, output_data) VALUES (?,?,?,?,?,?,?)";
		$param = array(NULL, $feature_id, $test_title, $test_description, "0000-00-00 00:00:00", $input_data, $output_data, $sequence, $tp_sequence ,0, 0);
		$result = $db->query($sql, $param);
	}
	
	/**
	 *  modify_primary_key
	 */
	private static function make_table($table, $fields)
	{
		$primmary_key_parts = explode("," , $fields[0]);
		$primary_key = $primmary_key_parts[0];
		
		$sql = 'CREATE TABLE `tp_feature` ('.PHP_EOL.
			   '{%FIELDS%}'.
			   ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
		
		$sql_substr = array();
		foreach($fields as $field)
		{
			$field_parts = explode("," , $field);
			$sql_line_template = "`<%FIELD_NAME%>` <%FIELD_TYPE%> NOT NULL";
			
			if (in_array($fields[1] , array('int', 'tinyint', 'tinytext', 'text' ,'datetime') ))
			{
				if ($fields[1] == 'int') $fields[1] = 'int(11)';
				$sql_line = $sql_line_template;
				$sql_line = str_replace("<%FIELD_TYPE%>", $fields[1], $sql_line);
				$sql_line = str_replace("<%FIELD_NAME%>", $fields[0], $sql_line);
				$sql_substr[] = $sql_line;
			}
			
		}
		$sql = str_replace( "{%FIELDS%}", join("," , $sql_substr), $sql );
		
		$sql.= '	ALTER TABLE `'.$table.'` '.PHP_EOL.' ADD PRIMARY KEY (`'.$primary_key.'`);'.PHP_EOL.PHP_EOL;		
		$sql.= 'ALTER TABLE `'.$table.'` '.PHP_EOL.'MODIFY `'.$primary_key.'` int(11) NOT NULL AUTO_INCREMENT;';				
		
		return $sql_fragment;
	}  


	
	public static function build_table($table_definitions)
	{
		if ($table_definitions)
		{
			foreach($table_definitions as $table_name => $field_definitions)
			{
				if ($field_definitions)
				{
					self::make_table($table_name , $field_definitions);
				}
			}
		}	
	}
	
	
	public static function make_a_table($table, $fields)
	{
		echo '<h1>'.$table.'</h1>';
		
		global $db;
		
		$primmary_key_parts = explode("," , $fields[0]);
		$primary_key = $primmary_key_parts[0];
		
		$sql = 'CREATE TABLE '.$table.' ('.PHP_EOL.
			   '{%FIELDS%}'.
			   ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
		
		$sql_substr = array();
		foreach($fields as $field)
		{
			$field_parts = explode(",", $field);

			$sql_line_template = " {%FIELD_NAME%} {%FIELD_TYPE%} NOT NULL".PHP_EOL;
			
			if (in_array($field_parts[1] , array('int', 'tinyint', 'tinytext', 'text' ,'datetime') ))
			{
				if ($field_parts[1] == 'int') $fields[1] = 'int(11)';
				$sql_line = $sql_line_template;
				$sql_line = str_replace("{%FIELD_TYPE%}", $field_parts[1], $sql_line);
				$sql_line = str_replace("{%FIELD_NAME%}", $field_parts[0], $sql_line);
				$sql_substr[] = $sql_line;
			}
			
		}
		
		$sql = str_replace( "{%FIELDS%}", join("," , $sql_substr), $sql );
		
		echo $sql.PHP_EOL.PHP_EOL;
		
		$sql = '	ALTER TABLE `'.$table.'` '.PHP_EOL.' ADD PRIMARY KEY (`'.$primary_key.'`);'.PHP_EOL.PHP_EOL;
	//	echo '<br>-------<br>'.PHP_EOL.$sql.PHP_EOL.PHP_EOL;
		
		$sql = 'ALTER TABLE `'.$table.'` '.PHP_EOL.'MODIFY `'.$primary_key.'` int(11) NOT NULL AUTO_INCREMENT;';				
	//	echo '<br>-------<br>'.PHP_EOL.$sql.PHP_EOL.PHP_EOL;
	}  
}