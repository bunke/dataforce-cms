<?
header("Pragma: no-cache");
header('Cache-Control: no-cache');

$tablea=$tablei_ASSOC;
$col_rec=$_REQUEST['col_rec'];
$col_under=$_REQUEST['col_under'];
$jfname=$_REQUEST['jfname'];

$tabler=$_REQUEST['tabler'];
$tablei=$_REQUEST['tablei'];

//var_dump($_REQUEST);

if (file_exists("connect.php")) include("connect.php"); else include("../includes/connect.php");

${"admin_$tabler"}->SORT=str_replace("%20"," ",${"admin_$tabler"}->SORT);

if (isset(${"admin_$tabler"}->ECHO_NAME)) $echo_rub_name=${"admin_$tabler"}->ECHO_NAME;
else $echo_rub_name="name_1";

if (isset(${'admin_'.$tablei}->ALT_ID)) $idname=${'admin_'.$tablei}->ALT_ID;
else $idname='id';

if (isset($tablea) && $id>0)
{
	
	if ($_GET['addrub']>0 && $id>0)
	{
		$q="INSERT INTO ".$tablea." (`$col_rec`,`$col_under`) VALUES ('".$id."','".$_GET['addrub']."')";
		mQuery($q);
		echo mError();
	}
	if ($_GET['delrub']>0 && $id>0)
	{
		$q="DELETE FROM ".$tablea." WHERE $col_under=".$_GET['delrub']." AND $col_rec=".$id."";
		mQuery($q);
	//echo $q.mError();
	}
}

$tdbg="";//"bgcolor=\"#c6d1de\"";
echo "<table border=0 class=txt>";
if ($tabler!="")
{	
	if ($under>0) 
	{
		$query="SELECT * FROM $tabler WHERE `id`='$under' ORDER BY name_1 LIMIT 150";
		//echo $query;
		$result = mQuery($query);
		$row=mFetchArray($result);
		echo "<tr $tdbg><td align=center>".imedm."</td><td><b><font color=#ff0000>".stripslashes($row[$echo_rub_name])."</font></b></td><td width=45></td></tr>";
		echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:$jfname(".$row['under'].",0,0)\"><b>/..</b></a></td><td></td><td width=45></td></tr>";
	}
	else echo "<tr $tdbg><td align=center>".imedm."</td><td><b><font color=#ff0000>".$word[$ALANG]['root']."</font></b></td><td width=45></td></tr>";

	if ($under) $query="SELECT * FROM $tabler WHERE `under`='$under' ORDER BY name_1 LIMIT 150";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		$row[name]=stripslashes($row[name]);
		$row[name_1]=stripslashes($row[name_1]);
		$row[zag_1]=stripslashes($row[zag_1]);
		$queryp="SELECT * FROM ".$tablea." WHERE `$col_rec`='$id' AND $col_under=$row[id]";
		//echo $queryp;
		$resultp = mQuery($queryp);
		$nump=mNumRows($resultp);
		echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:$jfname(".$row[id].",0,0)\">$row[name_1]</a></td><td></td><td width=45></td><td width=45></td></tr>";
		}
}

if ($tablei!="")
{	
	if ($under) $query="SELECT * FROM $tablei WHERE `under`='$under' ORDER BY name_1 LIMIT 150";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		$row[name]=stripslashes($row[name]);
		$row[name_1]=stripslashes($row[name_1]);
		$row[zag_1]=stripslashes($row[zag_1]);
		
		if ($row['preview_name']!='')
		{
			$image='<img src="'.$pref_url.$FOLDER_FILES.'/preview/'.$row['preview_name'].'" border="0" width="25">';
		}
		else
		if (is_file($pref.$FOLDER_IMAGES.'/'.$tablei.'.'.$row['crtdate'].'.1.s.jpg'))
		{
			$image='<img src="'.$pref_url.$FOLDER_IMAGES.'/'.$tablei.'.'.$row['crtdate'].'.1.s.jpg" border="0"  width="24">';
		}
		else
		if ($row['format']!='')
		{
			if (is_file('img/filetypes/'.$row['format'].'.gif')) $image='<img src="img/filetypes/'.$row['format'].'.gif" border="0"  width="24">';
		}
		else $image=imfile;


		$queryp="SELECT * FROM ".$tablea." WHERE `$col_rec`='$id' AND $col_under=$row[id]";
		//echo $queryp;
		$resultp = mQuery($queryp);
		$nump=mNumRows($resultp);
		if ($nump>0) $plus="";
		else $plus="<strong><a href=\"javascript:$jfname(".$row['under'].",$row[id],0)\">+</a></strong>";
		
		echo "<tr $tdbg><td align=center>".$image."</td><td>$row[$echo_rub_name]</td><td>#$row[id]</td><td width=45>$plus</td><td width=45></td></tr>";
		}
}

//echo $_GET['addrub'].' '.$tablea.' '.$id;

if (isset($tablea) && $id>0)
{
	echo "<tr $tdbg><td align=center></td><td><hr></td><td></td><td width=45></td><td width=45></td></tr>";
	$query="SELECT $tablei.* FROM ".$tablea." as admin_assoc LEFT JOIN $tablei ON  ($tablei.id=admin_assoc.$col_under) WHERE admin_assoc.$col_rec=$id ORDER by id";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		$row[$echo_rub_name]=stripslashes($row[$echo_rub_name]);
		if ($row['preview_name']!='')
		{
			$image='<img src="'.$pref_url.$FOLDER_FILES.'/preview/'.$row['preview_name'].'" border="0" width="25">';
		}
		else
		if (is_file($pref.$FOLDER_IMAGES.'/'.$tablei.'.'.$row['crtdate'].'.1.s.jpg'))
		{
			$image='<img src="'.$pref_url.$FOLDER_IMAGES.'/'.$tablei.'.'.$row['crtdate'].'.1.s.jpg" border="0"  width="24">';
		}
		else
		if ($row['format']!='')
		{
			if (is_file('img/filetypes/'.$row['format'].'.gif')) $image='<img src="img/filetypes/'.$row['format'].'.gif" border="0"  width="24">';
		}
		else $image=imfile;
		
		
		//if ($row['under']>0) $row[$echo_rub_name]=RubName($tabler,$row['under']).' / '.$row[$echo_rub_name];
		
		echo "<tr $tdbg><td align=center>".$image."</td><td><strong>$row[$echo_rub_name]</strong></td><td>#$row[id]</td><td width=45><strong><a href=\"javascript:$jfname(".$row['under'].",0,$row[id])\">-</a></strong></td><td width=45></td></tr>";
		}
}
echo "</table>";
if (file_exists("disconnect.php")) include("disconnect.php"); else include("../includes/disconnect.php");
?>

