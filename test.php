<?php

/*
	tp_feature
	id, feature_title, feature_description, module_name, order 
	
	tp_feature_test
	id , feature_id, test_title, test_description, 
	parameters, expected_result, status, order, last_success, success_count, fail_count

	test_plan_entry
	id, feature_id, created, input_parameter , outcome, actual_result	

	1]	auto testing	****
	6]	closed sets
	7]	chat feature
	8]	templates
	
	2]	app
	3]	meta tables
	4]	appointments
	5]	multilingual
*/

$post = $_POST;
$action = (!empty($post['action'])) ? $post['action'] : '';
if (!$action)
{
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>API Test plan</title>
</head>
<body>
<h1>Test Plan</h1>
<p>
Reset database <a>here</a>
</p>
<?php

$mysqli = new mysqli("localhost", "root", "", "conversation");
// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}

function module_title($module_title)
{
	echo '<h1>'.$module_title.'</h1>'.PHP_EOL;
}

function feature_title($feature_title)
{
	echo '<br><h3>'.$feature_title.'</h3>'.PHP_EOL;	
}

function feature_table($feature_id)
{
	global $mysqli;
	$sql = "SELECT * FROM tp_feature_test WHERE feature_id =".$feature_id." ORDER BY sequence";
	
	$rs = $mysqli->query($sql);
?>
<TABLE border="1" style="width:100%">
<TR>
	<TD>Test Title</TD>
	<TD>Test Description</TD>
	<TD>Input data</TD>
	<TD>Outcome</TD>
	<TD>Launch Test</TD>
</TR>
<?php
	if ($rs)
	{
	foreach($rs as $r)
	{
?>
	<TR>
	<TD><?=$r['test_title']?></TD>
	<TD><?=$r['test_description']?></TD>
	<TD><?=$r['input_data']?></TD>
	<TD><?=$r['output_data']?></TD>
	<TD><a href="test.php?action=<?=$r['id']?>">Launch Test</a></TD>
	</TR>
<?php
	}
	}
?>
</TABLE>
<?php	
}

// tp_feauture
$sql = "SELECT distinct(module_name) FROM tp_feature";
$result = $mysqli->query($sql);

foreach($result as $row)
{
	module_title($row['module_name']);
	
	$sql = "SELECT * FROM tp_feature WHERE module_name = '".$row['module_name']."' ORDER BY sequence_order";
	$rs = $mysqli->query($sql);
	foreach($rs as $r)
	{
		feature_title($r['feature_title']);
		echo '<p>'.$r['feature_description'].'</p>';
		
		feature_table($r['id']);
	}	
}

$mysqli -> close();
?>
</body>

</html>

<?php
}
else
{
	// perform test
	$tid = $_GET['tid'];
}
