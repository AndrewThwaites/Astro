<?php
define ("SLASH", "/");
include_once("config" .SLASH. "define.php");
include_once("config".SLASH. "config.php");
include_once("core".SLASH. "db.php");
require_once("core". SLASH. "utils.php");

#require_once(__CORE__DIR__ . "bottle.php");
#require_once(__CORE__DIR__ . "validation.php");


function build_tables($tables)
{
	$template = "CREATE TABLE `<TABLE_NAME>` ( ".PHP_EOL.
				"<FIELDS> ".PHP_EOL.
				") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;".PHP_EOL
				"ALTER TABLE `<TABLE_NAME>` ".PHP_EOL.
				"ADD PRIMARY KEY (`<PRIMARY_KEY>`);".PHP_EOL.PHP_EOL;
	
	$sql_table_content = '';
	
	foreach($tables as $table_name => $table_fields)
	{
		$primary_key = explode(",",table_fields[0]);
		
		$table_create_query = db_definition_template();
		$table_create_query = str_replace("<TABLE_NAME>" , $table_name , $table_create_query);
		$table_create_query = str_replace("<PRIMARY_KEY>" , $primary_key[0] , $table_create_query);
		
		$field_sql = '';
		foreach($table_fields as $table_field)
		{
			// {fieldname,type,caption,formtype,mandatory,validation type}
			$field = explode(",",$table_fields[0]);
			switch($field[1])
			{
				case "int":
				$field_sql[] = "`".$field[0]."` int(11) NOT NULL";
				break;
				
				case "tinyint":
				$field_sql[] = "`".$field[0]."` tinyint(4) NOT NULL";
				break;
				
				// `created` timestamp NOT NULL DEFAULT current_timestamp()
				case "timestamp":
				$field_sql[] = "`".$field[0]."` timestamp NOT NULL DEFAULT current_timestamp()";
				break;
				
				case "datetime":
				$field_sql[] = "`".$field[0]."` datetime NOT NULL";
				break;
				
				case "text":
				$field_sql[] = "`".$field[0]."` text NOT NULL";
				break;
				
				case "tinytext":
				$field_sql[] = "`".$field[0]."` tinytext NOT NULL";
				break;
			}
			
			$table_create_query.= str_replace("<FIELD_LIST>", join(PHP_EOL.",", $field_sql) , $table_create_query );
		}
		
		return $table_create_query;
	}
}
build_tables($tables);




function build_models($tables)
{
$template_model =<<EOL
<?php
class <MODEL_NAME>
{

	public static List($dataVessal, $data)
	{
		global $db;
		global $errorMessage;
		global $fieldList;
		
		$Limit = (!empty(($data['limit'])) ? $data['limit'] : 20;
		$offset = (!empty(($data['limit'])) ? $data['limit'] : 0;
		$params = array();
		
		$sql = "SELECT * FROM <TABLE> WHERE 1 ";
		
		$likes = array();
		$date_range = array();
		
		if ($data['likes'])
		{
			foreach($sata['likes'] as $k => $v)
			{
				$likes[] = " ".$k."= ?";
				$params[] = "%".$v."%";
			}
		}
		
		if ($data['equals'])
		{
			foreach($sata['equals'] as $k => $v)
			{
				$likes[] = " ".$k."= ?";
				$params[] = $v;
			}
		}
		
		if ($data['date_range'])
		{
			foreach($sata['date_range'] as $k => $v)
			{
				$date_range[] = " BETWEEN ? AND ? ";
				$dates = explode("," , $v);
				$params[] = $dates[0];
				$params[] = $dates[1];
			}
		}
		
		$sql = $sql.join(" AND " , $likes )." ".join(" AND " , $likes )." ".join(" AND ", $date_range);
		
		if ($offset)
		{
			$sql.= " OFFSET ?, LIMIT ?";
			$param[] = $offset;
			$param[] = $limit;
		}
		else
		{
			$sql.= " LIMIT ?";
			$param[] = $limit;
		}
		
		$result = $db->query($sql , $params)->fetchall();
		return $result;
	}
	
	
	public static delete($dataVessal, $data)
	{
		global $db;
		global $errorMessage;
		global $fieldList;

		$sql = "UPDATE <TABLE> SET status = ? WHERE <PRIMARY_KEY> = ?";
		$db->query($sql, array(0,$data['<PRIMARY_KEY>']));
	}
	
	
	public static update($dataVessal, $data)
	{
		global $errorMessage;
		global $fieldList;
		global $db;
		
		if($data['field_list'])
		{	
			$params = array();
			$sql = "UPDATE <TABLE> SET";
			$sub_qry= array();
			foreach($data['field_list'] as $field => $field_value)
			{
				$sub_qry[] = "$".$field." = ?"; 
			}
			
			$sql.=" WHERE <PRIMARY_KEY> = ?";
			$params[] = $data['primary_key'];
		}
		
	}
	
	public static create($dataVessal, $data)
	{
		global $errorMessage;
		global $fieldList;
		global $db;
		
		$field_names = array();
		$q = array();
		$params = array();

		foreach($data $field_name => $field_value)
		{
			$field_names[] = $field_name;
			$params[] = $field_value;
			$q[] = '?';
		}
		
		$sql = "INSERT INTO <TABLE> (".$field_names.") VALUES (".$q.")";
		$db->query($sql, $params);
	}
	
	
	public static edit($dataVessal, $tenant_id, $primary_key)
	{
		global $errorMessage;
		global $fieldList;
		$db;
		
		$aql = "SELECT * FROM <TABLE> WHERE tenant_id = ? AND <PRIMARY_KEY>= ? ";
		$result = $db->query($sql, array($tenant_id , $primary_key))->fetchone();
		return $result;
	}	
}	
?>
EOL;
	
	foreach($tables as $table_name => $table_fields)
	{
		$primary_key = explode(",",table_fields[0]);
		$table_create_query = db_definition_template();
		$table_create_query = str_replace("<TABLE_NAME>" , $table_name , $table_create_query);
		$table_create_query = str_replace("<PRIMARY_KEY>" , $primary_key[0] , $table_create_query);
		
		$field_sql = '';
		$myfile = fopen( $table_name . ".php", "w");
			
		$template = $template_model;
		$template = str_replace("<TABLE>", $tables, $template);
		$template = str_replace("<MODEL_NAME>", strtoupper($tables)."_MODEL", $template);
		$template = str_replace("<PRIMARY_KEY>", $primary_key , $template);
			
		fputs($myfile, $template);
	}
}

build_models($tables);