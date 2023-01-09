<?php

class UTILS
{
	/*
	 *
	 */
	public static function createToken($length)
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
	public static function isSuperAdmin($accountId)
	{
		global $db;
		$admin = false;
		
		$sql = "SELECT * FROM person WHERE tenant_id = ? AND id = ?";
		$params = array(STATE::$tenant_id , $accountId);
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
		
		echo  ($title) ? '<h1>'.$title.'</h1>' : '';
		echo '<xmp>';
		print_r($vars);
		echo '</xmp>';
	}


	public static function testPlanAction($actionId)
	{
		global $db;
		
		$sql = "SELECT * FROM tp_entry WHERE id = ?";
		$result = $db->query($sql, array($actionId))->fetchAll();
		
		$post_data = ($result) ? '' : json_encode($result['output_data']);
		return $post_data;
	}

	public static function addTestplanAction($featureId, $tpLabel, $testTitle, $testDescription, $inputData, $outputData, $sequence, $tpSequence )
	{
		global $db;
		
		$sql = "INSERT INTO tp_feature_test (id, feature_id, tp_label,execution_time, created, input_data, output_data) VALUES (?,?,?,?,?,?,?)";
		$param = array(NULL, $feature_id, $test_title, $test_description, "0000-00-00 00:00:00", $input_data, $output_data, $sequence, $tp_sequence ,0, 0);
		$result = $db->query($sql, $param);
	}
	
	/**
	 *  modify_primary_key
	 */
	private static function makeTable($table, $fields)
	{
		$primmaryKeyParts = explode("," , $fields[0]);
		$primaryKey = $primmaryKeyParts[0];
		
		$sql = 'CREATE TABLE `tp_feature` ('.PHP_EOL.
			   '{%FIELDS%}'.
			   ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
		
		$sqlSubstr = array();
		foreach($fields as $field)
		{
			$fieldParts = explode("," , $field);
			$sqlLineTemplate = "`<%FIELD_NAME%>` <%FIELD_TYPE%> NOT NULL";
			
			if (in_array($fields[1] , array('int', 'tinyint', 'tinytext', 'text' ,'datetime') ))
			{
				if ($fields[1] == 'int') $fields[1] = 'int(11)';
				$sqlLine = $sql_line_template;
				$sqlLine = str_replace("<%FIELD_TYPE%>", $fields[1], $sql_line);
				$sqlLine = str_replace("<%FIELD_NAME%>", $fields[0], $sql_line);
				$sqlSubstr[] = $sql_line;
			}
			
		}
		$sql = str_replace( "{%FIELDS%}", join("," , $sql_substr), $sql );
		
		$sql.= '	ALTER TABLE `'.$table.'` '.PHP_EOL.' ADD PRIMARY KEY (`'.$primary_key.'`);'.PHP_EOL.PHP_EOL;		
		$sql.= 'ALTER TABLE `'.$table.'` '.PHP_EOL.'MODIFY `'.$primary_key.'` int(11) NOT NULL AUTO_INCREMENT;';				
		
		return $sql;
	}  


	
	public static function buildTable($table_definitions)
	{
		if ($tableDefinitions)
		{
			foreach($tableDefinitions as $tableName => $fieldDefinitions)
			{
				if ($field_definitions)
				{
					self::make_table($tableName , $fieldDefinitions);
				}
			}
		}	
	}
	
	
	public static function makeATable($table, $fields)
	{
		echo '<h1>'.$table.'</h1>';
		
		global $db;
		
		$primmaryKeyParts = explode("," , $fields[0]);
		$primary_key = $primmaryKeyParts[0];
		
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