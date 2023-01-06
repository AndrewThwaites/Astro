<?php

class TEMPLATE
{
	function set_page_elements($tenant_id, $page, $lang, $elements)
	{
		// Does it exist?
		$sql = "SELECT * FROM language WHERE tenant_id  ? AND page = ? AND lang =? ";
		$params = array($tenant_id, $page, $lang);
		$record = $this->db->query($sql , $params )->result();
		if ($record)
		{
			$sql = "UPDATE language SET elements = ? WHERE id = ?";
			$content = json_encode($elements);
			$params = array($contents , $id);
			$this->db->query($sql, $params);
		}
		else
		{
			$sql  = "INSERT INTO language ";
			$params = array($tenant_id, $page, $lang, $elements);
			$this->db->query($sql, $params);
		}
		
	}
	
	function get_page_elements($tenant_id, $page, $lang)
	{
		// first choice
		$sql = "SELECT * FROM language WHERE tenant_id = ? AND page = ? AND lang = ?";
		
		$params = array($tenant_id, $page, $lang);
		$record = $this->db->query($sql , $params)->result();
		if ($record)
		{
			$elements = json_decode($record[0]->elements);
			return $elements;
		}
		
		if ($tenant_id != -1)
		{
			$params = array(-1, $page, $lang);
			$record = $this->db->query($sql , $params)->result();
			$elements = json_decode($record[0]->elements);
			if ($record) return $elements;
		}
		
		if ($lang != "EN")
		{
			$params = array(-1, $page, "EN");
			$record = $this->db->query($sql , $params)->result();
			$elements = json_decode($record[0]->elements);
			if ($record) return $elements;
		}
		
		return array();
	}
}




