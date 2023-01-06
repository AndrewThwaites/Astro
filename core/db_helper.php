<?php

class DB_HELPER
{
	public function add_criteria($sql, $criteria)
	{
		foreach($criteria as $clause)
		{
			$inequality = $clause[1];
			$field_name = $clause[0];
					
			switch($inequality)
			{
				case NOT_EQUAL:
				$sqlparts[] = " ".$field_name." not equal ? ";
				$parameters[] = $clause[1];
				break;
						
				case EQUAL:
				$sqlparts[] = " ".$field_name." = ? ";
				$parameters[] = $clause[1];
				break;
						
				case LESSTHAN:
				$sqlparts[] = " ".$field_name." < ? ";
				$parameters[] = $clause[1];
				break;
						
				case GREATERTHAN:
				$sqlparts[] = " ".$field_name." > ? ";
				$parameters[] = $clause[1];
				break;
						
				case LESSOREQUAL:
				$sqlparts[] = " ".$field_name." > ? ";
				$parameters[] = $clause[1];
				break;
						
				case GREATEROREQUAL:
				$sqlparts[] = " ".$field_name." > ? ";
				$parameters[] = $clause[1];
				break;
						
				case IN:
				if (count($parameters)>3)
				{
					$qs = array();
					$ps = $parameters;
					array_shift($ps);
					array_shift($ps);
							
					foreach($ps as $p)
					{
						$qs[] = '?';
						$parameters[] = $p;
					}
							
					$sqlparts[] = " ".$field_name." IN (".join(",",$qs).") ";
				}
				break;
						
				case NOTIN:
				if (count($parameters)>3)
				{
					$qs = array();
					$ps = $parameters;
					array_shift($ps);
					array_shift($ps);
							
					foreach($ps as $p)
					{
						$qs[] = '?';
						$parameters[] = $p;
					}
							
					$sqlparts[] = " ".$field_name." IN (".join(",",$qs).") ";
				}	
				break;
			}
		}
	
		if ($sqlparts)
		{
			$sql.=" WHERE ".join("AND ", $sqlparts);
		}
		
		return $sql;
	}

}