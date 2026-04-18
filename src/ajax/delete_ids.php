<?php

$table = $_GET['table'];
$ids = $_GET['ids'];

$nodelpos = getPosInFld('no_del',${"admin_$table"}->fld);

if ($nodelpos > 0) {
	$whnd = 'AND no_del=0';
} else {
	$whnd = '';
}

$q = "DELETE FROM `$table` WHERE id IN ($ids) $whnd";
mQuery($q);
echo $q . ' -> ' . mQuery($q);
