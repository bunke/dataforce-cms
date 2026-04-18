<?
header("Pragma: no-cache");
header('Cache-Control: no-cache');

$tablea=$_REQUEST['tablea'];
$col_rec=$_REQUEST['col_rec'];
$col_under=$_REQUEST['col_under'];
$jfname=$_REQUEST['jfname'];

if ($_SESSION['erubs']=='') $_SESSION['erubs']='0,';

//var_dump($_REQUEST);

//if (file_exists("connect.php")) include("connect.php"); else include("../includes/connect.php");

${"admin_$tabler"}->SORT=str_replace("%20"," ",${"admin_$tabler"}->SORT);

if (isset(${"admin_$tabler"}->ECHO_NAME)) $echo_rub_name=${"admin_$tabler"}->ECHO_NAME;
else $echo_rub_name="name_1";

if (isset(${'admin_'.$tablei}->ALT_ID)) $idname=${'admin_'.$tablei}->ALT_ID;
else $idname='id';


	if ($_GET['addrub']>0)
	{
		$_SESSION['erubs'].=$_GET['addrub'].',';
		//var_dump($_SESSION['erubs']);
		//mQuery("INSERT INTO ".$tablea." VALUES ('".$id."','".$_GET['addrub']."')");
		//echo mError();
	}
	if ($_GET['delrub']>0)
	{
		$_SESSION['erubs']=str_replace(','.$_GET['delrub'].',',',',$_SESSION['erubs']);
		//mQuery("DELETE FROM ".$tablea." WHERE $col_under=".$_GET['delrub']." AND $col_rec=".$id."");
	}

$tdbg="";//"bgcolor=\"#c6d1de\"";
echo "<table border=0 class=txt>";
if ($tabler!="")
{	
	if ($under>0) 
	{
		$query="SELECT * FROM $tabler WHERE `id`='$under' ORDER BY ".${"admin_$tabler"}->SORT." LIMIT 100";
		//echo $query;
		$result = mQuery($query);
		$row=mFetchArray($result);
		if (!($row['under']>0 || $row['under']==-1)) $row['under']='0';
		echo "<tr $tdbg><td align=center>".imedm."</td><td><b><font color=#ff0000>".stripslashes($row[$echo_rub_name])."</font></b></td><td width=45></td></tr>";
		echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:$jfname(".$row['under'].",0,0)\"><b>/..</b></a></td><td></td><td width=45></td></tr>";
		}
	else echo "<tr $tdbg><td align=center>".imedm."</td><td><b><font color=#ff0000>".$word[$ALANG]['root']."</font></b></td><td width=45></td></tr>";

	if ($under!=0) $whu="WHERE `under`='$under'";

	$query="SELECT * FROM $tabler $whu ORDER BY ".${"admin_$tabler"}->SORT." LIMIT 100";
	// echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		$row[name]=stripslashes($row[name]);
		$row[name_1]=stripslashes($row[name_1]);
		$row[zag_1]=stripslashes($row[zag_1]);
		if (!($row['under']>0 || $row['under']==-1)) $row['under']='0';
		/*$queryp="SELECT * FROM ".$tablea." WHERE `$col_rec`='$id' AND $col_under=$row[id]";
		//echo $queryp;
		$resultp = mQuery($queryp);
		$nump=mNumRows($resultp);*/
  if (preg_match('/,'.preg_quote($row['id'], '/').','.'/', $_SESSION['erubs'])) $plus="";
		else $plus="<strong><a href=\"javascript:$jfname(".$row['under'].",$row[id],0)\">+</a></strong>";

		echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:$jfname(".$row[id].",0,0)\">$row[$echo_rub_name]</a></td><td>#$row[id]</td><td width=45>$plus</td><td width=45></td></tr>";
		}
}

//echo $_GET['addrub'].' '.$tablea.' '.$id;


	$erubs=substr($_SESSION['erubs'],0,strlen($_SESSION['erubs'])-1);
	echo "<tr $tdbg><td align=center></td><td><hr></td><td></td><td width=45></td><td width=45></td></tr>";
	$query="SELECT * FROM $tabler WHERE id IN (".$erubs.") ORDER by id";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		$row[$echo_rub_name]=stripslashes($row[$echo_rub_name]);

		if ($row['under']>0) $row[$echo_rub_name]=RubName($tabler,$row['under']).' / '.$row[$echo_rub_name];
		if (!(abs($row['under'])>0))$row['under']='0';
		echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:$jfname(".$row[id].",0,0)\"><strong>$row[$echo_rub_name]</strong></a></td><td>#$row[id]</td><td width=45><strong><a href=\"javascript:$jfname(".$row['under'].",0,$row[id])\">-</a></strong></td><td width=45></td></tr>";
		}
echo "</table>";

	$query="SELECT uo.`id` AS `orderID`, IF (u.`email`, u.`email`, uo.`email`) AS `email`, IF (u.`name`, u.`name`, uo.`name`) AS `user_name` FROM `orders` uo  LEFT JOIN `orders_rubs_assoc` ora ON ora.`recID`=uo.`id` LEFT JOIN `users` u ON u.`id`=uo.`user_id` WHERE ora.`under` IN (".$erubs.") GROUP BY `email`  HAVING `email`<>'' ORDER BY `email` ASC";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	echo 'Подпищиков: '.$num;

//if (file_exists("disconnect.php")) include("disconnect.php"); else include("../includes/disconnect.php");
?>
