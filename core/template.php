<?php

class TEMPLATE
{
	public function setPageElements($tenantId, $page, $lang, $elements)
	{
		global $db;
		
		// Does it exist?
		$sql = "SELECT * FROM language WHERE tenant_id  ? AND page = ? AND lang =? ";
		$params = array($tenantId, $page, $lang);
		$record = $db->query($sql , $params )->result();
		if ($record)
		{
			$sql = "UPDATE language SET elements = ? WHERE id = ?";
			$content = json_encode($elements);
			$params = array($contents , $id);
			$db->query($sql, $params);
		} else {
			$sql  = "INSERT INTO language ";
			$params = array($tenant_id, $page, $lang, $elements);
			$db->query($sql, $params);
		}
		
	}
	
	public function getPageEWlements($tenantId, $page, $lang)
	{
		global $db;
				
		// first choice
		$sql = "SELECT * FROM language WHERE tenant_id = ? AND page = ? AND lang = ?";
		
		$params = array($tenant_id, $page, $lang);
		$record = $db->query($sql , $params)->result();
		if ($record)
		{
			$elements = json_decode($record[0]->elements);
			return $elements;
		}
		
		if ($tenant_id != -1)
		{
			$params = array(-1, $page, $lang);
			$record = $db->query($sql , $params)->result();
			$elements = json_decode($record[0]->elements);
			if ($record) return $elements;
		}
		
		if ($lang != "EN")
		{
			$params = array(-1, $page, "EN");
			$record = $db->query($sql , $params)->result();
			$elements = json_decode($record[0]->elements);
			if ($record) return $elements;
		}
		
		return array();
	}
}




