<?php


class CRUD
{
	/**
	 * Insert
	 *
	 * @param $table
	 * @param $fields
	 */
	public function insert($table, $fields)
	{
		global $db;
		
		$params = array();
		$fields = array();
		$placeholders = array();

		foreach($fields as $field => $value)
		{
			$placeholders[] = '?';
			$fields[] = $field;
			$params[] = $value;
		}
		
		$sql = "INSERT INTO ".$table." (".join(",",$fields)." ) VALUES (".$placeholders.")";
		$db->query($sql, $params);
	}
	
	
	/**
	 *	Update 
	 *  @param string table_name
	 *  @param int id
	 *  @param array conditions
	 */
	public function update($table_name, $id, $conditions)
	{
		global $db;
		
		$params = array();
		$subsql = array();
		foreach($conditions as $field => $value)
		{
			$subsql[] = " ".$field."=?";
			$params[] = $value;
		}
		$sql=  "UPDATE ".$table_name." SET ".join("," , $subsql)." WHERE ";
		$params[] = $id;
		
		$db->query($sql, $params);
	}
	
	
	/**
	 * Delete
	 * @param string table_name
	 * @param array conditions
	 */
	public function delete($table_name, $conditions)
	{
		global $db;
		
		$sql = "UPDATE ".$table_name." SET status = ? WHERE ";
		$params[] = 0;
		
		$subsql = array();
		foreach($conditions as $field => $value)
		{
			$subsql[] = " ".$field."=?";
			$params[] = $value;
		}
		
		$sql.=join("," , $subsql);
		$db->query($sql, $params);
	}
	
	
	/**
	 * Get
	 *
	 * @param string table
	 * @param array / boolean field_list
	 * @param array params 
	 */
	public function get($table, $field_list, $conditions, $offset , $limit)
	{
		global $db;
		
		$params = array();
		
		$sql = "SELECT ";
		if ($field_list == false) 
		{
			$sql.=" count(*) as 'COUNT' ";
		} else {
			$sql.= join(",", $field_list);
		}
		
		if( $conditions )
		{
			foreach($conditions as $condition_type => $condition)
			{
				switch($condition_type)
				{
					case "BETWEEN":
					$sql.= "BETWEEN ? AND ?";
					$p = explode("|", $condition`);
					$params[]= $p[0];
					$params[] =$p[1];
					break;
							
					case "EQUAL":
					break;
							
					case "GREATER_THAN":
					break;
							
					case "LESS_THAN":
					break;
							
					case "IN":
					break;
				}
			}
		}
		
		if ($field_list)
		{
			$sql.= " OFFSET ?, LIMIT ?";
			$params[] = $offset;
			$params[] = $limit;
		}
		
		$result = $db->query($sql, $params)->result();
		return $result;
	}
}