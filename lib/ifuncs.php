<?
//Interface functions by Alex Bunke //07.12.2013

function I_GetRealIp()
{
 if (!empty($_SERVER['HTTP_CLIENT_IP'])) 
 {
   $ip=$_SERVER['HTTP_CLIENT_IP'];
 }
 elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
 {
  $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
 }
 else
 {
   $ip=$_SERVER['REMOTE_ADDR'];
 }
 return $ip;
}

function I_GetFileFormat($name)
{
	$tmp=explode('.',$name);
	$tmp=array_reverse($tmp);
	$format=$tmp[0];
	return 	$format;			
}

function I_RFS($s)
{
	if ($s!='')
	{
		//if (strlen($s)>1)
		//{
			$tu=$s;
			if ($tu[0]=='/') 
			{
				$s=substr($tu,1,strlen($s)-1);
			}
		//}
	}
	return $s;
}

function I_GetImageFolder($table,$crtdate)
{
	$folder=$table.'/'.date("Y-m",$crtdate).'/';
	return $folder;
}

function getWay($table_rubs,$id,$pref='/rub_',$suf='.htm',$end=-1)
{
	global $way,$countw,$lang;
	if (!($lang>0)) $lang='1';
	$query="SELECT * FROM $table_rubs WHERE id=$id;";
	//echo $query;
	$result = mQuery($query);
	$row=mFetchArray($result);
	$row=I_ReplSls($row);
	$way='<a href="'.$pref.$row['id'].$suf.'">'.$row['name_'.$lang].'</a> / '.$way;
	$countw++;
	if ($row['under']==$end || $row['under']<1 || $countw>=10) return;
	//echo $row['under'].'!='.$end;
	getWay($table_rubs,$row['under'],$pref,$suf,$end);	
}

function I_smail($to, $from, $subject, $message) {
    $connect = fsockopen ("localhost", 25, $errno, $errstr, 30);
	$resp .= fgets($connect,1024);
    fputs($connect, "HELO localhost\r\n");
    fputs($connect, "MAIL FROM: $from.\n");
    fputs($connect, "RCPT TO: $to.\n");
	$resp .= fgets($connect,1024);
    fputs($connect, "DATA\r\n");
    fputs($connect, "Content-Type: text/html; charset=windows-1251\n");
    fputs($connect, "To: $to.\n");
	$resp .= fgets($connect,1024);
    fputs($connect, "Subject: $subject\n");
    fputs($connect, "\n\n");
	$resp .= fgets($connect,1024);
    fputs($connect, stripslashes($message)." \r\n");
    $resp .= fgets($connect,1024);
	fputs($connect, ".\r\n");
	$resp .= fgets($connect,1024);
    fputs($connect, "QUIT\n");
	$resp .= fgets($connect,1024);
	fclose($connect);
//I_smail("bunke@mail.ru", "bunke@volia.com", "111", "Hello, World!");
}

function I_GetConst($name)
{
        global $TABLE_CONST;
        $q="SELECT value FROM $TABLE_CONST WHERE var='$name'";
        $result= mQuery($q);
        $row=mFetchArray($result);
        return $row['value'];
}

function I_GetSl($name)
{
        global $TABLE_SLOVAR, $lang;
        $q="SELECT * FROM $TABLE_SLOVAR WHERE name='$name'";
        $result= mQuery($q);
        $row=mFetchArray($result);
		$row=I_ReplSls($row);
        return (isset($row['val_'.$lang])?$row['val_'.$lang]:$row['val_1']);
}

function I_AllSl($site)
{
	global $TABLE_SLOVAR, $lang;
	$q="SELECT * FROM $TABLE_SLOVAR";
	$result= mQuery($q);
	$num=mNumRows($result);
	for ($i=0; $i<$num;$i++)
	{
		$row=mFetchArray($result);
		if (isset($row['var'])) $site->ReplaceTag('{'.$row['var'].'}',(($row['val_'.$lang]!='')?$row['val_'.$lang]:$row['val_1']));
		elseif (isset($row['name'])) $site->ReplaceTag('{'.$row['name'].'}',(($row['val_'.$lang]!='')?$row['val_'.$lang]:$row['val_1']));
	}
    return $num;
}

function I_ReplSls($row)
{
	foreach($row as $key => $value)
	{
		$row[$key] = stripslashes($value);
	}

return $row;
}

function I_PVComment($pv)
{
	global $site;
	if ($pv==1)
	{
		$site->Replace("{pvcomment1}","<!--");
		$site->Replace("{pvcomment2}","-->");
	}
	else 
	{
		$site->Replace("{pvcomment1}","");
		$site->Replace("{pvcomment2}","");
	}
}

function I_CicleComment($comm)
{
	global $site;
	if ($comm==1)
	{
		$site->ReplaceInCicle("{comment1}","<!--");
		$site->ReplaceInCicle("{comment2}","-->");
	}
	else 
	{
		$site->ReplaceInCicle("{comment1}","");
		$site->ReplaceInCicle("{comment2}","");
	}
}

function I_ItemsNumUnder($table, $under)
{
	$query="SELECT * FROM $table where under=$under";
	$result= mQuery($query);
	$num=mNumRows($result);
	return $num;
}

function I_RubName($table, $id)
{
	if ($id==-1) return "";
	else
	{
		$query="SELECT * FROM $table WHERE `id`='$id'";
		$result= mQuery($query);
		$row=mFetchArray($result);
		return stripslashes($row[name_1]);
	}
}

function I_RubTxt($table, $id)
{
	if ($id==-1) return "";
	else
	{
		$query="SELECT * FROM $table WHERE `id`='$id'";
		$result= mQuery($query);
		$row=mFetchArray($result);
		return stripslashes($row[txt_1]);
	}
}

function I_List_Rubs($table,$under,$decor,$inc)
{
global $lang;
$rubs="";
if ($decor) $decor.="&nbsp;";
$query="SELECT * FROM $table where under=$under order by sort asc";
$result = mQuery($query);
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
$row=mFetchArray($result);
$rubs.="$decor<a href=index.php?inc=$inc&under=$row[id]>".stripslashes($row["name_$lang"])."</a><br>";
}
return $rubs;
}

function I_List_Rubs_Cnt($table,$tablei,$under,$decor,$inc)
{
global $lang;
$rubs="";
if ($decor) $decor.="&nbsp;";
$query="SELECT * FROM $table where under=$under order by name_fl asc";
$result = mQuery($query);
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
	$row=mFetchArray($result);
	    $cnt=0;
	    $query2="SELECT * FROM $tablei where under=$row[id]";
		$result2 = mQuery($query2);
		$cnt=mNumRows($result2);
	$rubs.="$decor<a href=index.php?inc=$inc&under=$row[id]>".$row["name_$lang"]." ($cnt)</a><br>";
}
return $rubs;
}


function I_List_Docs($under,$decor)
{
global $TABLE_DOCS,$lang;
$docs="";
if ($decor) $decor.="&nbsp;";
$query="SELECT * FROM $TABLE_DOCS where under=$under order by zag_$lang asc";
//echo $query;
$result = mQuery($query);
echo mError();
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
$row=mFetchArray($result);
if ($row[format]=="") $docs.="$decor<a href=index.php?inc=docs&id=$row[id]>".stripslashes($row["zag_$lang"])."</a>";
else $docs.="$decor<a href=files/$row[crtdate].$row[format] target=_blank>".stripslashes($row["zag_$lang"])."</a>";
}
return $docs;
}

function I_List_Docs2($under,$decor)
{
	global $TABLE_DOCS,$lang;
	$docs="<table width=100% border=0 cellspacing=0 cellpadding=1>";
	$query="SELECT * FROM $TABLE_DOCS where under=$under order by sort asc, name_fl_$lang asc, zag_$lang asc";
	//echo $query;
	$result = mQuery($query);
	echo mError();
	$num=mNumRows($result);
	for ($i=0; $i<$num;$i++)
	{
		$row=mFetchArray($result);
		if ($row[format]=="") $docs.="<tr><td valign=middle width=15>$decor</td><td valign=middle><a href=index.php?inc=docs&id=$row[id]>".$row["zag_$lang"]."</a></td></tr>";
		else $docs.="<tr><td valign=middle width=15><img src=img/$row[format].gif border=0></td><td valign=middle><a href=files/$row[crtdate].$row[format] target=_blank>".$row["zag_$lang"]."</a></td></tr>";
	}
	$docs.="</table>";
	return $docs;
}

function I_ReplKaw($txt)
{
	$txt=preg_replace("/<`>/","'",$txt);
	$txt=preg_replace("/<``>/","\"",$txt);
	return $txt;
}

//Get doc
function I_Doc($id,$url=NULL)
{
	global $TABLE_DOCS;

	if ($url==NULL) $query="SELECT * FROM $TABLE_DOCS WHERE id=$id;";
	else $query="SELECT * FROM $TABLE_DOCS WHERE name_url='$url';";
	//echo $query;
	$result = mQuery($query);
	$row=mFetchArray($result);
	$row=I_ReplSls($row);

	return $row;
}

//Get row
function I_Row($table,$id)
{
	$query="SELECT * FROM $table WHERE id='$id'";
	//echo $query;
	$result = mQuery($query);
	$row=mFetchArray($result);
	$row=I_ReplSls($row);

	return $row;
}

//Get row
function I_QRow($query,$debug=0)
{
	$result = mQuery($query);
	if ($debug==1) echo $query.' '.$result.' '.mError();
	$row=mFetchAssoc($result);
	$row=I_ReplSls($row);
	return $row;
}

function I_Rub($tabler,$id)
{
	return I_Row($tabler,$id);
}

function I_Gen_SelectItems($table,$tabler,$under,$varname,$itemv) // Gen select ~ under...
{
	$sbody="";
	if ($under) $wh="WHERE under='$under'";
	else $under="WHERE under>-2";
	$query="SELECT * FROM $table $wh ORDER BY under asc, name_1 asc";
	//echo $query;
	$result = mQuery($query);
	$num=mNumRows($result);
	for ($i=0; $i<$num;$i++)
	{
		$row=mFetchArray($result);
		$sel="";
		if ($itemv==$row[id]) $sel="selected";
		$sbody.="<option value=$row[id] $sel>";
		if ($tabler!="") $sbody.=I_RubName($tabler,$row[under]);
		$sbody.="$row[name]$row[name_1]$row[zag_1]</option>";
	}
	return "<SELECT name=\"$varname\" id=\"$varname\"><option value=-1>...</option>".$sbody."</SELECT>";
}

function I_Gen_Select($table,$under,$varname,$itemv,$namex="Выбор...") // Gen select ~ under...
{
$sbody="<option>$namex</option>";
if ($under) $wh="WHERE under='$under'";
else $under="WHERE under>'-2'";
$query="SELECT * FROM $table $wh ORDER by name_1";
$result = mQuery($query);
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
$row=mFetchArray($result);
$sel="";
if ($itemv==$row[id]) $sel="selected";
$sbody.="<option value=$row[id] $sel>$row[name]$row[name_1]$row[zag_1]</option>";
}
return "<SELECT name=\"$varname\">".$sbody."</SELECT>";
}

function I_Gen_RadioB($table,$under,$varname,$varval) // Gen radiogroup
{
$rbody="";
if ($under) $wh="WHERE under='$under'";
else $under="WHERE under>'0'";
$query="SELECT * FROM $table $wh";
$result = mQuery($query);
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
$row=mFetchArray($result);
$sel="";
if ((($i==0) && !$varval) || ($varval==$row[id])) $sel="checked";
$rbody.="<input type=\"radio\" name=\"$varname\" value=\"$row[id]\" $sel> $row[name_1]$row[zag_1]<br>";
}
return $rbody;
}

//Adding to DB Q
function I_addingQuery($table,$fld) //generates ADDING QUERY
{
	$query = "INSERT INTO `$table` (";
	$i=0;
	$query.="`".$fld[$i]->name."`";
	$i++;
	while($fld[$i])
	{
		if (($fld[$i]->type)<5) $query.=",`".$fld[$i]->name."`";
		$i++;
	}

	$query.=") VALUES (";
	$i=0;
	$query.="'".$fld[$i]->val."'";
	$i++;
	while($fld[$i])
	{
		if (($fld[$i]->type)<5) $query.=",'".$fld[$i]->val."'";
		$i++;
	}

	$query.=");";
	return $query;
}
//UPDATE Query
function I_updQuery($table,$fld,$id) 
{
	$query = "UPDATE `$table` SET ";
	$i=0;
	$query.="`".$fld[$i]->name."`='".$fld[$i]->val."'";
	$i++;
	while($fld[$i])
	{
		if (($fld[$i]->type)<5) $query.=",`".$fld[$i]->name."`='".$fld[$i]->val."'";
		$i++;
	}
	$query.=" WHERE id='$id'";
	return $query;
}

//IMAGES
function I_ImgResizeHor($file,$width,$name)
{
	$array = getimagesize($file);
	$height=$array[1]/($array[0]/$width);

	$types = array(
		1 => "imagecreatefromgif",
		2 => "imagecreatefromjpeg",
		3 => "imagecreatefrompng",
	);


	$im = $types[$array[2]]($file);
	$sim = imagecreatetruecolor ($width, $height);
	$white = imagecolorallocate ($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));

	imagejpeg($sim,$name,90);

	imagedestroy($sim);
	imagedestroy($im);

return getimagesize($name);
}

function I_ImgResizeVert($file,$height,$name)
{
	$array = getimagesize($file);
	$width=$array[0]/($array[1]/$height);

	$types = array(
		1 => "imagecreatefromgif",
		2 => "imagecreatefromjpeg",
		3 => "imagecreatefrompng",
	);


	$im = $types[$array[2]]($file);
	$sim = imagecreatetruecolor ($width, $height);
	$white = imagecolorallocate ($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));

	imagejpeg($sim,$name,81);

	imagedestroy($sim);
	imagedestroy($im);

return getimagesize($name);
}

function I_ImgResizeQuad($file,$size,$name)
{
	$array = getimagesize($file);
	if ($array[0]>$array[1])
	{
	return I_ImgResize($file,$size,$name);
	}
	else
	{
	return I_ImgResizeVert($file,$size,$name)	;
	}
}

function I_ImgResize($file,$size,$name,$type)
{
	switch($type)
	{
	case 1:return I_ImgResizeHor($file,$size,$name);
	case 2:return I_ImgResizeVert($file,$size,$name);
	case 3:return I_ImgResizeQuad($file,$size,$name);
	default: return 0;
	}
}

function I_UploadImages($table,$crtdate,$num)
{
for ($i=1;$i<=$num;$i++)
 	{
		$filename1="$table.$crtdate.$i.s.jpg";
		$filename2="$table.$crtdate.$i.b.jpg";
		$newname1="images/$filename1";
		$newname2="images/$filename2";

		$oldname='imgb'.$i;
		if ($_FILES[$oldname]['tmp_name']) 
		{			
 			@unlink($newname1);
			@unlink($newname2);

			move_uploaded_file($_FILES[$oldname]['tmp_name'],$newname1);
			copy($newname1,$newname2);
			chmod($newname1,0644);chmod($newname2,0644);
			I_ImgResize($newname1,160,$newname1,2);
			I_ImgResize($newname2,700,$newname2,1);
			chmod($newname2,0644);
		}
	}
}
?>
