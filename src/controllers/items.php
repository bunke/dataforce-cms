<?php
// Items by Alex Bunke
header('Pragma: no-cache');
header('Cache-Control: no-cache');
$version = 'items 23.0';
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

<?php

$table = $tablei;

if (empty($tablei)) {
	die('NO tablei');
} else {
	syncTableFields($tablei);
}

//Parent rub field
if (!isset(${"admin_$tabler"}->FIELD_UNDER)) {
	$under_field_rub = 'under';
} else {
	$under_field_rub = ${"admin_$tabler"}->FIELD_UNDER;
}

//Parent rub ~ under
if (!isset(${"admin_$tablei"}->FIELD_UNDER) || ${"admin_$tablei"}->FIELD_UNDER == 'under') {
	$under_field = 'under';
} else {
	$under_field = ${"admin_$tablei"}->FIELD_UNDER;
}

$under = isset($_REQUEST[$under_field])?(int)$_REQUEST[$under_field]:(isset($_GET['under'])?(int)$_GET['under']:-1);

//echo 'under = ' . $under . ' GET '. $under_field . ' = ' . $_REQUEST[$under_field]. 'GET under =' .$_GET['under'];

if ($under == 0) {
	$under = -1;
}

// Initialize $id variable
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Initialize $del variable
$del = isset($_REQUEST['del']) ? (int)$_REQUEST['del'] : 0;

if (isset($id) && $id == 0) {
	unset($id);
}

if (!isset($_GET['ipage'])) {
	$ipage = 0;
}

$src = 'items.php';
$srci = $src;

if (isset(${"admin_$tablei"}->IMG_TYPE)) {
	$img_type = ${"admin_$tablei"}->IMG_TYPE;
} else {
	$img_type = 'jpg';
}

//Global parameters
$gp = 'tabler=' . (!empty($tabler)?$tabler:'') . '&tablei=' . (!empty($tablei)?$tablei:'');

if (isset(${"admin_$tablei"}->TYPE_PARAM) && !empty($_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)])) {
	$gp .= '&' . (${"admin_$tablei"}->TYPE_PARAM) . '=' . $_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)];
}
//function genExtra
function genExtra($under,$itemID)
{
	if (!($under > 0)) {
		$under = -1;
	}
	if (!($itemID > 0)) {
		$itemID = 0;
	}

	global $rowi,$tablei,${"admin_$tablei"},$TABLE_PARAMS, $TABLE_PARAMS_ASSOC_RUBS,$TABLE_PARAMS_VALS, $TABLE_PARAMS_VARS,$word,$ALANG;

	/*Создаем объект варсов для работы с настройками*/
	$cfn = 'admin_' . $TABLE_PARAMS_VARS;
	${'admin_' . $TABLE_PARAMS_VARS} = new $cfn();

	$tbl = "<h3 style=\"background-color: rgb(249, 249, 249); cursor: pointer; border-bottom:1px dashed #CCC;\" onclick=\"popUp('extra_params')\"><img src=\"_layout/images/icons/icon-list.png\" border=\"0\"> " . $word[$ALANG]['extraiparams'] . '</h3>
	<div style="background-color: rgb(249, 249, 249); display: ' . (!isset(${"admin_$tablei"}->SHOW_EXTRA_PARAMS)?'none':'block') . ';" id="extra_params"><table width="700" border="0" cellspacing="1" cellpadding="1">';

	if (${"admin_$tablei"}->EXTRA_PARAMS == 4) {
		$query = 'SELECT ' . ${"admin_$tablei"}->TABLE_PARAMS . '.*  FROM ' . ${"admin_$tablei"}->TABLE_PARAMS . "  where category_id=$under order by sort ASC";
		//,".${"admin_$tablei"}->TABLE_PARAMS_VALS.".value JOIN ".${"admin_$tablei"}->TABLE_PARAMS_VALS." ON ".${"admin_$tablei"}->TABLE_PARAMS_VALS.".param_id=".${"admin_$tablei"}->TABLE_PARAMS.".id
		$result = mQuery($query);
		$num = mNumRows($result);
		//echo $query.$num.mError();
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchAssoc($result);
			$row[paramID] = $row[id];
			$row[name_1] = stripslashes($row[name_1]);

			$query3 = 'SELECT * FROM ' . ${"admin_$tablei"}->TABLE_PARAMS_VALS . " where param_id=$row[id] AND card_id=$rowi[card_id]";
			//echo $query3;
			$result3 = mQuery($query3);
			$num3 = mNumRows($result3);
			//echo $num3;
			$row3 = mFetchAssoc($result3);

			$tbl .= "<tr>
			<td>$row[name]</td>
			<td><input name=\"param_$row[paramID]_1\" type=\"text\" id=\"param_$row[paramID]_1\" size=\"50\" maxlength=\"255\" value=\"$row3[value]\"/>";
			$tbl .= '</td>
		  </tr>';
		}
	} else {
		$rubs = '';

		$query = "SELECT * FROM $TABLE_PARAMS where id IN (SELECT paramID FROM $TABLE_PARAMS_ASSOC_RUBS where rubID=$under) order by under, sort desc";
		$result = mQuery($query);
		$num = mNumRows($result);
		for ($i = 0; $i < $num; $i++) {
			$row2 = mFetchArray($result);
			$row['paramID'] = $row2['id'];
			$row2['name_1'] = stripslashes($row2['name_1']);
			//var_dump ($row2);

			if (!(ereg('r' . $row2['under'] . 'i', $rubs))) {
				$tbl .= '<tr><td colspan=2><br/><strong>' . RubName($TABLE_PARAMS, $row2['under']) . '</strong></td></tr>';
				$rubs .= 'r' . $row2['under'] . 'i';
			}
			$query3 = "SELECT * FROM $TABLE_PARAMS_VALS where paramID=$row2[id] AND itemID=$itemID";
			//echo $query3;
			$result3 = mQuery($query3);
			$num3 = mNumRows($result3);
			$vals3 = null;
			for ($i3 = 0; $i3 < $num3; $i3++) {
				$row3 = mFetchArray($result3);
				$row3['value_1'] = stripslashes($row3['value_1']);
				$row3['value_2'] = stripslashes($row3['value_2']);
				$vals3[$i3] = $row3['valueID'];
			}

			$query4 = "SELECT * FROM $TABLE_PARAMS_VARS WHERE under=$row2[id] ORDER by " . ${'admin_' . $TABLE_PARAMS_VARS}->SORT;
			$result4 = mQuery($query4);
			$num4 = mNumRows($result4);

			$tbl .= "<tr><td width=\"150\">$row2[name_1]</td><td width=\"200\">";
			//if ($num4>0) //Variants
			//{
			//Multi variants selection
			if (isset($row2['multi_sel']) && $row2['multi_sel'] == 1) {
				$sbody = '';
				for ($i4 = 0; $i4 < $num4; $i4++) {
					$row4 = mFetchArray($result4);
					$sel = '';
					if (is_array($vals3) && in_array($row4[id],$vals3)) {
						$sel = 'checked="checked"';
					} else {
						$sel = '';
					}
					$sbody .= "<input name=\"param_$row[paramID]_1[$i4]\" type=\"checkbox\" value=\"$row4[id]\" $sel>" . stripslashes($row4['name_1']) . '<br>
						';
				}
				$tbl .= $sbody . " <input name=\"paramvar_$row[paramID]\" type=\"hidden\" id=\"paramvar_$row[paramID]\" value=\"1\" />";
			}
			//Classic Select
			else {
				$sbody = '';
				for ($i4 = 0; $i4 < $num4; $i4++) {
					$row4 = mFetchArray($result4);
					$sel = '';
					if ($row3['valueID'] == $row4['id']) {
						$sel = 'selected';
					} else {
						$sel = '';
					}
					$sbody .= "<option value=$row4[id] $sel>" . stripslashes($row4['name_1']) . '</option>';
				}
				$tbl .= "
					<SELECT title=\"Вариант значения\" name=\"param_$row[paramID]_1\"><option value=0>Выбор...</option>" . $sbody . "</SELECT>
					<input name=\"paramvar_$row[paramID]\" type=\"hidden\" id=\"paramvar_$row[paramID]\" value=\"1\" />";
			}
			//}
			//else
			//{//input
			$tbl .= "</td><td>
			<input title=\"Новый вариант значения\" style=\"width:150px;\" name=\"parami_$row[paramID]_1\" type=\"text\" id=\"parami_$row[paramID]_1\" size=\"50\" maxlength=\"255\" value=\"$row3[value_1]\"/>";
			if (${"admin_$tablei"}->EXTRA_PARAMS == 2) {
				$tbl .= "<input name=\"parami_$row[paramID]_2\" type=\"text\" id=\"parami_$row[paramID]_2\" size=\"50\" maxlength=\"255\" value=\"$row3[value_2]\"/>";
			}

			$tbl .= '</td>
		  </tr>';
			//}
			unset($row3,$vals3);
		} //end p`arams list
	}

	$tbl .= '</table></div>';

	return $tbl;
}

function saveExtra($under,$itemID)
{
	if (!($under > 0)) {
		$under = -1;
	}
	if (!($itemID > 0)) {
		$itemID = 0;
	}

	global $tablei,$_POST, ${"admin_$tablei"},$TABLE_PARAMS, $TABLE_PARAMS_ASSOC_RUBS,$TABLE_PARAMS_VALS, $TABLE_PARAMS_VARS;

	if (${"admin_$tablei"}->EXTRA_PARAMS == 4) {
		//echo "extra edit";
		$query = 'SELECT ' . ${"admin_$tablei"}->TABLE_PARAMS . '.*  FROM ' . ${"admin_$tablei"}->TABLE_PARAMS . "  where category_id=$under order by sort ASC";
		//,".${"admin_$tablei"}->TABLE_PARAMS_VALS.".value JOIN ".${"admin_$tablei"}->TABLE_PARAMS_VALS." ON ".${"admin_$tablei"}->TABLE_PARAMS_VALS.".param_id=".${"admin_$tablei"}->TABLE_PARAMS.".id
		$resultp = mQuery($query);
		$num = mNumRows($resultp);
		//echo $query.$num.mError();
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchAssoc($resultp);
			$param = $_POST['param_' . $row['id'] . '_1'];
			if ($param != '') {
				$qa = 'UPDATE ' . ${"admin_$tablei"}->TABLE_PARAMS_VALS . " SET value='$param' WHERE param_id=$row[id] AND card_id=$_POST[card_id]";
				$result = mQuery($qa);
				//echo $qa.mError().'<br>';
			}
		}
	} else {
		//o4istka
		$query = "DELETE FROM $TABLE_PARAMS_VALS where itemID=$itemID";
		$result = mQuery($query);

		$query = "SELECT * FROM $TABLE_PARAMS_ASSOC_RUBS where rubID=$under";
		$result = mQuery($query);
		$num = mNumRows($result);

		//prosmotr v POSTe vseh zna4eniy
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchArray($result);
			if (!empty($_POST['param_' . $row['paramID'] . '_1']) || !empty($_POST['parami_' . $row['paramID'] . '_1'])) {
				$param = $_POST['param_' . $row['paramID'] . '_1'];

				//Мультиселект
				if (is_array($param)) {
					foreach ($param as $val) {
						$query = "INSERT INTO $TABLE_PARAMS_VALS (`itemID` ,`paramID` ,`valueID`) VALUES ('$itemID' , '$row[paramID]', '$val')";
						//echo $query.";<br>";
						mQuery($query);
						echo mError();
					}
				}
				//Одно значение
				else {
					//Value ID
					$val = addslashes($_POST['param_' . $row['paramID'] . '_1']);

					//Text values
					$vali = addslashes($_POST['parami_' . $row['paramID'] . '_1']);
					$vali2 = isset($_POST['parami_' . $row['paramID'] . '_2'])?addslashes($_POST['parami_' . $row['paramID'] . '_2']):'';

					if (!empty($vali)) {
						//Проверка и создание варианта
						$qs = "SELECT * FROM $TABLE_PARAMS_VARS WHERE name_1='$vali' AND under=" . $row['paramID'];
						$ress = mQuery($qs);
						//echo $qs.mError();
						$nums = mNumRows($ress);
						//Есть такой вариант в таблице
						if ($nums > 0) {
							$rows = mFetchAssoc($ress);
							$val = $rows['id'];
						}
						//Такого варианта еще нет, создаем новый
						else {
							$qi = "INSERT INTO $TABLE_PARAMS_VARS SET `name_1`='$vali',under=" . $row['paramID'];
							$resi = mQuery($qi);
							//echo $qi.mError();
							$val = mysql_insert_id();
						}
					}

					if (!($val > 0)) {
						echo ' <strong>PARAM ' . $row['paramID'] . ' EMPTY!</strong> ';
					}

					//paramvar -> Variant!
					if ($_POST['paramvar_' . $row['paramID']] == '1') {
						$query = "INSERT INTO $TABLE_PARAMS_VALS (`itemID` ,`paramID` ,`valueID`) VALUES ('$itemID' , '$row[paramID]', '$val')";
					} else {
						$query = "INSERT INTO $TABLE_PARAMS_VALS (`itemID` ,`paramID` ,`value_1`,`value_2`) VALUES ('$itemID' , '$row[paramID]', '$vali', '$vali2')";
					}
					//echo 'param_'.$row['paramID'].'_1 ';
					//echo $query;
					mQuery($query);
					unset($val,$vali,$vali2);
				}
				echo mError();
			}
		}

		return $result;
	}
}

function delExtra($itemID)
{
	global $tablei, $TABLE_PARAMS, $TABLE_PARAMS_ASSOC_RUBS,$TABLE_PARAMS_VALS, $TABLE_PARAMS_VARS;

	//o4istka
	if (${"admin_$tablei"}->EXTRA_PARAMS == 4) {
	} else {
		$query = "DELETE FROM $TABLE_PARAMS_VALS where itemID=$itemID";
	}
	$result = mQuery($query);

	return $result;
}

if (isset(${'admin_' . $tablei}->ALT_ID)) {
	$idname = ${'admin_' . $tablei}->ALT_ID;
} else {
	$idname = 'id';
}

if (isset(${'admin_' . $tablei}->ONLY_ID) && ${'admin_' . $tablei}->ONLY_ID > 0) {
	$id = ${'admin_' . $tablei}->ONLY_ID;
}

if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && method_exists(${'admin_' . $tablei}, 'checkPermissions')) {
	//var_dump($_REQUEST['id']);
	if (!${'admin_' . $tablei}->checkPermissions($_REQUEST['id'])) {
		die('No access!');
	}
}
?>

<!--<script type="text/javascript" src="fckeditor.js"></script>-->
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
	http_request_r.open('GET', 'ajax/listrubs.php?tabler=<?php echo(isset(${"admin_$tabler"}->TABLE_UNDER_DOP)?${"admin_$tabler"}->TABLE_UNDER_DOP:$tabler); ?>&tablei=<?php echo $tabler; ?>&under='+$under_name+'&id=<?php echo(isset($id)?$id:0); ?>&addrub='+addrub+'&delrub='+delrub, true);
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
while (isset(${"admin_$tablei"}->fld[$i]->name)) {
	if (!empty(${"admin_$tablei"}->fld[$i]->extra_param['validation'])) {
		?>	
		    var regExpField = '<?=${"admin_$tablei"}->fld[$i]->name ?>';
		    var regExp = <?=${"admin_$tablei"}->fld[$i]->extra_param['validation']['rule'] ?>;
			if (!regExp.test($('#' + regExpField).val())) {
		        alert('Поле "<?=str_replace("'",'`',${"admin_$tablei"}->fld[$i]->txt)?>" <?=${"admin_$tablei"}->fld[$i]->extra_param['validation']['alert'] ?>');
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

<body>
<?php
//MENU
include('controllers/menu.php');
?>
  <div id="helplayer" style="border: 1 solid #000000; position:absolute; left:253px; top:67px; width:443px; height:189px; z-index:1; background-color: #FBFBFB; layer-background-color: #FBFBFB; overflow: visible; visibility: hidden;" class="window">
    <div align="center"><strong>Помощь</strong> </div>
    <ul>
      <li>
        <div align="left">Для добавления материала нажмите один раз на кнопку<br>
          <span class="txt">&quot;<strong><img src="img/plus.gif"> <a href="#">добавить новый</a></strong></span>&quot;.</div>
      </li>
      <li>
        <div align="left">В правой колонке отображается список материалов рубрики.</div>
      </li>
      <li>
        <div align="left">Для редактирования материала нажмите на его название или на кнопку &quot;<img src="img/editm.gif" width="9" height="8">&quot; </div>
      </li>
      <li>
        <div align="left">Для удаления материала нажмите один раз на красный крестик &quot;<img src="img/delm.gif" width="9" height="8">&quot; справа от названия. </div>
      </li>
      <li>
        <div align="left">Чтобы вернуться к рубрикам нажмите &quot;<img src="img/folder.gif" width="14" height="9"> <a href="#">Вернуться к рубрикам</a>&quot;</div>
      </li>
    </ul>
    <p align="center"><a  onClick="helplayer.style.visibility='hidden'" href=#>закрыть окно </a><br>
    </p>
  </div>
   <div style="text-align:center">
  <?php
$creation_time = (date('U'));
$_POST['creation_time'] = $creation_time;
$date = (date('Y m d, H:i'));

// Drop items table
if (isset($_GET['drt']) && $_GET['drt'] == 1) {
	mQuery("DROP TABLE $tablei");
}

// Creating new table
if (isset($_GET['crt']) && $_GET['crt'] == 1) {
	echo crtQuery($tablei,${"admin_$tablei"}->fld);
}

//o4istka cache
if ($_POST || ($_GET['del'] > 0)) {
	CleanSiteCache();
}

//Number of images
if (!empty(${"admin_$tablei"}->IMG_NUM)) {
	$imgnum = ${"admin_$tablei"}->IMG_NUM;
} else {
	$imgnum = 0;
}

//Image field
if (empty(${"admin_$tablei"}->IMG_FIELD)) {
	$img_field = 'creation_time';
} else {
	$img_field = ${"admin_$tablei"}->IMG_FIELD;
}

if (isset($_POST['duplicate']) && $_POST['duplicate'] == 1) {
	$query = "SELECT * FROM $tablei WHERE $idname='$id'";
	//echo $query;
	$result = mQuery($query);
	$row = mFetchArray($result);

	unset($result,$query);

	if (empty($snum)) {
		for ($i = 1; $i <= $imgnum; $i++) {
			@copy("$pref$FOLDER_IMAGES/$tablei.$row[creation_time].$i.s0.jpg","$pref$FOLDER_IMAGES/$tablei.$creation_time.$i.s0.jpg");
			@copy("$pref$FOLDER_IMAGES/$tablei.$row[creation_time].$i.s.jpg","$pref$FOLDER_IMAGES/$tablei.$creation_time.$i.s.jpg");
			@copy("$pref$FOLDER_IMAGES/$tablei.$row[creation_time].$i.b.jpg","$pref$FOLDER_IMAGES/$tablei.$creation_time.$i.b.jpg");
		}
	}
	echo 'dublicating... ';
	unset($id);
	unset($row);
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

//add

if (!isset($id) && isset($_POST['posted'])) {
	//echo $word[$ALANG]['addnew']."<br>";
	/*Перед сохранением новых данных можно что-то проверить и сделать*/
	if (method_exists(${"admin_$tablei"},'beforeAdd')) {
		${"admin_$tablei"}->beforeAdd(${"admin_$tablei"}->fld);
	}

	/*$query=addingQuery($table,${"admin_$tablei"}->fld);
	$result = mQuery($query);
	//echo $query;
	echo mError();
	//if ($result==1) echo "<b>".$word[$ALANG]['ok']."</b><br>";

	$query="SELECT * FROM $tablei ORDER by $idname desc";
	$result= mQuery($query);
	$rownew=mFetchAssoc($result);*/

	$rownew = ${"admin_$tablei"}->insertQuery();
	$rownew = FetchID($tablei, $rownew['id']);

	if (!isset(${"admin_$tablei"}->IMG_FIELD) || ${"admin_$tablei"}->IMG_FIELD == 'creation_time') {
		$img_id = $creation_time;
	} else {
		$img_id = $rownew[${"admin_$tablei"}->IMG_FIELD];
	}

	uploadImagesAndFiles($tablei,$img_id);

	$query = ${"admin_$tablei"}->updateQuery($rownew['id']);
	//echo 'QUERY = '.$query;
	//$result = mQuery($query);

	addLog($tablei,1,$rownew[$idname]);

	if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && !isset(${"admin_$tablei"}->TABLE_UNDER_DOP) && isset($under) && $under != 0) { //Under to Assoc table
		if (isset(${"admin_$tablei"}->ASSOC_FIELD_ID)) {
			$afid = ${"admin_$tablei"}->ASSOC_FIELD_ID;
		} else {
			$afid = 'recID';
		}
		if (isset(${"admin_$tablei"}->ASSOC_FIELD_UNDER)) {
			$afunder = ${"admin_$tablei"}->ASSOC_FIELD_UNDER;
		} else {
			$afunder = 'under';
		}

		mQuery('INSERT INTO ' . ${"admin_$tablei"}->TABLE_RUB_ASSOC . " (`$afid`,`$afunder`) VALUES ('" . $rownew['id'] . "','" . $under . "')");
	}

	if ($sortpos > 0) {//sort
		$query = "SELECT * FROM $tablei ORDER by sort desc";
		$result = mQuery($query);
		$rowsort = mFetchAssoc($result);
		mQuery("update $tablei set sort='" . (++$rowsort['sort']) . "' WHERE id=$rownew[id]");
	}

	//extra old
	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
		saveExtra($rownew[$under_field],$rownew[$idname]);
	}

	//Extra params new
	if (!empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
		saveExtraParamsProduct($rownew[$under_field],$rownew[$idname]);
	}

	// Domains dynamic Texts edit
	if (isset(${"admin_$tablei"}->dynamicFields)) {
		saveDomainsTexts($tablei, $rownew['id']);
	}

	// Tags save
	if (${"admin_$tablei"}->USE_TAGS) {
		saveTags($tablei, $rownew['id']);
	}

	if (method_exists(${"admin_$tablei"},'afterAdd')) {
		${"admin_$tablei"}->afterAdd($rownew);
	}

	$query = '';
	$result = 0;

	//Edit after add
	if (!empty(${"admin_$tablei"}->EDIT_AFTER_ADD)) {
		//echo 'AFTER ADD!';
		$id = $rownew[$idname];
		$no_edit = 1;
	}
}

// Edit

if (isset($id) && isset($_POST['posted']) && empty($del) && !isset($no_edit)) {
	//echo $word[$ALANG]['editing']."<br>";

	$row = FetchID($tablei, $id);

	//Перед сохранением новых данных можно что-то проверить и сделать
	if (method_exists(${"admin_$tablei"},'beforeEdit')) {
		${"admin_$tablei"}->beforeEdit($row);
	}

	//Extra params old
	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
		saveExtra($row[$under_field],$id);
	}

	//Extra params new
	if (!empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
		saveExtraParamsProduct($row[$under_field],$id);
	}

	// Domains dynamic Texts edit
	if (isset(${"admin_$tablei"}->dynamicFields)) {
		saveDomainsTexts($tablei, $id);
	}

	// Tags save
	if (${"admin_$tablei"}->USE_TAGS) {
		saveTags($tablei, $id);
	}

	$query = '';
	$result = 0;

	//Некоторые поля остаются неизменными
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

	if (!empty($deldocsfile)) {
		//echo "../files/$row[creation_time].$row[format]";
		@unlink("../$FOLDER_FILES/$row[format]/$row[filename]");
		@unlink("../$FOLDER_FILES/$row[format]/$row[creation_time].$row[format]");
		@unlink("../$FOLDER_FILES/preview/$row[creation_time].jpg");
		$format = '';
		$_POST['format'] = '';
		$filename = '';
		$_POST['filename'] = '';
	}

	$i = 0;
	while (isset(${"admin_$tablei"}->fld[$i]->name)) {
		/*Удаление динамических файлов*/
		if (isset($_POST['del_' . ${"admin_$tablei"}->fld[$i]->name])) {
			unlink('../userfiles/' . GetFileFormat($row[${"admin_$tablei"}->fld[$i]->name]) . '/' . $row[${"admin_$tablei"}->fld[$i]->name]);
			$row[${"admin_$tablei"}->fld[$i]->name] = '';
		}

		/*Сохраняем динамический файл*/
		if (${"admin_$tablei"}->fld[$i]->type == 11) {
			$_POST[${"admin_$tablei"}->fld[$i]->name] = $row[${"admin_$tablei"}->fld[$i]->name];
		}

		$i++;
	}
	//var_dump($_POST);
	${"admin_$tablei"}->fillValuesFromPost();

	/*Название картинок*/
	if (!isset(${"admin_$tablei"}->IMG_FIELD) || ${"admin_$tablei"}->IMG_FIELD == 'creation_time') {
		$img_id = $creation_time;
	} else {
		$img_id = $_POST[${"admin_$tablei"}->IMG_FIELD];
	}

	/*Всеобщий аплоад*/
	uploadImagesAndFiles($tablei,$img_id);

	/*Сохранение линейных данных*/
	//$query=updQuery($table,${"admin_$tablei"}->fld,$id);
	$query = ${"admin_$tablei"}->updateQuery($id);
	//echo $query;
	//$result = mQuery($query);

	//if ( mError()!='') echo '<strong>ERROR: '.mError().'</strong><br />';

	addLog($tablei,2,$id);

	//echo "$query";
	$query = '';

	if (isset($id) && $id > 0) {
		//$query="SELECT * FROM $table WHERE $idname='$id'";
		//$res = mQuery($query);
		//$row=mFetchArray($res);
		//$query="";

		$row = FetchID($tablei, $id);

		/*Дополнительные действия после сохранения*/
		if (method_exists(${"admin_$tablei"},'afterEdit')) {
			${"admin_$tablei"}->afterEdit($row);
		}
	}
} //Конец редактирования

//Del
if (!empty($del) && !empty($id)) {
	//echo $word[$ALANG]['del']."<br>";

	// Fetching before Delete
	$row = FetchID($tablei, $id);

	if (method_exists(${"admin_$tablei"},'beforeDelete')) {
		${"admin_$tablei"}->beforeDelete($row);
	}

	if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC)) {
		mQuery('DELETE FROM ' . ${"admin_$tablei"}->TABLE_RUB_ASSOC . ' WHERE recID=' . $id);
	}

	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
		delExtra($id);
	}
	$result = delrow($table,$id);
	addLog($tablei,3,$id);
	unset($id,$row);
}

if (!empty($delfile)) {
	$result = delfile($delfile);
}

if (!empty($delimg)) {
	$result = delfile($delimg . '.s.jpg');
	$result = delfile($delimg . '.b.jpg');
	$result = delfile($delimg . '.b.png');
	$result = delfile($delimg . '.m.b.jpg');
}

// Query exec
if (!empty($query)) {
	$result = mQuery($query);
}
// Error
if (isset($result) && $result != 1) {
	echo mError();
}

// for editing
if (isset($id) && $id > 0) {
	$row = FetchID($tablei, $id);

	$rowi = $row;
	if (isset($rowi[$under_field])) {
		$under = $rowi[$under_field];
	}
	$query = '';
	$result = 0;

	$i = 0;
}

if ($under > 0) {
	$rowu = FetchID($tabler, $under);
}

?></div><a name="header"></a>
<div class="container-fluid">
        <div class="row">
      <?php if (empty(${"admin_$tablei"}->ONLY_ID) && empty(${"admin_$tablei"}->NO_LEFT_RUBS)) { ?>
      <aside class="col-md-4 col-lg-3" id="sidebar">
				 <h4><?php echo $word[$ALANG]['rubricator']; ?></h4>
				 <div id="listcat_div">
				 </div>
				 <script language="javascript">ListCat('<?=isset($tabler)?$tabler:''?>','<?=$tablei?>',<?=($under != 0?$under:0)?>,0,<?=isset($rpage)?$rpage:0?>,<?=isset($ipage)?$ipage:0?>);</script>
        </aside>
      <?php } ?>
        <main class="col-md-8 col-md-offset-4 col-lg-9 col-lg-offset-3" id="main">
            <!-- Navigation !-->
            <div class="row">
                    <ul class="breadcrumb">
<?php

if (isset(${"admin_$tablei"}->ECHO_NAME)) {
	$echo_item_name = ${"admin_$tablei"}->ECHO_NAME;
} else {
	$echo_item_name = 'name_1';
}

$namePos = getPosInFld($echo_item_name, ${"admin_$tablei"}->fld);

if (${"admin_$tablei"}->MULTI_LANG && !empty(${"admin_$tablei"}->fld[$namePos]->extra_param['multiLang'])) {
	$echo_item_name .= '_1';
}

// Breadcrumbs

echo " <li><a href=catalog.php?$gp&under=-1><i class=\"glyphicon glyphicon-book\"></i>&emsp;" . (isset(${"admin_$tablei"}->NAME)?${"admin_$tablei"}->NAME:(isset(${"admin_$tablei"}->name)?${"admin_$tablei"}->name:'')) . '</a></li>';

if (isset($tabler) && $tabler != '') {
	if (isset(${"admin_$tabler"}->ECHO_NAME)) {
		$echo_rub_name = ${"admin_$tabler"}->ECHO_NAME;
	} else {
		$echo_rub_name = 'name_1';
	}

	if (${"admin_$tabler"}->MULTI_LANG) {
		$echo_rub_name .= '_1';
	}

	if (isset($rowu[$under_field_rub]) && $rowu[$under_field_rub] > 0) {
		$rowuu = FetchID($tabler,$rowu[$under_field_rub]);
		if ($rowuu[$echo_rub_name] != '') {
			echo "<li><a href=\"catalog.php?$gp&under=$rowuu[id]\">" . $rowuu[$echo_rub_name] . '</a></li>';
		}
	}

	if (!empty($rowu['id'])) {
		echo "<li><a href=\"catalog.php?$gp&under=$rowu[id]\">" . $rowu[$echo_rub_name] . '</a></li>';
	}

	if (isset($row['id']) && $row['id'] > 0) {
		echo '<li>' . $row[$echo_item_name] . '</li>';
	}
}
?>
                    </ul>
        </div><!-- End navigation !-->

<!-- ACTIONS -->
 <div class="row">
                    <div class="col-md-7 col-lg-8 clearfix">
                        <h1 class="pull-left"><i class="glyphicon glyphicon-folder-open"></i>&emsp;<?php
                        if (empty($id)) {
                        	echo (isset(${"admin_$tabler"}->NAME)?${"admin_$tabler"}->NAME:(isset(${"admin_$tablei"}->NAME)?${"admin_$tablei"}->NAME:${"admin_$tablei"}->name)) . '/' . (!empty($rowu)?$rowu[$echo_rub_name]:'');
                        } else {
                        	echo ${"admin_$tablei"}->getRowName($row);
                        }
?>&emsp;</h1>
                        <div class="btn-group pull-left" role="group" aria-label="...">

                            <a href="<?="catalog.php?$gp&under=" . (isset($rowu[$under_field_rub])?$rowu[$under_field_rub]:'-1')?>" class="btn btn-default" data-toggle="tooltip" title="На уровень вверх" role="button">
                                <i class="glyphicon glyphicon-level-up"></i>
                            </a>
                            <?php
    if (isset($id) || isset($addid)) {
    	if (!isset($id) || (isset($id) && !($id <> ''))) {
    		//echo "<b>".$word[$ALANG]['newrub']."</b>";
    	} else {
    		if (empty($_SESSION['admin']['group']['del_restrict']) && (!isset($row['no_del']) || (isset($row['no_del']) && $row['no_del'] != 1))) {
    			?>
			<a href="#" onClick="<?="confirming('" . $word[$ALANG]['del'] . ' ' . ((isset($id) && isset($row[$echo_item_name]))?$row[$echo_item_name]:'') . "?','catalog.php?tabler=$tabler&tablei=$tablei&under=$under&delitem=$id')"?>" class="btn btn-default" data-toggle="tooltip" title="<?=$word[$ALANG]['del']?>" role="button">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>
			<?php
    		}
    		//echo " / <strong><a  href=# title=\"".$word[$ALANG]['del']."\">".$word[$ALANG]['del']." ".$row[$echo_rub_name]." ".imdelm."</a></strong>";
    	}
    }
?>

                            <?php if (isset($tablei) && $tablei != '') {
                            	?>
								<a href="<?="$srci?$gp&id=&under=" . (isset($under)?$under:-1)?>" class="btn btn-default" data-toggle="tooltip" title="<?=$word[$ALANG]['addnew']?>" role="button">
                                <i class="glyphicon glyphicon-file"></i>
                            </a>
                            <?php } ?>
                        </div>
                        <div class="btn-group hidden-xs" data-toggle="buttons">
                            <button type="button" class="btn btn-default" id="full_screen" data-toggle="tooltip" title="Скрыть / показать боковую панель">
                                <i class="glyphicon glyphicon-resize-horizontal"></i>
                            </button>
                        </div>
                    </div>
                </div>
 <!-- ACTIONS END -->

            <form action="<?php echo $src; ?>" method="post" enctype="multipart/form-data" name="form1" id="form1">
		<?php
		//Printing action
		if (!empty($id) && empty($del)) {
			echo '<p class="txt"><strong>' . $word[$ALANG]['editing'] . '</strong></p>';
		} elseif (!isset($rowi)) {
			echo '<p class="txt"><strong>' . $word[$ALANG]['create'] . '</strong></p>';
		}

?>
        <div role="tabpanel">
         <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#tabs-1" aria-controls="tabs-1" role="tab" data-toggle="tab"><?=$word[$ALANG]['tab1']?></a></li>
            <?php
                $tabIndex = 1;

// Langs
if (${"admin_$tablei"}->MULTI_LANG || ${"admin_$tablei"}->PSEUDO_MULTI_LANG) {
	foreach ($LANGS as $lang => $langName) {
		if ($lang == array_keys($LANGS)[0]) {
			continue;
		}

		$tabIndex++;
		echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['lang'] . ': ' . $langName . '</a></li>
						';
	}
}

				//Params
if (!empty(${"admin_$tablei"}->EXTRA_PARAMS) || !empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) { //
	$tabIndex++;
	echo '<li role="presentation"><a href="#tabs-' . $tabIndex . '" aria-controls="tabs-' . $tabIndex . '" role="tab" data-toggle="tab">Дополнительные характеристики</a></li>
                            ';
}

//Tags
if (${"admin_$tablei"}->USE_TAGS) {
	$tabIndex++;
	echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['tags'] . '</a></li>
						';
}

//Dynamic domains
if (isset(${"admin_$tablei"}->dynamicFields)) {
	$siteDomains = pdoFetchAll('SELECT * FROM domains ORDER by id ASC');

	foreach ($siteDomains as $domain) {
		$tabIndex++;
		echo '<li role="presentation"><a href="#tabs-' . $tabIndex . '" aria-controls="tabs-' . $tabIndex . '" role="tab" data-toggle="tab">Домен: ' . $domain['name'] . '</a></li>
						';
	}
}

//Images
if ($imgnum > 0) {
	$tabIndex++;
	echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['images'] . '</a></li>
                    ';
}

//Files
if (method_exists(${"admin_$tablei"}, 'printFiles') || $formatpos != null) {
	$tabIndex++;
	echo '<li><a href="#tabs-' . $tabIndex . '" role="tab" data-toggle="tab">' . $word[$ALANG]['files_bank'] . '</a></li>
					';
}
?>
        </ul>
        <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="tabs-1">

        <?php

        if (!empty($row[$img_field])) {
        	$folder = GetImageFolder($tablei,$row[$img_field]);
        	if (@filesize("$pref$FOLDER_IMAGES/$folder" . $row[$img_field] . ".1.b.$img_type") > 0) {
        		echo "<img src=\"$pref$FOLDER_IMAGES/$folder" . $row[$img_field] . ".1.b.$img_type\" style=\"display:block;
border-radius:15px;
position:relative;
max-height:200px;\" alt=\"" . $row[$echo_item_name] . '" title="' . $row[$echo_item_name] . '">';
        	}
        }

if (!isset(${"admin_$tablei"}->NO_COPY) && isset($rowi)) { ?>
            <div class="form-group">
                <label for="duplicate"><?php echo $word[$ALANG]['makecopy']; ?></label>
                <input name="duplicate" type="checkbox" id="duplicate" value="1">
            </div><?php } ?>
          <input name="id" type="hidden" id="id" value="<?php if (!isset($del) || $del != 1) {
          	echo $id;
          } ?>">
			<input name="ipage" type="hidden" id="ipage" value="<?php echo $ipage; ?>">
            <input name="nomenu" type="hidden" id="nomenu" value="<?php echo isset($nomenu)?$nomenu:''; ?>">
            <input name="tabler" type="hidden" id="tabler" value="<?=isset($tabler)?$tabler:''; ?>">
            <input name="tablei" type="hidden" id="tablei" value="<?php echo $tablei; ?>">
            <input name="posted" type="hidden" id="posted" value="1">
		  <?php

          //Rubric substitude
          if (!empty($under)) {
          	if (!is_array($row)) {
          		$row = [];
          	}
          	$row[$under_field] = $under;
          }

${"admin_$tablei"}->addSpFields($row,$under);

if (isset(${"admin_$tablei"}->SHOWED_FORM) && is_file(${"admin_$tablei"}->SHOWED_FORM)) {
	include(${"admin_$tablei"}->SHOWED_FORM);
} else {
	$basicFields = AddFields($tablei,${"admin_$tablei"}->fld,$row,!empty($id)?$id:0);
}

//Log
if (isset($TABLE_ADMINS_LOG) && !empty($id)) {
	genLog($tablei, $id);
}

?></p>
  	</div>
	<?php
    $tabIndex = 1;

// Langs
if (${"admin_$tablei"}->MULTI_LANG || ${"admin_$tablei"}->PSEUDO_MULTI_LANG || !empty($basicFields)) {
	foreach ($LANGS as $lang => $langName) {
		if ($lang == array_keys($LANGS)[0]) {
			continue;
		}
		$tabIndex++;
		echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';
		echo implode("\n", $basicFields[$lang]);
		echo '</div>';
	}
}

// Extra params
if (!empty(${"admin_$tablei"}->EXTRA_PARAMS) || !empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
	$tabIndex++;
	echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';

	//Extra params old
	if (!empty(${"admin_$tablei"}->EXTRA_PARAMS)) {
		echo genExtra($under,$row['id']);
	}

	//Extra params new
	if (!empty(${"admin_$tablei"}->TABLE_EXTRA_PARAMS)) {
		echo genExtraParamsProduct($under,isset($row['id'])?$row['id']:0);
	}

	echo '</div>';
}

// Tags
if (${"admin_$tablei"}->USE_TAGS) {
	$tabIndex++;
	echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '"><br/>';
	echo genTagsList($tablei, isset($id) ? $id : null);
	echo '</div>';
}
// Images
if (${"admin_$tablei"}->IMG_NUM > 0) {
	$tabIndex++;
	echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '"><br/>';

	if ($imgnum > 1) {
		printImageMultiInput();
	}

	for ($i = 1; $i <= $imgnum; $i++) {
		echo '<p class=txt><strong>' . $word[$ALANG]['image'] . ' ' . ${"admin_$tablei"}->IMG_BIG_SIZE . 'x' . ${"admin_$tablei"}->IMG_BIG_VSIZE . ' pх:</strong>';
		printImageInputs($src, $table, $row, $i, 's');
		echo '</p>';
	}

	// Extra inputs
	if (!empty($basicFields['images'])) {
		echo implode("\n", $basicFields['images']);
	}

	echo '</div>';
}
// Files
if (method_exists(${"admin_$tablei"}, 'printFiles') || $formatpos != null) {
	$tabIndex++;
	echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '"><br/>';

	//Bank
	if (method_exists(${"admin_$tablei"}, 'printFiles')) {
		${"admin_$tablei"}->printFiles($rowi);
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
if (isset(${"admin_$tablei"}->dynamicFields)) {
	foreach ($siteDomains as $domain) {
		$tabIndex++;
		echo '<div role="tabpanel" class="tab-pane" id="tabs-' . $tabIndex . '">';
		printDynamicFields($tablei, ${"admin_$tablei"}->dynamicFields, isset($id)?$id:0, $domain['id']);
		echo '</div>';
	}
}
?>
    </div> <!-- Ends tabs content !-->
    <?php
    if (method_exists(${"admin_$tablei"}, 'showAfterTabs')) {
    	${"admin_$tablei"}->showAfterTabs($row);
    }

?>

</div>

          </form>
          <br/>
          <br/>
<div id="ready_block">
	<button class="btn btn-success" onclick="submitForm();"><?=$word[$ALANG]['ready']?></button>
</div>
          </main> <!-- main block !-->

      </div><!-- end row !-->
    </div><!-- container-fluid !-->

<?php
require_once('inc/Bottom.php');
?>
  </body>
</html>
