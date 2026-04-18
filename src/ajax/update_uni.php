<?
$table=$_GET['table'];
$st=str_replace(':','=',stripslashes($_GET['st']));
$wh=str_replace(':','=',stripslashes($_GET['wh']));
$q="UPDATE `$table` SET $st WHERE $wh";
mQuery($q);
echo $q.' -> '.mQuery($q);
?>