<?php

class HTML
{
	public static function hidden($id, $name, $value, $class)
	{
		return  '<input="hidden" id="'.$id.'"  name="'.$name.'" value=".$value."  />';
	}
	
	public static function input($id, $name, $value, $data, $class, $styling)
	{
		return  '<input="text" id="'.$id.'"  name="'.$name.'" value="'.$value.'"class="'.$class.'" style="'.$styling.'" />';		
	}
	
	public static function label($caption, $for = false, $form = false)
	{
		$extra = ($for) ? ' for="$for" ' : '';
		$extra.= ($form) ? ' form="$form" ' : $extra;
		return "<label $extra >$caption</label>";
	}
	
	public static function textarea($id, $name, $value, $data, $class, $styling)
	{
		return '<textarea id="'.$id.'" name="'.$name.'" class="'.$class.'" style="'.$data.'">'.$value.'</textrea>';
	}
	
	public function select($id, $name, $options, $selected_value, $data, $class, $styling)
	{
		$option_html = array();
		foreach($options as $k => $v)
		{
			$selected = ($k == $selected_value) ? 'selected' : '';
			$option_html[] = '<option id="'.$k.'" '.$selected.'>'.$v.'</option>';
		}
		return '<select id="'.$id.'" name="'.$name.'" >'.$options_html.'</select>';
	}

	
	public function radio_radio($id, $name, $value, $data, $class, $styling)
	{
		return '';
	}
}

?>