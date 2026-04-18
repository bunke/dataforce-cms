<?php
//importing companies via API
if (isset($_GET['api_import_comp']) && $_GET['api_import_comp'] == 1) {
	require_once('init_yii.php');
	try {
		\Yii::$app->cni_api->importCompanies();
		header('Location: /admin/catalog.php?tabler=&tablei=companies&srci=items.php&under=-1',true,301);
		exit;
	} catch (Exception $ex) {
		$companies_import_res = $ex->errorInfo[2];
	}
}

header('Pragma: no-cache');
header('Cache-Control: no-cache');
$version = 'catalog v 23.0';

//Init
if (!empty($_REQUEST['tabler'])) {
	$tabler = $_REQUEST['tabler'];
} else {
	$tabler = '';
}

if (!empty($_REQUEST['tablei'])) {
	$tablei = $_REQUEST['tablei'];
} else {
	$tablei = '';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
<html dir="ltr" lang="en-US" xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
        
        <?php
            $App->insertHead();
?>
        
        <title><?php echo $PROJECT_NAME . ': ' . $word[$ALANG]['adminpanel']; ?></title>
        
<script type="text/javascript" src="plugins/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="plugins/ckfinder/ckfinder.js"></script>
<script language="javascript">
function ListRubs($under_name,addrub,delrub)
{
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request_r = new XMLHttpRequest();
            if (http_request_r.overrideMimeType) {
                http_request_r.overrideMimeType('text/xml');
				     // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request_r = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request_r = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request_r) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }

	http_request_r.onreadystatechange = function(){
		// do the thing
		//alert('111');
		alertContents(http_request_r,'rubs');
	}
	
	//http_request_r.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	http_request_r.open('GET', 'ajax/listrubs.php?tabler=<? echo (isset(${"admin_$tabler"}->TABLE_UNDER_DOP)?${"admin_$tabler"}->TABLE_UNDER_DOP:$tabler);?>&tablei=<? echo $tabler; ?>&under='+$under_name+'&id=<? echo (isset($id)?$id:0);?>&addrub='+addrub+'&delrub='+delrub, true);
	http_request_r.send(null);
}

function ListCat(tabler,tablei,under,sub_under,rpage,ipage)
{
        //return 0;
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request2 = new XMLHttpRequest();
            if (http_request2.overrideMimeType) {
                http_request2.overrideMimeType('text/xml');
				     // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request2 = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request2 = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request2) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }

	http_request2.onreadystatechange = function(){
		// do the thing
		alertContents(http_request2,'listcat_div');
	}
	
	//http_request2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	http_request2.open('GET', 'ajax/listcat.php?tabler='+tabler+'<?php

       if (isset(${"admin_$tablei"}->TYPE_PARAM) && !empty($_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)])) {
       	echo '&' . (${"admin_$tablei"}->TYPE_PARAM) . '=' . $_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)];
       }
?>&tablei='+tablei+'&rpage='+rpage+'&ipage='+ipage+'&act=items.php&under='+under+'&sub_under='+sub_under, true);
	http_request2.send(null);
}

function submitForm() {
	
	<?php

	$i = 0;
while (isset(${"admin_$tabler"}->fld[$i]->name)) {
	if (!empty(${"admin_$tabler"}->fld[$i]->extra_param['validation'])) {
		?>	
		    var regExpField = '<?=${"admin_$tabler"}->fld[$i]->name ?>';
		    var regExp = <?=${"admin_$tabler"}->fld[$i]->extra_param['validation']['rule'] ?>;
			if (!regExp.test($('#' + regExpField).val())) {
		        alert('Поле "<?=${"admin_$tabler"}->fld[$i]->txt ?>" <?=${"admin_$tabler"}->fld[$i]->extra_param['validation']['alert'] ?>');
		        $('#' + regExpField).focus();
		        return false;
		    }	
		<?php
	}
	$i++;
}
?>
	document.forms.form1.submit();
    
}

</script>
</head>
<?php

//SORT order
if (!empty($tabler) && strstr(${"admin_$tabler"}->SORT,'sort') !== FALSE) {
	$rub_sort = 1;
}

//Adding Fields to Table
if (!empty($tabler)) {
	syncTableFields($tabler);
}

//----------------------------Extra params
function crtParams()
{
	global $TABLE_PARAMS_ASSOC_RUBS,$TABLE_PARAMS_VALS;

	mQuery("CREATE TABLE IF NOT EXISTS `$TABLE_PARAMS_ASSOC_RUBS` (
		  `rubID` int(11) NOT NULL,
		  `paramID` int(11) NOT NULL,
		  KEY `rubID` (`rubID`,`paramID`))
		  ENGINE=MyISAM DEFAULT CHARSET=cp1251;");

	mQuery("CREATE TABLE IF NOT EXISTS `$TABLE_PARAMS_VALS` (
		  `itemID` int(11) NOT NULL,
		  `paramID` int(11) NOT NULL,
		  `value_1` varchar(100) NOT NULL,
		  `value_2` varchar(100) NOT NULL,
		  `valueID` int(11) NOT NULL,
		  KEY `itemID` (`itemID`,`paramID`)) 
		  ENGINE=MyISAM DEFAULT CHARSET=cp1251;");

	echo ' Creating params ' . mError();
}

function genExtra($under)
{
	if (!($under > 0)) {
		$under = -1;
	}

	global $TABLE_PARAMS, $TABLE_PARAMS_ASSOC_RUBS, $_GET,$word,$ALANG;
	$tbl = "<h3 style=\"background-color: rgb(249, 249, 249); cursor: pointer; border-bottom:1px dashed #CCC;\" onclick=\"popUp('extra_params')\"><img src=\"_layout/images/icons/icon-list.png\" border=\"0\"> " . $word[$ALANG]['extraparams'] . '</h3>
	<div style="width:500px;background-color: rgb(249, 249, 249); display: none;" id="extra_params">
	<table border="0" cellspacing="1" cellpadding="1">';
	$under_name = 'under';
	$query = "SELECT * FROM $TABLE_PARAMS where $under_name=-1";
	$result = mQuery($query);
	$num = mNumRows($result);
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$row['name_1'] = stripslashes($row['name_1']);
		$tbl .= "<tr>
    <td><strong>$row[name_1]</strong> [ <a href=\"javascript:selectGroupChbx('chbx_$row[id]')\">all</a> ]</td>
    <td></td>
  </tr>";
		$query2 = "SELECT * FROM $TABLE_PARAMS where $under_name=$row[id]";
		$result2 = mQuery($query2);
		$num2 = mNumRows($result2);
		for ($i2 = 0; $i2 < $num2; $i2++) {
			$row2 = mFetchArray($result2);
			$row2['name_1'] = stripslashes($row2['name_1']);

			if (isset($_GET['addid']) && $_GET['addid'] > 0) {
				$query3 = "SELECT * FROM $TABLE_PARAMS_ASSOC_RUBS where rubID=$_GET[addid] and paramID=$row2[id]";
				$result3 = mQuery($query3);
				//echo $query3;
				if (mError() != '') {
					echo $query3 . mError();
				}
				$num3 = mNumRows($result3);
				if ($num3 > 0) {
					$chkd = 'checked="checked"';
				} else {
					$chkd = '';
				}
			} else {
				$query3 = "SELECT * FROM $TABLE_PARAMS_ASSOC_RUBS where rubID=$under and paramID=$row2[id]";
				$result3 = mQuery($query3);
				//echo $query3;
				if (mError() != '') {
					echo $query3 . mError();
				}
				$num3 = mNumRows($result3);
				if ($num3 > 0) {
					$chkd = 'checked="checked"';
				} else {
					$chkd = '';
				}
			}
			if (($i2 + 1) % 2 == 0) {
				$rcolor = '#f9f9f9';
			} else {
				$rcolor = '#ffffff';
			}
			$tbl .= "<tr style=\"background-color:$rcolor\">
		<td>&nbsp;$row2[name_1]</td>
		<td><input type=\"checkbox\" class=\"chbx_$row[id]\" name=\"chb_$row2[id]\" id=\"chb_$row2[id]\" value=\"1\" $chkd /></td>
	  </tr>";
		}
	}
	$tbl .= '</table></div><br>';
	return $tbl;
}

function extraEdit($id)
{
	global $TABLE_PARAMS,$TABLE_PARAMS_ASSOC_RUBS;
	$under_name = 'under';
	//o4istka
	mQuery("DELETE FROM $TABLE_PARAMS_ASSOC_RUBS WHERE rubID=$id");
	//new
	$query2 = "SELECT * FROM $TABLE_PARAMS where $under_name<>-1";
	$result2 = mQuery($query2);
	$num2 = mNumRows($result2);
	$addQ = "INSERT INTO $TABLE_PARAMS_ASSOC_RUBS (`rubID` ,`paramID`) VALUES";
	$first = 1;
	$n = 0;
	for ($i2 = 0; $i2 < $num2; $i2++) {
		$row2 = mFetchAssoc($result2);

		if (isset($_POST['chb_' . $row2['id']])) {
			if ($first == 1) {
				$addQ .= ' ';
				$first = 0;
			} else {
				$addQ .= ', ';
			}
			$addQ .= "('$id', '$row2[id]')";
			$n++;
		}
	}
	if ($n > 0) {
		mQuery($addQ);
	}
}

//Parent rub ~ under
if (!empty($tabler) && isset(${'admin_' . $tabler}->FIELD_UNDER)) {
	$under_name = ${'admin_' . $tabler}->FIELD_UNDER;
} else {
	$under_name = 'under';
}

$under = isset($_REQUEST[$under_name])?(int)$_REQUEST[$under_name]:(isset($_GET['under'])?(int)$_GET['under']:-1);

// Access
if (!empty($tabler) && $under == -1 && method_exists(${'admin_' . $tabler}, 'getRoot')) {
	$under = ${'admin_' . $tabler}->getRoot();
}

if (isset($addid)) {
	$under = $addid;
}

$src = 'catalog.php';
if (!isset($srci)) {
	$srci = 'items.php';
}

if (!isset($_REQUEST['rubs_list_type'])) {
	$RUBS_LIST_TYPE = 'tree';
} else {
	$RUBS_LIST_TYPE = $_REQUEST['rubs_list_type'];
}

$creation_time = (date('U'));
$_POST['creation_time'] = $creation_time;

if (!empty($tabler)) {
	${"admin_$tabler"}->SORT = str_replace('%20',' ',${"admin_$tabler"}->SORT);
}

if (!empty($tabler) && isset(${"admin_$tabler"}->ECHO_NAME)) {
	$echo_rub_name = ${"admin_$tabler"}->ECHO_NAME;
} else {
	$echo_rub_name = 'name_1';
}

if (!empty($tabler) && ${"admin_$tabler"}->MULTI_LANG) {
	$echo_rub_name = $echo_rub_name . '_' . mainLang();
}

if (!empty($tablei) && isset(${"admin_$tablei"}->ECHO_NAME)) {
	$echo_item_name = ${"admin_$tablei"}->ECHO_NAME;
} else {
	$echo_item_name = 'name_1';
}

if (!empty($tablei) && isset(${'admin_' . $tablei}->FIELD_UNDER)) {
	$under_item_name = ${'admin_' . $tablei}->FIELD_UNDER;
} else {
	$under_item_name = 'under';
}

//Fetching
if (!empty($id) || !empty($del)) { //dlya edita
	$query = "SELECT * FROM $tabler WHERE `id`='" . (!empty($id)?$id:$del) . "'";
	$result = mQuery($query);
	$row = mFetchArray($result);
	$i = 0;
	if (is_array($row) && empty(${"admin_$tabler"}->RUBS_NO_UNDER) && !empty($row[$under_name]) && $row[$under_name] > 0) {
		$under = $row[$under_name];
	}
}

// Parent rub
$levelOfRub = 0;

if ($under > 0 && $tabler != '') {
	$rowu = FetchID($tabler, $under);

	if (isset(${"admin_$tabler"}->RUBS_NO_UNDER) || $rowu[$under_name] == -1) {
		$levelOfRub = 1;
	}

	$rowu[$echo_rub_name] = stripslashes($rowu[$echo_rub_name]);

	if (!isset($_REQUEST[$under_name])) {
		$_REQUEST[$under_name] = $under;
	}
} elseif (!($under > 0)) {
	$rowu[$echo_rub_name] = $word[$ALANG]['root'];
}

//Global parameters
$gp = "tabler=$tabler&tablei=" . (!empty($tablei)?$tablei:'') . "&srci=$srci";

if (isset(${"admin_$tablei"}->TYPE_PARAM) && !empty($_REQUEST[${"admin_$tablei"}->TYPE_PARAM])) {
	$gp .= '&' . (${"admin_$tablei"}->TYPE_PARAM) . '=' . $_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)];
}

if (isset($_GET['drt']) && $_GET['drt'] == 1) { //drop rubs table
	mQuery("DROP TABLE $tabler");
}
if (isset($_GET['crt']) && $_GET['crt'] == 1) { //creating new rubs table
	echo crtQuery($tabler,${"admin_$tabler"}->fld);

	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
		crtParams();
	}
	echo @mError();
}

if (isset($crtparams)) {
	crtParams();
}

//set all checkboxes
if (isset($under) && $tabler != '') {
	if (isset($rowu['sp_where']) && $rowu['sp_where'] != '') {
		$tw = explode('ORDER',$rowu['sp_where']);
		$whck = 'WHERE ' . $tw[0];
	} elseif ($under > 0) {
		$whck = "WHERE $under_name=$under";
	} else {
		$whck = 'WHERE 1';
	}
	//echo $whck;
} else {
	$whck = '';
}

if (isset($_GET['set_1'])) {
	mQuery('UPDATE ' . $_GET['act_table'] . ' SET `' . $_GET['set_1'] . "`=1 $whck");
}
if (isset($_GET['set_0'])) {
	mQuery('UPDATE ' . $_GET['act_table'] . ' SET `' . $_GET['set_0'] . "`=0 $whck");
}

//o4istka cache
if ($_POST || ($_GET['del'] > 0)) {
	CleanSiteCache();
}

if (isset($_GET['sortid']) && $_GET['sortid'] > 0 && isset($_GET['sort'])) {
	// Choosing table for sorting
	if (isset($_GET['sortmode']) && $_GET['sortmode'] == 'rub') {
		$tablesort = $tabler;
	} else {
		$tablesort = $tablei;
	}

	$query = "SELECT id,sort FROM $tablesort WHERE id=" . $_GET['sortid'];
	$res = mQuery($query);
	$rowi = mFetchAssoc($res);

	if ($tabler != '') {
		$und = 'AND ' . ((isset($_GET['sortmode']) && $_GET['sortmode'] == 'rub')?$under_name:$under_item_name) . " = $under";
	} else {
		$und = '';
	}

	if ($_GET['sort'] == 'up' || $_GET['sort'] == 'down') {
		if ($_GET['sort'] == 'up') {
			$query = "SELECT id,sort FROM $tablesort WHERE sort " . ($_GET['sort_set'] == 'desc'?'>':'<') . " $rowi[sort] $und ORDER BY sort " . ($_GET['sort_set'] == 'desc'?'ASC':'DESC') . '';
		}
		if ($_GET['sort'] == 'down') {
			$query = "SELECT id,sort FROM $tablesort WHERE sort" . ($_GET['sort_set'] == 'desc'?'<':'>') . "$rowi[sort] $und ORDER BY sort " . ($_GET['sort_set'] == 'desc'?'DESC':'ASC') . '';
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
		$query = "SELECT id,sort FROM $tablesort WHERE id<>'' $und ORDER BY sort " . ($_GET['sort_set'] == 'desc'?'ASC':'DESC') . '';
		$res = mQuery($query);
		$row2 = mFetchAssoc($res);
		if ($row2['sort'] != '') {
			mQuery("UPDATE $tablesort set sort='" . ($_GET['sort_set'] == 'desc'?($row2['sort'] - 1.1):($row2['sort'] + 1.1)) . "' WHERE id=$rowi[id]");
		}
	}
}

if (!empty($tabler)) {
	// Fill values of fields
	extract(${"admin_$tabler"}->fillValuesFromPost());

	// Images count
	if (isset(${"admin_$tabler"}->IMG_NUM) && ${"admin_$tabler"}->IMG_NUM > 0) {
		$imgnum = ${"admin_$tabler"}->IMG_NUM;
	} else {
		$imgnum = 0;
	}
}
?>
        
<body >
<?php
//MENU
include('controllers/menu.php');
?>

<!--<script>
	$(function() {
	$( "#tabs" ).tabs();
	});
</script> -->
<div align="center">
  <?php
//Manipulations
if (isset($id) || (!empty($tabler) && isset(${${"admin_$tabler"}->fld[0]->name})) || isset($del) || isset($delitem)) {
	//Add
	if ((!isset($id) || (isset($id) && !($id <> ''))) && !empty($tabler)
		&& (isset($_POST[${"admin_$tabler"}->fld[0]->name]) || isset($_POST[${"admin_$tabler"}->fld[0]->name . '_1']))) {
		//echo $word[$ALANG]['add']."<br>";

		/*$query=addingQuery($tabler,${"admin_$tabler"}->fld);
		//echo $query;
		$result = mQuery($query);
		if ($result==1) {}//echo "<b>".$word[$ALANG]['ok']."</b><br>";
		else echo $query."->".mError();
		$query="SELECT * FROM $tabler ORDER by id desc";
		$result= mQuery($query);
		$rownew=mFetchAssoc($result);*/

		$rownew = ${"admin_$tabler"}->insertQuery();
		$rownew = FetchID($tabler, $rownew['id']);

		if (!isset(${"admin_$tabler"}->IMG_FIELD) || ${"admin_$tabler"}->IMG_FIELD == 'creation_time') {
			$img_id = $creation_time;
		} else {
			$img_id = $rownew[${"admin_$tabler"}->IMG_FIELD];
		}

		uploadImagesAndFiles($tabler,$img_id);

		${"admin_$tabler"}->updateQuery($rownew['id']);
		/*$query = updQuery($tabler,${"admin_$tabler"}->fld,$rownew['id']);
		$result = mQuery($query);
		*/
		addLog($tabler,1,$rownew['id']);

		if (method_exists(${"admin_$tabler"},'afterAdd')) {
			${"admin_$tabler"}->afterAdd($rownew);
		}

		// If rubs has SORT - setting sort after add
		if (isset($rub_sort) && $rub_sort == 1) {
			mQuery("update $tabler set sort='$rownew[id]' WHERE id=$rownew[id]");
		}

		// Extra params old
		if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
			extraEdit($rownew['id']);
		} //EXTRA PARAMS EDIT

		// Extra params
		if (!empty(${"admin_$tabler"}->TABLE_EXTRA_PARAMS)) {
			saveExtraParamsAssocTable($rownew['id']);
		} //EXTRA PARAMS EDIT

		// Tags save
		if (${"admin_$tabler"}->USE_TAGS) {
			saveTags($tabler, $rownew['id']);
		}

		// Domains dynamic Texts edit
		if (isset(${"admin_$tabler"}->dynamicFields)) {
			saveDomainsTexts($tabler, $rownew['id']);
		}

		// RELATIONS
		if (!empty(${"admin_$tabler"}->TABLE_RELATIONS)) {
			saveCatRelations($rownew['id'],$rownew['id'],$under_name);
		}

		// Flush query
		$query = '';
		$result = 0;

		// Returning to list of rubs after adding new rub
		if (!empty(${"admin_$tabler"}->LIST_AFTER_ADD)) {
			unset($id, $addid);
		}
	}

	if (isset($delimg)) {
		$result = delfile($delimg . 's0.jpg');
		$result = delfile($delimg . '.s.jpg');
		$result = delfile($delimg . '.b.jpg');
	}

	if (isset($delfile)) {
		$result = delfile($delfile);
		echo 'X ' . $delfile;
	}

	if (isset($delfile2)) {
		$result = delfile($delfile2);
		echo ' X ' . $delfile2;
	}

	//Editing
	if (isset($id) && !empty($id) && (isset($_POST[${"admin_$tabler"}->fld[0]->name]) || isset($_POST[${"admin_$tabler"}->fld[0]->name . '_1']))) {
		//echo $word[$ALANG]['editing'];

		$row = FetchID($tabler,$id);

		//Перед сохранением новых данных можно что-то проверить и сделать
		if (method_exists(${"admin_$tabler"},'beforeEdit')) {
			${"admin_$tabler"}->beforeEdit($row);
		}

		/*Некоторые поля остаются неизменными*/
		if (isset($row['creation_time'])) {
			$creation_time = $row['creation_time'];
			$_POST['creation_time'] = $creation_time;
		}

		if (isset($row['format'])) {
			$format = $row['format'];
			$_POST['format'] = $format;
		}

		if (isset($row['sort'])) {
			$sort = $row['sort'];
			$_POST['sort'] = $sort;
		}

		if (isset($row['filename'])) {
			$_POST['filename'] = $row['filename'];
		}

		if (!empty($NO_ADMIN)) {
			$pref = '';
		} else {
			$pref = '../';
		}

		if (!empty($deldocsfile)) {
			@unlink($pref . "$FOLDER_FILES/$row[format]/$row[filename]");
			@unlink($pref . "$FOLDER_FILES/$row[format]/$row[creation_time].$row[format]");
			@unlink($pref . "$FOLDER_FILES/preview/$row[creation_time].jpg");
			$format = '';
			$_POST['format'] = '';
			$filename = '';
			$_POST['filename'] = '';
		}

		${"admin_$tabler"}->fillValuesFromPost();

		if (!isset(${"admin_$tabler"}->IMG_FIELD) || ${"admin_$tabler"}->IMG_FIELD == 'creation_time') {
			$img_id = $creation_time;
		} else {
			$img_id = $_POST[${"admin_$tabler"}->IMG_FIELD];
		}

		uploadImagesAndFiles($tabler,$img_id);

		//EXTRA PARAMS OLD EDIT
		if (!empty($tablei) && !empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
			extraEdit($id);
		}

		//Extra params edit
		if (!empty(${"admin_$tabler"}->TABLE_EXTRA_PARAMS)) {
			saveExtraParamsAssocTable($id);
		}

		// Tags save
		if (${"admin_$tabler"}->USE_TAGS) {
			saveTags($tabler, $id);
		}

		// Domains dynamic Texts edit
		if (isset(${"admin_$tabler"}->dynamicFields)) {
			saveDomainsTexts($tabler, $id);
		}

		//RELATIONS
		if (!empty(${"admin_$tabler"}->TABLE_RELATIONS)) {
			saveCatRelations($id,$id,$under_name);
		}

		$query = ${"admin_$tabler"}->updateQuery($id);
		//$result = mQuery($query);
		if (mError() != '') {
			echo '<br/><strong>Error:</strong> ' . $query . ' ' . mError() . '<br/>';
		}

		addLog($tabler,2,$id);

		$query = '';

		if (isset($id)) {
			$row = FetchID($tabler,$id);

			if (method_exists(${"admin_$tabler"},'afterEdit')) {
				${"admin_$tabler"}->afterEdit($row);
			}
		}
	}
	//Del
	if (isset($del)) {
		//echo "<strong>".$word[$ALANG]['del']." - OK</strong>";

		if (method_exists(${"admin_$tabler"},'beforeDelete')) {
			${"admin_$tabler"}->beforeDelete($row);
		}

		//Recursion deletion
		$result = DelUni($tabler,$tablei,$del);

		//Extra params cleaning
		if (!empty($tablei) && !empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
			mQuery("DELETE FROM $TABLE_PARAMS_ASSOC_RUBS WHERE rubID=$del");
		}

		if (!empty(${"admin_$tablei"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$tablei"}->TABLE_UNDER_DOP) || ${"admin_$tablei"}->TABLE_UNDER_DOP == $tabler)) {
			mQuery('DELETE ' . ${"admin_$tablei"}->TABLE_RUB_ASSOC . " WHERE $under_name=$del");
		}
		//$result=delrow($tabler,$del);

		addLog($tabler,3,$del);
	}

	//Del item
	if (isset($delitem) && $delitem > 0) {
		echo $word[$ALANG]['del'];

		// Fetching before Delete
		$query = "SELECT * FROM $tablei WHERE id='$delitem'";
		$result = mQuery($query);
		$row = mFetchArray($result);

		if (method_exists(${"admin_$tablei"},'beforeDelete')) {
			${"admin_$tablei"}->beforeDelete($row);
		}

		$result = delrow($tablei,$delitem);
		if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC)) {
			mQuery('DELETE FROM ' . ${"admin_$tablei"}->TABLE_RUB_ASSOC . ' WHERE recID=' . $delitem);
		}
		addLog($tablei,3,$delitem);
	}

	if (isset($crt)) {
		$query = crtQuery($tabler,${"admin_$tabler"}->fld);
	}
	if (isset($query) && strlen($query) > 10) {
		$result = mQuery($query);
	}

	if (isset($result) && $result) {
		//echo "<br/><strong>".$word[$ALANG]['ok']."</strong><br>";
	} elseif (mError() != '') {
		echo $query . '->' . mError();
	}

	//For edit
	if (isset($id)) {
		$row = FetchID($tabler,$id);
	}
}
?>
</div>

<a name="header"></a>
    <div class="container-fluid">
        <div class="row">
	<?php if ($tabler != '' && !isset(${"admin_$tabler"}->NO_LEFT_RUBS)) {
		$mainColClass = 'col-md-8 col-md-offset-4 col-lg-9 col-lg-offset-3';
		?>
			 <aside class="col-md-4 col-lg-3" id="sidebar">
				 <h4><? echo $word[$ALANG]['rubricator']; ?></h4>
				 <div id="listcat_div">
				 </div>
				 <script language="javascript">ListCat('<?=$tabler?>','<?=$tablei?>',<?=$under?>);</script>
				</aside> 
	<?php
	} // end left col
	else {
		$mainColClass = 'col-md-10 col-lg-12';
	}
?>			 

    
      <main class="<?=$mainColClass?>" id="main">
                <div class="row">
                    <ul class="breadcrumb">
<?php

echo " <li><a href=catalog.php?$gp&under=-1><i class=\"glyphicon glyphicon-book\"></i>&emsp;" . (isset(${"admin_$tabler"}->NAME)?${"admin_$tabler"}->NAME:(isset(${"admin_$tablei"}->NAME)?${"admin_$tablei"}->NAME:${"admin_$tablei"}->name)) . '</a></li>';

if (isset($tabler) && $tabler != '') {
	if (isset($rowu[$under_name]) && $rowu[$under_name] > 0) {
		$rowuu = FetchID($tabler,$rowu[$under_name]);
		if ($rowuu[$echo_rub_name] != '') {
			$levelOfRub = 2;
			echo "<li><a href=\"catalog.php?$gp&under=$rowuu[id]\">" . $rowuu[$echo_rub_name] . '</a></li>';
		}

		//Max level
		if ($rowuu[$under_name] > 0) {
			$levelOfRub = 3;
		}
	}

	if (!empty($rowu['id'])) {
		echo "<li><a href=\"catalog.php?$gp&under=$rowu[id]\">" . $rowu[$echo_rub_name] . '</a></li>';
	}

	if (isset($row['id']) && $row['id'] > 0 && empty($del) && empty($delitem)) {
		echo "<li><a href=\"catalog.php?$gp&under=$row[id]\">" . $row[$echo_rub_name] . '</a></li>';
	}
}

// Insertion phrase
if (isset($TABLE_PARAMS) && $tabler == $TABLE_PARAMS) {
	if (isset($rowu[$under_name]) && $rowu[$under_name] == -1) {
		$instert = $word[$ALANG]['insertparam'];
	} elseif (isset($rowu[$under_name]) && $rowu[$under_name] > 0) {
		$instert = '';
	} else {
		$instert = $word[$ALANG]['insertparamg'];
	}
} else {
	$instert = '';

	if (!empty($tabler) && is_array(${"admin_$tabler"}->NAME2)) {
		if (isset(${"admin_$tabler"}->NAME2[$levelOfRub])) {
			$instert = $word[$ALANG]['insert_'] . ' ' . ${"admin_$tabler"}->NAME2[$levelOfRub];
		}
	} else {
		$instert = $word[$ALANG]['insert_'] . ' ' . (isset(${"admin_$tabler"}->NAME2)?${"admin_$tabler"}->NAME2:$word[$ALANG]['rubric2']);
	}
}

?>
</ul>
</div>
<!-- ACTIONS -->
 <div class="row">
                    <div class="col-md-7 col-lg-8 clearfix">
                        <h1 class="pull-left"><i class="glyphicon glyphicon-folder-open"></i>&emsp;<?php
                        if (empty($id)) {
                        	echo (isset(${"admin_$tabler"}->NAME)?${"admin_$tabler"}->NAME:(isset(${"admin_$tablei"}->NAME)?${"admin_$tablei"}->NAME:${"admin_$tablei"}->name)) . '/' . $rowu[$echo_rub_name];
                        } else {
                        	echo $row[$echo_rub_name];
                        }
?>&emsp;</h1>
                        <div class="btn-group pull-left" role="group" aria-label="...">
                            
                            <a href="<?="$src?$gp&under=" . (isset($rowu[$under_name])?$rowu[$under_name]:'-1')?>" class="btn btn-default" data-toggle="tooltip" title="На уровень вверх" role="button">
                                <i class="glyphicon glyphicon-level-up"></i>
                            </a>
                            <?php
    if (isset($id) || isset($addid)) {
    	if (!isset($id) || (isset($id) && !($id <> ''))) {
    		//echo "<b>".$word[$ALANG]['newrub']."</b>";
    	} else {
    		if (!isset($row['no_del']) || (isset($row['no_del']) && $row['no_del'] != 1)) {
    			?>
			<a href="#" onClick="<?="confirming('" . $word[$ALANG]['del'] . ' ' . ((isset($id) && isset($row[$echo_rub_name]))?$row[$echo_rub_name]:'') . "?','$src?$gp&under=" . ((isset($id) && isset($row[$under_name]))?$row[$under_name]:(isset($rowu[$under_name])?$rowu[$under_name]:'-1')) . '&del=' . (isset($id)?$id:$row['id']) . "')"?>" class="btn btn-default" data-toggle="tooltip" title="<?=$word[$ALANG]['del']?>" role="button">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>
			<?php
    		}
    		//echo " / <strong><a  href=# title=\"".$word[$ALANG]['del']."\">".$word[$ALANG]['del']." ".$row[$echo_rub_name]." ".imdelm."</a></strong>";
    	}
    } elseif ($under > 0) {
    	?>
                            <a href="<?="$src?$gp&under=" . ((isset($id) && isset($row[$under_name]))?$row[$under_name]:(!empty($rowu[$under_name])?$rowu[$under_name]:'-1')) . '&id=' . (isset($id)?$id:$rowu['id'])?>" class="btn btn-default" data-toggle="tooltip" title="<?=$word[$ALANG]['edit']?>" role="button">
                                <i class="glyphicon glyphicon-edit"></i>
                            </a>
                            <?php
    }
?>
                            <?php if (!empty($tablei) && $levelOfRub >= ${"admin_$tablei"}->LEVEL_OF_RUBS) {
                            	?>
								<a href="<?="$srci?$gp&under=" . (isset($id)?$id:$under)?>" class="btn btn-default" data-toggle="tooltip" title="Перейти к материалам" role="button">
                                <i class="glyphicon glyphicon-file"></i>
                            </a>
                            <?php } ?>
                        </div>
                        <?php if (empty(${"admin_$tabler"}->NO_LEFT_RUBS)) {
                        	?>
                        <div class="btn-group hidden-xs" data-toggle="buttons">
                            <button type="button" class="btn btn-default" id="full_screen" data-toggle="tooltip" title="Скрыть / показать боковую панель">
                                <i class="glyphicon glyphicon-resize-horizontal"></i>
                            </button>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="col-md-5 col-lg-4">
                        <form action="catalog.php#items" method="get">
							<?php
							 if (!empty(${"admin_$tablei"}->TYPE_PARAM) && !empty($_REQUEST[${"admin_$tablei"}->TYPE_PARAM])) {
							 	echo '<input name="' . ${"admin_$tablei"}->TYPE_PARAM . '" type="hidden" value="' . $_REQUEST[${"admin_$tablei"}->TYPE_PARAM] . '" />';
							 }
?>
							
							<input name="srci" type="hidden" value="<?=($srci)?>" />
							<input name="tabler" type="hidden" value="<?=(isset($tabler)?$tabler:'')?>" /> 
		<input name="tablei" type="hidden" value="<?=(isset($tablei)?$tablei:'')?>" /> 
		<input name="under" type="hidden" value="<?=($under)?>" />
                            <div class="form-group">
                                <label class="sr-only" for="search"><?=$word[$ALANG]['search']?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search_w" placeholder="<?=$word[$ALANG]['search']?>" <?=(isset($_GET['search_w'])?'value="' . $_GET['search_w'] . '"':'')?>>
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary"><i class="glyphicon glyphicon-search"></i></button>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
 <!-- ACTIONS END -->
<?php
 if (0) {
 	?>
 <!-- FILTER AND PRICE CORR -->
 <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <i class="glyphicon glyphicon-resize-vertical"></i> Фильтры и цены
                                </a>
                            </h4>
                        </div>
                        <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body" role="tabpanel">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation" class="active">
                                        <a href="#filter" aria-controls="filter" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-filter"></i>&emsp;Фильтры</a>
                                    </li>
                                    <li role="presentation">
                                        <a href="#price" aria-controls="price" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-tags"></i>&emsp;Цены</a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane active" id="filter">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <form class="form-inline">
                                                    <div class="form-group">
                                                        <label for="select1">Фильтр по бренду</label>
                                                        <select class="form-control" id="select1">
                                                            <option>Все</option>
                                                            <option>Фирма 1</option>
                                                            <option>Фирма 2</option>
                                                            <option>Фирма 3</option>
                                                            <option>Фирма 4</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">&emsp;</div>
                                                    <div class="form-group">
                                                        <label for="select2">Количество полос</label>
                                                        <select class="form-control" id="select2">
                                                            <option>Все</option>
                                                            <option>1</option>
                                                            <option>2</option>
                                                            <option>3</option>
                                                            <option>4</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">&emsp;</div>
                                                    <div class="form-group">
                                                        <label for="select3">Типоразмер</label>
                                                        <select class="form-control" id="select3">
                                                            <option>Все</option>
                                                            <option>Типоразмер 1</option>
                                                            <option>Типоразмер 2</option>
                                                            <option>Типоразмер 3</option>
                                                            <option>Типоразмер 4</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">&emsp;</div>
                                                    <div class="form-group">
                                                        <label for="select4">Тип</label>
                                                        <select class="form-control" id="select4">
                                                            <option>Все</option>
                                                            <option>Тип 1</option>
                                                            <option>Тип 2</option>
                                                            <option>Тип 3</option>
                                                            <option>Тип 4</option>
                                                        </select>
                                                    </div>
                                                    <button class="btn btn-default">Применить</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div><!--/#filter-->
                                    <div role="tabpanel" class="tab-pane" id="price">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <form class="form-inline">
                                                    <div class="form-group">
                                                        <label for="select5">Изменить цены товаров бренда</label>
                                                        <select class="form-control" id="select4">
                                                            <option>Все</option>
                                                            <option>Бренд 1</option>
                                                            <option>Бренд 2</option>
                                                            <option>Бренд 3</option>
                                                            <option>Бренд 4</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">&emsp;</div>
                                                    <div class="form-group">
                                                        <label for="input1">и этой, вложенных рубрик на</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" value="-5" />
                                                            <span class="input-group-addon">%</span>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-default">Изменить базовые цены</button>
                                                    <button class="btn btn-default">Цены = базовые</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div><!--/#price-->
                                </div><!--/tab-content-->
                            </div><!--/panel-body-->
                        </div><!--/#collapseOne-->
                    </div>
                </div><!--/panel-group-->
<!-- FILTER AND PRICE CORR END-->
<?php
 }
?>
                <hr /> 
                                      
<?php

if (isset($id) || isset($addid)) {
	echo '<form name="form1" id="form1" method="post" action="' . $src . '" enctype="multipart/form-data" style="width:95%">';

	?>
    
                    <div class="col-md-12">
                        <div role="tabpanel">
 <ul class="nav nav-tabs" role="tablist">
	<li class="nav nav-tabs" role="tablist"><a href="#tabs-1" aria-controls="first" role="tab" data-toggle="tab"><?=$word[$ALANG]['tab1']?></a></li>
	<?php
    $tabIndex = 1;
	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS) || !empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
		$tabIndex++;
		echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">Дополнительные характеристики товаров</a></li>
                        ';
	}
	if (${"admin_$tabler"}->MULTI_LANG) {
		foreach ($LANGS as $lang => $langName) {
			if ($lang == 1) {
				continue;
			}

			$tabIndex++;
			echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['lang'] . ': ' . $langName . '</a></li>
            ';
		}
	}

	// Tags tab
	if (${"admin_$tabler"}->USE_TAGS) {
		$tabIndex++;
		echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['tags'] . '</a></li>
				';
	}

	//Files
	if (method_exists(${"admin_$tabler"}, 'printFiles') || $formatpos != null) {
		$tabIndex++;
		echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['files_bank'] . '</a></li>
				';
	}

	if (isset(${"admin_$tabler"}->dynamicFields)) {
		$siteDomains = pdoFetchAll('SELECT * FROM domains ORDER by id ASC');

		foreach ($siteDomains as $domain) {
			$tabIndex++;
			echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">Домен: ' . $domain['name'] . '</a></li>
            ';
		}
	}
	?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
<div role="tabpanel" class="tab-pane active" id="tabs-1">
<?php
echo '<span class="txt">
<input name="id" type="hidden" id="id" value="' . $id . '">
<input name="tabler" type="hidden" id="tabler" value="' . $tabler . '">
<input name="tablei" type="hidden" id="tablei" value="' . $tablei . '">
<input name="srci" type="hidden" id="srci" value="' . $srci . '">';

	/*if ($id<>'' && $row['no_del']!=1) echo "[ <a  onClick=\"confirming('".$word[$ALANG]['del']."?','catalog.php?tabler=$tabler&tablei=$tablei&srci=items.php&del=$row[id]')\" href=#>".$word[$ALANG]['del']." ".$word[$ALANG]['rubric2']." ".$row[$echo_rub_name]."</a> ]<br><br>";*/

	//Pole kartinki
	if (!isset(${"admin_$tabler"}->IMG_FIELD)) {
		$img_field = 'creation_time';
	} else {
		$img_field = ${"admin_$tabler"}->IMG_FIELD;
	}

	if (isset($row) && !empty($row[$img_field])) {
		$folder = GetImageFolder($tabler,$row[$img_field]);

		if (file_exists("$pref$FOLDER_IMAGES/$folder$tabler." . $row[$img_field] . '.1.s.jpg')) {
			echo '<a href="' . $pref_url . "$FOLDER_IMAGES/$folder$tabler." . $row[$img_field] . '.1.b.jpg" target="_blank"><img src=' . $pref_url . "$FOLDER_IMAGES/$folder$tabler." . $row[$img_field] . ".1.s.jpg border=1 width=75></a><br>[ <a href=catalog.php?$gp&under=$under&id=$row[id]&delfile=" . urlencode("$pref$FOLDER_IMAGES/$folder$tabler." . $row[$img_field] . '.1.s.jpg') . '&delfile2=' . urlencode("$pref$FOLDER_IMAGES/$folder$tabler." . $row[$img_field] . '.1.b.jpg') . '>' . $word[$ALANG]['del'] . ' ' . $word[$ALANG]['image2'] . '</a> ]<br><br>
';
		}
	}

	if (isset($_REQUEST[$under_name])) {
		$row[$under_name] = $_REQUEST[$under_name];
	}

	if (!isset($row)) {
		$row = [0];
	}

	${"admin_$tabler"}->addSpFields($row,$under);

	$basicFields = AddFields($tabler,${"admin_$tabler"}->fld,$row, $id);

	// Images
	if ($imgnum > 1) {
		printImageMultiInput();
	}

	for ($i = 1; $i <= $imgnum; $i++) {
		echo '<div class="form-group">
			<label for="imgb' . $i . '">' . $word[$ALANG]['image'] . ' ' . ${"admin_$tabler"}->IMG_BIG_SIZE . 'x' . ${"admin_$tabler"}->IMG_BIG_SIZE . ' pх:</label>';
		printImageInputs($src, $tabler, $row, $i, 's');
		echo '</div>';
	}

	//Comment file
	if ($formatpos != null) {
		if (${"admin_$tabler"}->fld[$formatpos]->txt == 'format') {
			$file_an = $word[$ALANG]['loadfile'];
		} else {
			$file_an = ${"admin_$tabler"}->fld[$formatpos]->txt;
		}
		printUpFile($row,$file_an);
	}

	//Log
	if (isset($TABLE_ADMINS_LOG) && !empty($id)) {
		genLog($tabler, $id);
	}
	?>
	</div> <!-- first tab end -->
	<?php
	        $tabIndex = 1;
	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS) || !empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
		$tabIndex++;

		echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';

		//Old
		if (isset(${"admin_$tablei"}->EXTRA_PARAMS) && ${"admin_$tablei"}->EXTRA_PARAMS > 0) {
			echo genExtra((isset($row['id'])?$row['id']:NULL));
		}
		//New Extra params
		if (!empty(${"admin_$tabler"}->TABLE_EXTRA_PARAMS)) {
			echo genExtraParamsAssocTable((isset($row['id'])?$row['id']:NULL));
		}

		echo '</div>';
	}
	// Langs
	if (${"admin_$tabler"}->MULTI_LANG) {
		foreach ($LANGS as $lang => $langName) {
			if ($lang == 1) {
				continue;
			}
			$tabIndex++;
			echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';
			echo implode("\n", $basicFields[$lang]);
			echo '</div>';
		}
	}

	// Tags
	if (${"admin_$tabler"}->USE_TAGS) {
		$tabIndex++;
		echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '"><br/>';
		echo genTagsList($tabler, isset($id) ? $id : null);
		echo '</div>';
	}
	// Files
	if (method_exists(${"admin_$tabler"}, 'printFiles') || $formatpos != null) {
		$tabIndex++;
		echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '"><br/>';

		//Bank
		if (method_exists(${"admin_$tabler"}, 'printFiles')) {
			${"admin_$tabler"}->printFiles($row);
		}

		//One file
		if ($formatpos != null) {
			if (${"admin_$tablei"}->fld[$formatpos]->txt == 'format') {
				$file_an = $word[$ALANG]['loadfile'];
			} else {
				$file_an = ${"admin_$tablei"}->fld[$formatpos]->txt;
			}
			printUpFile($row,$file_an);
		}

		echo '</div>';
	}
	// Domains dynamic Tabls
	if (isset(${"admin_$tabler"}->dynamicFields)) {
		foreach ($siteDomains as $domain) {
			$tabIndex++;
			echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';
			printDynamicFields($tabler, ${"admin_$tabler"}->dynamicFields, isset($id)?$id:0, $domain['id']);
			echo '</div>';
		}
	}
	?>
</div> 
<!-- ends tab content -->
 <?php
    if (method_exists(${"admin_$tabler"}, 'showAfterTabs')) {
    	${"admin_$tabler"}->showAfterTabs($row);
    }

	?>
    <br/>
    <br/>
	<div id="ready_block">
		<button class="btn btn-success" onclick="submitForm();"><?=$word[$ALANG]['ready']?></button>
	</div>
       
</div>
</div> <!-- ends col-md-12 -->
</form>
<?php
}
?>


<?php
if (!isset($id)) {
	if (!empty($tabler) && !(isset(${"admin_$tabler"}->RUBS_NO_UNDER) && $under > 0) && (empty($tablei) || !isset(${"admin_$tablei"}->RUBS_NOT_SHOW))) {
		$rpage = !empty($_GET['rpage'])?(int)$_GET['rpage']:0;
		//Если нету пустого поля андера либо андер начальный
		if (!(isset(${"admin_$tabler"}->FIELD_UNDER) && ${"admin_$tabler"}->FIELD_UNDER == '') || $under < 0) {
			if ($RUBS_LIST_TYPE == 'tree') {
				?>
			<!-- RUBRICS HEAD -->
			<?php if (!empty($instert)) { ?>    
                <ul class="list-inline">
                    <li><strong><i class="glyphicon glyphicon-folder-close"></i>&emsp;<?=$word[$ALANG]['chldrubs']?></strong></li>

                    <li>
                        
                        <a href="<?php echo "$src?$gp&id=&under=" . (!empty($id)?$id:$under) . '&addid=' . (!empty($id)?$id:$under); ?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="Добавить рубрику">
                            <i class="glyphicon glyphicon-plus"></i>&nbsp;<?=$instert?>
                        </a>
                        
                    </li>
                </ul>
                
                <div class="table-responsive">
 <!-- RUBRICS HEAD END-->   
 <?php
				//echo "<span style=\"font-size:11px; font-weight:bold; color:#3f64b0;\"><b>".$word[$ALANG]['chldrubs']." \"".(isset($rowu[$echo_rub_name])?$rowu[$echo_rub_name]:'Корневого раздела')."\":</b></span><br><br>";

				//echo "<strong><a href=$src?$gp&id=&under=".(!empty($id)?$id:$under)."&addid=".(!empty($id)?$id:$under)." title=\"".$instert."\"><img src=\"img/add_folder.gif\" border=\"0\" /> ".$instert."</a></strong><br><br> ";
				listtable('catalog.php',  isset($tabler)?$tabler:'', isset($tabler)?$tabler:'', isset($tablei)?$tablei:'', $under, ${"admin_$tabler"}->SORT,$rpage);
				?>
		</div>
		<hr/>
		<?php }
			} else {
				$fld = new stdClass();
				$fld->table_field = 'name';
				$fld->extra_param = ['attributes' => 'onChange="document.location=\'' . "$src?$gp&under='+this[this.selectedIndex].value" . '"'];

				echo Gen_Select($tabler,-1,${"admin_$tabler"}->FIELD_UNDER,$under,$fld);
				echo '<hr/>';
			}
		}
	}

	if (!empty($tablei) && $levelOfRub >= ${"admin_$tablei"}->LEVEL_OF_RUBS) {
		$insert_item_text = (isset(${"admin_$tablei"}->NAME2)?
		$word[$ALANG]['insert'] . ' ' . ${"admin_$tablei"}->NAME2 . ' ' . (!empty($tabler)?'в рубрику ' . (isset($rowu[$echo_rub_name])?$rowu[$echo_rub_name]:''):'')
		:$word[$ALANG]['insertitem_norub']);

		$ipage = !empty($_GET['ipage'])?(int)$_GET['ipage']:0;

		if (!(isset(${"admin_$tabler"}->RUBS_NO_UNDER) && $under == -1)) {
			?>
				<a name="items"></a>
                <ul class="list-inline">
                    <li><strong><i class="glyphicon glyphicon-file"></i>&emsp;<?=${"admin_$tablei"}->NAME?></strong></li>
                    <li>
                        <a href="<?php echo "$srci?$gp&data=$tablei&under=$under#header"; ?>" class="btn btn-default btn-xs" data-toggle="tooltip" title="Add to <?=$rowu[$echo_rub_name]?>">
                            <i class="glyphicon glyphicon-plus"></i>&nbsp;<?=$insert_item_text?>
                        </a>
                    </li>
                    <?php
                    if (isset($_GET['tablei']) && $_GET['tablei'] == 'companies') { ?>
                    
                    <li>
                       <a href="catalog.php?tabler=&tablei=companies&srci=items.php&under=-1&api_import_comp=1" class="btn btn-primary" >Импортировать компании по API</a>
                       <?= (isset($companies_import_res)) ? $companies_import_res : ''; ?>
                    </li>
                    
                    <?php
                    } ?>
                </ul>
		<?php
		}
		/*echo "<br><span style=\"font-size:11px; font-weight:bold; color:#3f64b0;\"><b>".(isset(${"admin_$tablei"}->NAME)?${"admin_$tablei"}->NAME:$word[$ALANG]['items'])."</b><br/><br/>";
		if(!$noedit) {
		  echo "<a href=$srci?$gp&data=$tablei&under=$under#header>".imadd." ".$insert_item_text."</a>";
        }
        echo "</span><br><br>";*/

		//Presets
		if (!isset(${"admin_$tablei"}->ECHO_ID)) {
			${"admin_$tablei"}->ECHO_ID = 'id';
		}
		if (!isset(${"admin_$tablei"}->SORT)) {
			${"admin_$tablei"}->SORT = 'id desc';
		}

		//Print items
		?>
		<div class="table-responsive1">
		<?php
		$noedit = isset(${"admin_$tablei"}->NO_EDIT)?${"admin_$tablei"}->NO_EDIT:0;
		if (method_exists(${"admin_$tablei"},'pre_Table')) {
			echo ${"admin_$tablei"}->pre_Table();
		}

		if (method_exists(${"admin_$tablei"},'ListTable')) {
			echo ${"admin_$tablei"}->listTable($under,$ipage);
		} else {
			listtable($srci,$tablei,isset($tabler)?$tabler:'',$tablei,$under,str_replace('%20',' ',${"admin_$tablei"}->SORT),$ipage,${"admin_$tablei"}->ECHO_ID, $noedit);
		}
		?>
		</div>
		<?php
	}
	//no tablei
	else {
		$insert_item_text = '';
	}
}
?>            </main>
        </div><!--/row (aside + main)-->
    </div><!--/container-fluid-->
<?php
require_once('inc/Bottom.php');
?>
</body>
</html>
