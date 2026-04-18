<?
header("Pragma: no-cache");
header('Cache-Control: no-cache');
$dir=$_GET['dir'];

	if ($dir=='') $path='.';
	else $path=$dir.'/.';
	echo imfolder.'<strong>'.$path.'</strong><br/>';
	echo "<table border=0 class=txt>";
	//folders
	$d=scandir($path);
	foreach($d as $file)
   	{
		if (($file!='.'))
		{
			//echo (($dir!='')?$dir.'/':'').$file;
			if (is_dir((($dir!='')?$dir.'/':'').$file)) if (!($dir=='..' && $file=='..')) echo "<tr $tdbg><td align=center>".imfolder1."</td><td><a href=\"javascript:ListFilesDir('".(($dir!='')?$dir.'/':'')."$file')\"><strong>/$file</strong></a></td><td></td><td width=45></td></tr>";
		}
			
	}
	//files
	$d=scandir($path);
	foreach($d as $file)
   	{
		if (($file!='.'))
		{
			if (is_file((($dir!='')?$dir.'/':'').$file)) echo "<tr $tdbg><td align=center>".imfile."</td><td><a href=\"text_editor.php?src=".(($dir!='')?$dir.'/':'')."$file\">$file</a></td><td></td><td width=45></td></tr>";
		}
			
	}
	echo "</table>";
?>	