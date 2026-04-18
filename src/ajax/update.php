<?php

/*
 *  AJAX UPDATE
 * */

$table = addslashes($_GET['table']);
$field = addslashes($_GET['field']);
$value = addslashes($_GET['value']);

if (empty($table) || empty($field)) {
	die('visible');
}

$id = isset($_GET['id'])?$_GET['id']:null;
$wh = isset($_GET['wh'])?addslashes($_GET['wh']):null;

if (!empty($id)) {
	//PreProcessing
	if (class_exists('admin_' . $table)) {
		$cfn = 'admin_' . $table;
		${'admin_' . $table} = new $cfn();
		if (method_exists(${'admin_' . $table}, 'beforeAjaxUpdate')) {
			${'admin_' . $table}->beforeAjaxUpdate($id, $field, $value);
		}
	}

	$query = "UPDATE `$table` SET `$field` = '$value' WHERE id = '$id'";
} elseif (!empty($wh) && strlen($wh) >= 3) {
	$query = "UPDATE `$table` SET `$field` = '$value' WHERE $wh";
}

$result = mQuery($query);
//echo "$query -> $result".mError();

//Результат AJAX апдейта задает стиль DIVу который всплывает при вызове скрипта
if ($result == 1) {
	if (class_exists('admin_' . $table)) {
		$cfn = 'admin_' . $table;
		${'admin_' . $table} = new $cfn();
		if (method_exists(${'admin_' . $table}, 'afterAjaxUpdate')) {
			${'admin_' . $table}->afterAjaxUpdate($id, $field, $value);
		} else {
			//echo 'No Ajax method';
		}
	}

	echo 'none';
} else {
	echo 'visible';
}
