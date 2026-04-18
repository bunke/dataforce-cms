<?php

$table = $_GET['table'];
$type = $_GET['type'];
$sort_set = $_GET['sort_set'];
$sort_max = $_GET['sort_max'];
$sort_min = $_GET['sort_min'];

//echo $sort_max.'--->'.$sort_min;
//var_dump(${'table-'.$type});

if ($sort_set == 'desc') {
	$sort = $sort_max;
} else {
	$sort = $sort_min;
}

foreach (${'table-' . $type} as $id) {
	if ($id > 0) {
		$q = "UPDATE $table SET `sort`='$sort' WHERE id='$id'";
		//echo '<br/>'.$q;
		mQuery($q);
		if (mError() != '') {
			echo '<br/> ' . $q . mError() . '<br/> ';
		}
		if ($sort_set == 'desc') {
			$sort--;
		} else {
			$sort++;
		}
	}
}
