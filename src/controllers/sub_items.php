<?php
// SUBItems by Alex Bunke
/*
 * 2016.03.07 - Multi image for add
 *
 * */

header('Pragma: no-cache');
header('Cache-Control: no-cache');
$version = '0.5';
error_reporting(E_ERROR);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="plugins/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="plugins/ckfinder/ckfinder.js"></script>
<title>SUB Items</title>
<?php
include('inc/HeadCssJavaLinks.php');
$src = 'sub_items.php';
?>
</head>

<body style="padding:15px;">
<?php
//ID
if (!empty($_REQUEST['id'])) {
	$id = (int)$_REQUEST['id'];
} else {
	$id = 0;
}

//UNDER
if (!isset(${"admin_$tablei"}->FIELD_UNDER) || ${"admin_$tablei"}->FIELD_UNDER == 'under') {
	$under_field = 'under';
} else {
	$under_field = ${"admin_$tablei"}->FIELD_UNDER;
}

$under = isset($_REQUEST[$under_field])
	? (int)$_REQUEST[$under_field]
	: (isset($_REQUEST['under']) ? (int)$_REQUEST['under'] : 0);

//CREATION DATE
$creation_time = (date('U'));
$_POST['creation_time'][0] = $creation_time; //[0] - так как у нас мульти-форма

if (isset($_GET['drt']) && $_GET['drt'] == 1) { //drop subitems table
	mQuery("DROP TABLE $tablei");
}

if (isset($_GET['crt']) && $_GET['crt'] == 1) {//creating new table
	echo crtQuery($tablei,${"admin_$tablei"}->fld);
	mQuery(crtQuery($tablei,${"admin_$tablei"}->fld));
	echo @mysql_error();
}

//Сортировка
if (isset($_GET['sortid']) && $_GET['sortid'] > 0 && isset($_GET['sort'])) {
	if ($_GET['sortmode'] == 'rub') {
		$tablesort = $tabler;
	} else {
		$tablesort = $tablei;
	}

	$query = "SELECT id,sort FROM $tablesort WHERE id=" . $_GET['sortid'];
	$res = mQuery($query);
	$rowi = mFetchAssoc($res);
	if ($tabler != '') {
		$und = "AND $under_field=$under";
	} else {
		$und = '';
	}
	if ($_GET['sort'] == 'up' || $_GET['sort'] == 'down') {
		if ($_GET['sort'] == 'up') {
			$query = "SELECT id,sort FROM $tablesort WHERE sort>$rowi[sort] $und ORDER BY sort ASC";
		}
		if ($_GET['sort'] == 'down') {
			$query = "SELECT id,sort FROM $tablesort WHERE sort<$rowi[sort] $und ORDER BY sort DESC";
		}
		$res = mQuery($query);
		$row2 = mFetchAssoc($res);
		//var_dump($rowi);
		//var_dump($row2);
		if ($row2['sort'] != '') {
			mQuery("UPDATE $tablesort set sort='$row2[sort]' WHERE id=$rowi[id]");
			mQuery("UPDATE $tablesort set sort='$rowi[sort]' WHERE id=$row2[id]");
		}
	}
	if ($_GET['sort'] == 'down_max') {
		$query = "SELECT id,sort FROM $tablesort WHERE id<>'' $und ORDER BY sort ASC";
		$res = mQuery($query);
		$row2 = mFetchAssoc($res);
		if ($row2['sort'] != '') {
			mQuery("UPDATE $tablesort set sort='" . ($row2['sort'] - 1.1) . "' WHERE id=$rowi[id]");
		}
	}
}

// Определение наличия поля под файл
$i = 0;
$formatpos = null;
$filenamepos = null;

while (isset(${"admin_$tablei"}->fld[$i]->name)) {
	if (${"admin_$tablei"}->fld[$i]->name == 'format') {
		$formatpos = $i;
	} elseif (${"admin_$tablei"}->fld[$i]->name == 'filename') {
		$filenamepos = $i;
	}
	$i++;
}

// Fill values of fields
extract(${"admin_$tablei"}->fillValuesFromPost());
//echo ' !test! ';

//For edit
if (!empty($id)) {
	$row = FetchID($tablei, $id);

	//$query = "";
	//$result =0;

	//Перед сохранением новых данных можно что-то проверить и сделать
	if (method_exists(${"admin_$tablei"},'firstAction')) {
		${"admin_$tablei"}->firstAction($row);
	}
}

/*ДОБАВЛЕНИЕ*/
if (!empty($posted) && !$id) {
	echo 'Adding...';

	//echo $under_field.'->'.var_dump($_POST);
	//foreach($_POST[$under_field] as $n=>$under)
	//{
	/*$i=0;
		while(isset(${"admin_$tablei"}->fld[$i]->name))
		{
			if (${"admin_$tablei"}->fld[$i]->name=="sort") $sortpos=$i;

			${"admin_$tablei"}->fld[$i]->val = isset($_POST[${"admin_$tablei"}->fld[$i]->name])?$_POST[${"admin_$tablei"}->fld[$i]->name]:NULL; //[$n]
			$i++;
		}*/

	//echo 'OK';
	if ((isset(${"admin_$tablei"}->ECHO_NAME) && empty($_POST[${"admin_$tablei"}->ECHO_NAME])) && empty($_POST['name_1']) && empty(${"admin_$tablei"}->FILES_NUM)) {
		echo 'Skips...';
		//continue;
	}

	$rownew = ${"admin_$tablei"}->insertQuery();
	//var_dump($rownew);

	$rownew = FetchID($tablei, $rownew['id']);

	$img_id = $rownew[${"admin_$tablei"}->IMG_FIELD];

	uploadImagesAndFiles($tablei,$img_id); //0,$n

	// Редактирование после заливки файлов
	$query = ${"admin_$tablei"}->updateQuery($rownew['id']);
	//echo $query;

	// Устанавливаем параметр сортировки для новой записи на 1 больше, чем у предыдущей
	if ($sortpos > 0) {
		$query = "SELECT * FROM $tablei ORDER by sort desc";
		$result = mQuery($query);
		$rowsort = mFetchAssoc($result);
		mQuery("update $tablei set sort='" . (++$rowsort['sort']) . "' WHERE id=$rownew[id]");
	}

	if (method_exists(${"admin_$tablei"},'afterAdd')) {
		${"admin_$tablei"}->afterAdd($rownew);
	}

	//}
}
//Редактирование
else if ($id && !empty($posted) && empty($delitem)) {
	echo $word[$ALANG]['editing'] . '<br>';

	$query = "SELECT * FROM $tablei WHERE id='$id'";
	$result = mQuery($query);
	$rowi = mFetchArray($result);

	//Некоторые поля остаются неизменными
	if (isset($rowi['creation_time'])) {
		$creation_time = $rowi['creation_time'];
		$_POST['creation_time'] = $creation_time;
	}
	if (isset($rowi['filename'])) {
		$filename = $rowi['filename'];
		$_POST['filename'] = $filename;
	}
	if (isset($rowi['format'])) {
		$format = $rowi['format'];
		$_POST['format'] = $format;
	}

	$query = '';
	$result = 0;
	if (!empty($rowi['sort'])) {
		$_POST['sort'] = $rowi['sort'];
	}

	if (!empty($deldocsfile)) {
		@unlink("$pref$FOLDER_FILES/$row[format]/$row[filename]");
		@unlink("$pref$FOLDER_FILES/$row[format]/$row[creation_time].$row[format]");
		@unlink("$pref$FOLDER_FILES/preview/$row[creation_time].jpg");
		$format = '';
		$_POST['format'] = '';
		$filename = '';
		$_POST['filename'] = '';
	}

	${"admin_$tablei"}->fillValuesFromPost();

	// Название картинок
	if (!isset(${"admin_$tablei"}->IMG_FIELD) || ${"admin_$tablei"}->IMG_FIELD == 'creation_time') {
		$img_id = $creation_time;
	} else {
		$img_id = $_POST[${"admin_$tablei"}->IMG_FIELD][0];
	}

	uploadImagesAndFiles($tablei,$img_id);

	$query = ${"admin_$tablei"}->updateQuery($id);
	//$result = mQuery($query);
	//echo $query;//.'->'.mysql_error();

	addLog($tablei,2,$id);

	if (isset($id) && $id > 0) {
		$query = "SELECT * FROM $tablei WHERE id = '$id'";
		$res = mQuery($query);
		$row = mFetchArray($res);
		$query = '';

		/*Дополнительные действия после сохранения*/
		if (method_exists(${"admin_$tablei"},'afterEdit')) {
			${"admin_$tablei"}->afterEdit($row);
		}
	}
}
//Удаление
else if (!empty($delitem)) {
	echo $word[$ALANG]['del'] . '<br>';

	$result = delrow($tablei,$delitem);
	addLog($tablei,3,$delitem);
	unset($delitem);
}

if (!empty($delfile)) {
	$result = delfile($delfile);
}

if (!empty($delimg)) {
	$result = delfile($delimg . '.s.jpg');
	$result = delfile($delimg . '.b.jpg');
}

// for editing
if ($id > 0) {
	$rowi = FetchID($tablei, $id);
} else {
	$rowi = [];
}

?>
<form action="<? echo $src; ?>" method="post" enctype="multipart/form-data" name="form1">
  <input name="id" type="hidden" id="id" value="<? if (empty($delitem) && !empty($id)) echo $id; ?>">
  <input name="tabler" type="hidden" id="tabler" value="<? if (!empty($tabler))echo $tabler; ?>">
  <input name="tablei" type="hidden" id="tablei" value="<? echo $tablei; ?>">
  <input name="under" type="hidden" id="under" value="<? if (!empty($under))echo $under; ?>">
  <input name="posted" type="hidden" id="posted" value="1">
  
  <?php
  //Adding
  if (!$id) {
  	if (!empty(${"admin_$tablei"}->SUB_NUM)) {
  		$sub_num = ${"admin_$tablei"}->SUB_NUM;
  	} else {
  		$sub_num = 1;
  	}

  	/*for ($i=0;$i<$sub_num;$i++)
  	{*/
  	if (isset($_REQUEST['under'])) {
  		$rowi[$under_field] = $_REQUEST['under'];
  	}
  	if (!isset($_REQUEST[$under_field])) {
  		$_REQUEST[$under_field] = $under;
  		$rowi[$under_field] = $under;
  	}

  	echo '<strong>' . $word[$ALANG]['addnew'] . ':</strong>
		';

  	$basicFields = AddFields($tablei, ${"admin_$tablei"}->fld, $rowi, $id);
  	// Langs
  	if (${"admin_$tablei"}->MULTI_LANG || !empty($basicFields)) {
  		foreach ($LANGS as $lang => $langName) {
  			if ($lang == 1) {
  				continue;
  			}

  			echo implode("\n", $basicFields[$lang]);
  		}
  	}

  	for ($im = 1; $im <= ${"admin_$tablei"}->IMG_NUM; $im++) {
  		echo '<p class=txt><strong>' . $word[$ALANG]['image'] . ' ' . ${"admin_$tablei"}->IMG_BIG_SIZE . ' pх:</strong>';
  		printImageInputs($src, $tablei, $rowi, $im, 'b');
  		echo '</p>';
  	}

  	// File input
  	if ($formatpos != null) {
  		if (${"admin_$tablei"}->fld[$formatpos]->txt == 'format') {
  			$file_an = $word[$ALANG]['loadfile'];
  		} else {
  			$file_an = ${"admin_$tablei"}->fld[$formatpos]->txt;
  		}
  		printUpFile($rowi,$file_an);
  	}

	  //    }
  } else { //Редактирование
  	$basicFields = AddFields($tablei,${"admin_$tablei"}->fld,$rowi,$id);

  	// Langs
  	if (${"admin_$tablei"}->MULTI_LANG || !empty($basicFields)) {
  		foreach ($LANGS as $lang => $langName) {
  			if ($lang == 1) {
  				continue;
  			}

  			echo implode("\n", $basicFields[$lang]);
  		}
  	}

  	for ($im = 1; $im <= ${"admin_$tablei"}->IMG_NUM; $im++) {
  		echo '<p class=txt><strong>' . $word[$ALANG]['image'] . ' ' . ${"admin_$tablei"}->IMG_BIG_SIZE . ' pх:</strong>';
  		printImageInputs($src, $tablei, $rowi, $im, 'b');
  		echo '</p>';
  	}

  	// File input
  	if ($formatpos !== null) {
  		if (${"admin_$tablei"}->fld[$formatpos]->txt == 'format') {
  			$file_an = $word[$ALANG]['loadfile'];
  		} else {
  			$file_an = ${"admin_$tablei"}->fld[$formatpos]->txt;
  		}
  		printUpFile($rowi,$file_an);
  	}
  }
?>
  <br/>
  <p class="txt">
    <button type="submit" class="btn btn-primary" onclick="submitForm();"><?=$word[$ALANG]['save'] . ' ' . ${"admin_$tablei"}->NAME2?></button>
  </p>
</form>
<?
$insert_item_text = (isset(${"admin_$tablei"}->NAME2)?
		$word[$ALANG]['addnew']." ".${"admin_$tablei"}->NAME2
		:$word[$ALANG]['insertitem_norub'] );


if (!empty($id) && empty($delitem)) {
        echo "<br/> <strong><a onclick=\"confirming('".$word[$ALANG]['delthis']."?','$src?tabler=$tabler&tablei=$tablei&under=$under&delitem=$id')\" href=\"#\"><i class=\"glyphicon glyphicon-trash\"></i> ".$word[$ALANG]['delthis']."</a></strong> ";
        echo "<strong><a href=$src?tabler=$tabler&tablei=$tablei&id=0&under=$under>".'<i class="glyphicon glyphicon-plus"></i>&nbsp;' . $insert_item_text . "</a></strong><br>";
}
    echo "<br/>";
    
if ($tabler == $tablei && empty($under)) {
	$under = $rowi[$under_field];
}    

listtable($src, $tablei, $tabler, $tablei, $under, ${"admin_$tablei"}->SORT, 0);
?><br />
<!--<? echo "Tables: $tabler, $tablei"; ?>-->
</body>
</html>
