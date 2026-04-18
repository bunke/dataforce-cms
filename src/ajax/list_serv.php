<?
$oid=(int)$_GET['oid'];
$add=(int)$_GET['add'];
$del=(int)$_GET['del'];
if ($oid>0)
{
if (file_exists("connect.php")) include("connect.php"); else include("../includes/connect.php");

	if ($add>0)
	{
		$rowi=FetchID($TABLE_CATALOG,$add);
		$qi="INSERT INTO `$TABLE_SERV_COMPONENT` (`id` ,`servID`,`itemID`,`item_name`,`serv_dt`) VALUES (NULL , '$oid', '$add', '$rowi[name_1]', '0')";
		mQuery($qi);
		//echo $qi.mError();
	}
	
	if ($del>0)
	{
		$qd="DELETE FROM $TABLE_SERV_COMPONENT WHERE id=$del";
		mQuery($qd);
	}
	
echo "<strong style=\"font-size:15px;\">Компоненты в заказе на обслуживание №$oid</strong><br />
<br />";
$query="SELECT * FROM $TABLE_SERV_COMPONENT WHERE servID=$oid ORDER BY serv_dt asc";
$result = mQuery($query);
$num=mNumRows($result);
echo '<table border="0">';
if ($num) for ($i=0; $i<$num;$i++)
	{
	$row=mFetchArray($result);
	$row[item_name]=stripslashes($row[item_name]);
	echo '<tr><td>'.imfile." <a href=\"items.php?tabler=catalog_rubs&tablei=catalog&id=".$row['itemID']."#header\" target=\"_blank\">$row[item_name]</a></td><input name=\"serv_dt".$row['id']."\" type=\"text\" id=\"serv_dt".$row['id']."\" value=\"".$row['serv_dt']."\" onkeyup=\"updateItemVal('".$TABLE_SERV_COMPONENT."','serv_dt','serv_dt".$row['id']."','".$row['id']."');\" style=\"width:70px;\"/></td><td> [ <a  href=\"javascript:list_serv(0,".$row['id'].")\">".imdelm."</a> ]</td></tr>";
	}
echo '</table><br />Добавить компонент по ИД: <input name="addid" type="text" id="addid" style="width:50px;" />
<a href="javascript:list_serv(\'addid\',0)"><u>добавить</u></a>';
	if (file_exists("disconnect.php")) include("disconnect.php"); else include("../includes/disconnect.php"); 
}
?>

