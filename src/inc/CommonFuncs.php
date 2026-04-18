<?php

/*
 * Common funcs for CMS
 * update list:
 * 24.02.2017 - Multi select of extra params added
 * 21.04.2016 - Pseudo multi lang fixed
 * 12.04.2016 - Type of field 10 added
 * 07.03.2016 - Del img bug fixed
 * 15.12.2015 - Creation of time fields fixed
 * 04.07.2015 - Classes moved to AdminApp
 * 25.02.2015 - genAssocBlock for items assoc, type 5 not syncs
 * 24.02.2015 - images folders type 2
 * 18.09.2014 - extra_param['NO_EDIT'] for field type 9 added
 * 31.08.2014 - Sort $srci bug fixed
 * 23.08.2014 - Crt Query comments added
 * 16.07.2014 - Dinamic Fields for Entities
 * 08.07.2014 - Dinamic Fields - added type 1
 * 28.05.2014 - Olga PDO try|catch, in Translit add $q = trim($q);
 */

require_once('AdminApp.php');
$App = new AdminApp();

// Folders — defaults apply only if bootstrap didn't already set them
if (!isset($FOLDER_FILES)) {
	$FOLDER_FILES = 'userfiles';
}
if (!isset($FOLDER_IMAGES) || $FOLDER_IMAGES == '') {
	$FOLDER_IMAGES = 'images';
}
if (!isset($FOLDER_USERFILES)) {
	$FOLDER_USERFILES = 'userfiles';
}
if (!isset($FOLDER_IMAGES_FRONTEND)) {
	$FOLDER_IMAGES_FRONTEND = $FOLDER_IMAGES;
}

// $pref   — filesystem prefix for @unlink / file_exists / mkdir / copy
// $pref_url — URL prefix for <img src=...> / <a href=...> emitted to browser
if (!isset($pref)) {
	$pref = isset($NO_ADMIN) ? '' : '../';
}
if (!isset($pref_url)) {
	$pref_url = isset($NO_ADMIN) ? '/' : '../';
}

//OLD mysql

function mQuery($q)
{
	global $mySqlLink;
	return mysqli_query($mySqlLink, $q);
}
function mNumRows($res)
{
	if ($res) {
		return mysqli_num_rows($res);
	} else {
		return 0;
	}
}
function mFetchArray($res)
{
	return mysqli_fetch_array($res);
}
function mFetchAssoc($res)
{
	return mysqli_fetch_assoc($res);
}
function mFetchRow($res)
{
	return mysqli_fetch_row($res);
}
function mError()
{
	global $mySqlLink;
	return mysqli_error($mySqlLink);
}
function mInsertId()
{
	global $mySqlLink;
	return mysqli_insert_id($mySqlLink);
}

// PDO usage
function pdoQuery($q)
{
	try {
		global $pdo;
		return $pdo->query($q);
	} catch (PDOException $e) {
		echo "\nPDO error: " . $e->getMessage() . "\n";
	}
	return FALSE;
}

function pdoExec($q)
{
	try {
		global $pdo;
		return $pdo->exec($q);
	} catch (PDOException $e) {
		echo "\nPDO error: " . $e->getMessage() . "\n";
	}
	return FALSE;
}

function pdoEscape($value)
{
	try {
		global $pdo;
		return $pdo->quote($value);
	} catch (PDOException $e) {
		echo "\nPDO error: " . $e->getMessage() . "\n";
	}
	return FALSE;
}

function pdoFetchAll($q, $format = NULL)
{
	try {
		if (!$format) {
			$format = PDO::FETCH_BOTH;
		}
		global $pdo;
		$res = $pdo->query($q, $format);

		if ($res != FALSE) {
			return $res->fetchAll();
		} else {
			return $res;
		}
	} catch (PDOException $e) {
		echo "\nPDO error: " . $e->getMessage() . "\n";
	}
	return FALSE;
}

function pdoLastInsertId()
{
	try {
		global $pdo;
		return $pdo->lastInsertId();
	} catch (PDOException $e) {
		echo "\nPDO error: " . $e->getMessage() . "\n";
	}
	return FALSE;
}

function pdoError()
{
	global $pdo;
	return $pdo->errorInfo();
}

function pdoSet($fields = [], $source = [])
{
	$set = '';
	$i = 0;
	foreach ($fields as $field) {
		$escapedVal = pdoEscape($source[$i]);
		// Remove the quotes added by pdoEscape as we'll add them in the query
		$escapedVal = substr($escapedVal, 1, -1);
		$set .= '`' . str_replace('`','``',$field) . '`' . "='$escapedVal', ";
		$i++;
	}
	return substr($set, 0, -2);
}

// Создаем недостающие поля в таблице
function syncTableFields($table)
{
	global ${'admin_' . $table};

	$chkcol = mQuery("SELECT * FROM `$table` LIMIT 1");

	//Таблица есть)
	if ($chkcol && mNumRows($chkcol) > 0) {
		$mycol = mFetchArray($chkcol);

		$i = 0;

		//echo 'Fields sync';
		foreach (${'admin_' . $table}->fld as $field) {
			if ($field->type == 5 || $field->type == 8 || $field->type == 14 || $field->multi_lang) {
				$i++;
				continue;
			}

			if (!array_key_exists($field->name,$mycol)) {
				switch($field->type) {
					case 0: $newType = "FLOAT NOT NULL DEFAULT '0'";
						break;
					case 1: $newType = "VARCHAR(255) NOT NULL DEFAULT ''";
						break;
					case 2: $newType = "TEXT NOT NULL DEFAULT ''";
						break;
					case 3: $newType = "VARCHAR(128) NOT NULL DEFAULT ''";
						break;
					case 4: $newType = "BIGINT(20) NOT NULL DEFAULT '0'";
						break;
					case 6: $newType = "INT(1) NOT NULL DEFAULT '0'";
						break;
					case 7: $newType = "TEXT NOT NULL DEFAULT ''";
						break;
					case 8: $newType = "TEXT NOT NULL DEFAULT ''";
						break;
					case 9:
					case 10: $newType = "INT(11) NOT NULL DEFAULT '0'";
						break;
					case 11: $newType = "VARCHAR(255) NOT NULL DEFAULT ''";
						break;
					case 12: $newType = "VARCHAR(255) NOT NULL DEFAULT ''";
						break;
					case 13: $newType = "DATE NOT NULL DEFAULT '0000-00-00'";
						break;
					case 15: $newType = "VARCHAR(7) NOT NULL DEFAULT ''";
						break;
					case 16: $newType = "TEXT NOT NULL DEFAULT ''";
						break;
					default: $newType = "VARCHAR(255) NOT NULL DEFAULT ''";
				}

				if (mQuery('ALTER TABLE `' . $table . '` 
                        ADD `' . $field->name . '` ' . $newType . ' 
                        AFTER `' . ${'admin_' . $table}->fld[$i - 1]->name . '`')) {
					echo ' Column ' . $field->name . ' added! ';
				} else {
					echo mError();
				}
			}

			$i++;
		}
	}
}

function GetFileFormat($name)
{
	$tmp = explode('.',$name);
	$tmp = array_reverse($tmp);
	$format = $tmp[0];
	return 	$format;
}

//Supertime - Spasibo Sprintery
function getTime()
{
	$time1 = explode(' ', microtime());
	$usec = (double)$time1[0];
	$sec = (double)$time1[1];
	return ($sec + $usec);
}

function getFileUploadMaxSize()
{
	static $max_size = -1;

	function parse_size($size)
	{
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}

	if ($max_size < 0) {
		// Start with post_max_size.
		$post_max_size = parse_size(ini_get('post_max_size'));
		if ($post_max_size > 0) {
			$max_size = $post_max_size;
		}

		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$upload_max = parse_size(ini_get('upload_max_filesize'));
		if ($upload_max > 0 && $upload_max < $max_size) {
			$max_size = $upload_max;
		}
	}
	return $max_size;
}

function clearUrl($url)
{
	return $str = preg_replace('/[^a-z0-9\-]/i','',str_to_l($url));
}

function saveAllCatRelations($tabler = null)
{
	if ($tabler == null) {
		global $tabler;
	}

	global ${'admin_' . $tabler};

	mQuery('DELETE FROM ' . $tabler . '_relations');

	$q = "SELECT * FROM $tabler ORDER by id ASC";
	$res = mQuery($q);
	$num = mNumRows($res);

	for ($i = 0; $i < $num; $i++) {
		$row = mFetchAssoc($res);
		//echo $row['name'] . '... ';
		saveCatRelations($row['id'], $row['id'], ${'admin_' . $tabler}->FIELD_UNDER,1);
	}
}

function mainLang()
{
	global $LANGS;

	if (empty($LANGS)) {
		die('Array LANGS not set in config!');
	}

	return array_keys($LANGS)[0];
}

function genTagsList($table, $id = null)
{
	global $TABLE_TAGS;
	$res = '';

	$cfn = 'admin_' . $TABLE_TAGS;
	${'admin_' . $TABLE_TAGS} = new $cfn();
	//var_dump(${'admin_'.$TABLE_TAGS});
	$j = genLangJoin($TABLE_TAGS, ${'admin_' . $TABLE_TAGS});

	$savedValues = [];

	if ($id) {
		$qvals = "SELECT * FROM tags_assoc WHERE table_name = '" . $table . "' AND record_id = " . $id;
		$vals = pdoFetchAll($qvals);

		foreach ($vals as $val) {
			$savedValues[] = $val['tag_id'];
		}

		//var_dump($savedValues);
	}
	$q1 = 'SELECT * FROM ' . $TABLE_TAGS . ' ' . $j . ' WHERE parent_id = -1';
	$tags1 = pdoFetchAll($q1);

	foreach ($tags1 as $tag) {
		$res .= '<li>' . $tag[${'admin_' . $TABLE_TAGS}->ECHO_NAME];

		$q2 = 'SELECT * FROM ' . $TABLE_TAGS . ' ' . $j . ' WHERE parent_id = ' . $tag['id'];
		$tags2 = pdoFetchAll($q2);

		if (count($tags2) > 0) {
			$res .= '<ul>';
		}

		foreach ($tags2 as $tag2) {
			if (in_array($tag2['id'], $savedValues)) {
				$chk = 'checked';
			} else {
				$chk = '';
			}

			$res .= '<li>' . $tag2[${'admin_' . $TABLE_TAGS}->ECHO_NAME] . ' <input type="checkbox" name="tags_values[]" value="' . $tag2['id'] . '" ' . $chk . '/></li>';
		}

		if (count($tags2) > 0) {
			$res .= '</ul>';
		}

		$res .= '</li>
		';
	}
	return '<ul>' . $res . '</ul>';
}

function genAssocBlock($params)
{
	$res = '<script language="JavaScript" type="text/javascript">
					function List_' . $params['tableRubsAssoc'] . "(under,addrub,delrub,rpage)
					{
						$.ajax({
						  type: \"GET\",
						  url: 'ajax/assoc.php',
						  data: 'under='+under+'&addrub='+addrub+'&delrub='+delrub+'&rpage='+rpage+'&jfname=List_" . $params['tableRubsAssoc'] . '&id=' . $params['id'] . '&tabler=&tablea=' . $params['tableRubsAssoc'] . "&xr='+Math.random(),
						  success: function(answer) {
						  $('#assoc_div_" . $params['tableRubsAssoc'] . "').html(answer);
						  }
						 });
					}
				</script>";
	$res .= '<img src="img/link_icon.png" height="25" border="0"> 
					<strong style="font-size:16px; font-weight: bold;cursor:pointer;" onclick="$(\'#assoc_div_' . $params['tableRubsAssoc'] . '\').toggle()">' . $params['name'] . '</strong>
					<br/>
					<input type="hidden" name="random_insert_id" value="' . $params['id'] . '" />
					<div id="assoc_div_' . $params['tableRubsAssoc'] . '" style="width:400px;padding-top:10px;display:none;">
						<script language="javascript">List_' . $params['tableRubsAssoc'] . '(0);</script>
					</div>';
	return $res;
}

//Recursive path save
function saveCatRelations($cat_id, $rub_id, $under_name, $renew = 0)
{
	global $tabler, ${'admin_' . $tabler};

	static $rel = [];
	static $level = 0;

	if ($renew == 1) {
		$rel = [];
		$level = 0;
	}

	$q = 'SELECT * FROM ' . $tabler . " WHERE id = '" . $rub_id . "'";
	$res = mQuery($q);
	$row = mFetchAssoc($res);

	//Level 1 - No recursion
	if ($row['id'] == $row[$under_name]) {
		echo '<h1>Категория привязана сама к себе!!!</h1>';
	} elseif ($row[$under_name] <= 0) {
		//$level++;

		$rel[] = -1;

		mQuery('DELETE FROM ' . $tabler . "_relations 
                        WHERE record_id = '" . $cat_id . "'");
		$i = 0;
		for ($l = $level; $l >= 0; $l--) {
			$qi = 'INSERT INTO ' . $tabler . "_relations  (
                `record_id` ,
                `parent_id` ,
                `level`
                )
                VALUES (
                '" . $cat_id . "', '" . $rel[$i] . "', '" . $l . "'
                );";

			$res = mQuery($qi);
			$i++;
			// echo ' level = ' . $l . ' -> ' . $qi . ' '. mError() . '<hr/>';
		}
	} else {
		$rel[] = $row[$under_name];

		if ($row[$under_name] != $cat_id) {
			$level++;

			saveCatRelations($cat_id, $row[$under_name], $under_name);
		} else {
			echo '<h1>Категория привязана сама к себе!!!</h1>';
		}
	}
}

//Gens Assoc table for category
function genExtraParamsAssocTable($cat_id)
{
	if (!($cat_id > 0)) {
		$cat_id = -1;
	}

	global $tabler,${'admin_' . $tabler},$word,$ALANG;

	$TABLE_PARAMS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS;
	$TABLE_PARAMS_GROUPS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS_GROUPS;
	$TABLE_PARAMS_ASSOC_RUBS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS_ASSOC;

	$tbl = '<table class="table">';

	$query = "SELECT * FROM $TABLE_PARAMS_GROUPS 
            WHERE parent_id = -1";

	$result = mQuery($query);
	$num = mNumRows($result);

	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$row['name_' . mainLang()] = stripslashes($row['name_' . mainLang()]);
		$tbl .= '<tr>
    <td><strong>' . $row['name_' . mainLang()] . "</strong> [ <a href=\"javascript:selectGroupChbx('chbx_$row[id]')\" title=\"Check all\">&radic;</a> ]</td>
    <td>Вкл</td><td>Админ фильтр</td>
  </tr>";
		$query2 = "SELECT * FROM $TABLE_PARAMS 
                    WHERE group_id = $row[id]";
		$result2 = mQuery($query2);
		$num2 = mNumRows($result2);

		for ($i2 = 0; $i2 < $num2; $i2++) {
			$row2 = mFetchArray($result2);
			$row2['name_' . mainLang()] = stripslashes($row2['name_' . mainLang()]);

			//For Adding
			if (isset($_GET['addid']) && $_GET['addid'] > 0) {
				$query3 = "SELECT * FROM $TABLE_PARAMS_ASSOC_RUBS 
                            WHERE cat_id = $_GET[addid] and param_id = $row2[id]";

				$result3 = mQuery($query3);
				//echo $query3;

				if (mError() != '') {
					echo $query3 . mError();
				}

				$num3 = mNumRows($result3);

				if ($num3 > 0) {
					$chkd = 'checked="checked"';
					$chkdf = '';
				} else {
					$chkd = '';
					$chkdf = '';
				}
			}
			//For editing
			else {
				$query3 = "SELECT * FROM $TABLE_PARAMS_ASSOC_RUBS 
                            WHERE cat_id = $cat_id AND param_id = $row2[id]";
				$result3 = mQuery($query3);

				//echo $query3;

				if (mError() != '') {
					echo $query3 . mError();
				}
				$num3 = mNumRows($result3);
				if ($num3 > 0) {
					$chkd = 'checked="checked"';

					$rowAssoc = mFetchAssoc($result3);

					if ($rowAssoc['admin_filter'] > 0) {
						$chkdf = 'checked="checked"';
					} else {
						$chkdf = '';
					}
				} else {
					$chkd = '';
					$chkdf = '';
				}
			}

			if (($i2 + 1) % 2 == 0) {
				$rcolor = '#f9f9f9';
			} else {
				$rcolor = '#ffffff';
			}

			$tbl .= "<tr style=\"background-color:$rcolor\">
		<td>&nbsp;" . $row2['name_' . mainLang()] . ' #' . $row2['id'] . '</td>
		<td><input type="checkbox" class="chbx_' . $row['id'] . '" name="chb_' . $row2['id'] . '" 
            id="chb_' . $row2['id'] . '" value="1" ' . $chkd . ' /></td>
        <td><input type="checkbox" class="chbx_' . $row['id'] . '" name="chbf_' . $row2['id'] . '" 
            id="chbf_' . $row2['id'] . '" value="1" ' . $chkdf . ' /></td>
	  </tr>';
		}//end params in group
	} //end groups

	$tbl .= '</table>
    <input type="checkbox" value="1" name="params_to_childs"> Применить такие-же параметры для вложенных рубрик';
	return $tbl;
}

function getChildCategories($tabler, $catId, $under_name)
{
	static $childs = [];

	$query = "SELECT * FROM $tabler 
                WHERE $under_name = $catId AND $under_name != id";

	$result = mQuery($query);
	$num = mNumRows($result);

	for ($i = 0; $i < $num; $i++) {
		$rowChild = mFetchAssoc($result);

		$childs[] = $rowChild['id'];

		getChildCategories($tabler, $rowChild['id'], $under_name);
	}

	return $childs;
}

function saveExtraParamsAssocTable($id)
{
	global $tabler,${'admin_' . $tabler};
	saveExtraParamsCategory($id);

	//Recursive params
	if (isset($_POST['params_to_childs'])) {
		$childs = getChildCategories($tabler, $id, ${'admin_' . $tabler}->FIELD_UNDER);

		foreach ($childs as $catId) {
			saveExtraParamsCategory($catId);
			//echo 'saving params for #' . $catId . ' ';
		}
	}
}

function saveExtraParamsCategory($id)
{
	global $tabler,${'admin_' . $tabler};

	$TABLE_PARAMS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS;
	$TABLE_PARAMS_GROUPS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS_GROUPS;
	$TABLE_PARAMS_ASSOC_RUBS = ${'admin_' . $tabler}->TABLE_EXTRA_PARAMS_ASSOC;

	//Clean assoc for this category
	mQuery("DELETE FROM $TABLE_PARAMS_ASSOC_RUBS 
                    WHERE cat_id = $id");
	//New assoc insert
	$query2 = "SELECT * FROM $TABLE_PARAMS";
	$result2 = mQuery($query2);
	$num2 = mNumRows($result2);

	$addQ = "INSERT INTO $TABLE_PARAMS_ASSOC_RUBS (`cat_id` ,`param_id`, `admin_filter`) 
            VALUES";
	$first = 1;
	$n = 0;

	for ($i2 = 0; $i2 < $num2; $i2++) {
		$rowParam = mFetchAssoc($result2);

		if (isset($_POST['chb_' . $rowParam['id']])) {
			if ($first == 1) {
				$addQ .= ' ';
				$first = 0;
			} else {
				$addQ .= ', ';
			}

			if (!empty($_POST['chbf_' . $rowParam['id']])) {
				$admin_filter = 1;
			} else {
				$admin_filter = 0;
			}

			$addQ .= "('$id', '$rowParam[id]', $admin_filter)";
			$n++;
		}
	}
	//Executing query
	if ($n > 0) {
		mQuery($addQ);
	}
}

//Extra params in product
function genExtraParamsProduct($cat_id,$product_id)
{
	if (!($cat_id > 0)) {
		$cat_id = -1;
	}
	if (!($product_id > 0)) {
		$product_id = 0;
	}

	global $rowi,$tablei,${"admin_$tablei"},$word,$ALANG;

	$TABLE_PARAMS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS;
	$TABLE_PARAMS_GROUPS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_GROUPS;
	$TABLE_PARAMS_ASSOC_RUBS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_ASSOC;
	$TABLE_PARAMS_VALUES = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_VALUES;
	$TABLE_PARAMS_PROD_ASSOC = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_PRODUCTS_ASSOC;
	$TABLE_PARAMS_RANGES = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_RANGES;
	$USE_PARAMS_RANGES = ${'admin_' . $tablei}->USE_EXTRA_PARAMS_RANGES;

	/*Создаем объект варсов для работы с настройками*/
	$cfn = 'admin_' . $TABLE_PARAMS_VALUES;
	${'admin_' . $TABLE_PARAMS_VALUES} = new $cfn();

	$tbl = '<table class="table" style="width:700px;">
    <tr style="font-weight:bold;"><td>Параметр</td><td>Значение</td><td>Новое значение</td>';

	if (!empty($USE_PARAMS_RANGES)) {
		$tbl .= '<td>Диапазон (группа)</td>';
	}

	$tbl .= '</tr>';

	$rubs = '';

	//All params from assoc for this category
	$query = "SELECT * FROM $TABLE_PARAMS 
            WHERE id IN (SELECT param_id FROM $TABLE_PARAMS_ASSOC_RUBS where cat_id = $cat_id) 
            ORDER by group_id, sort desc";
	$result = mQuery($query);
	$num = mNumRows($result);

	for ($i = 0; $i < $num; $i++) {
		$rowParam = mFetchArray($result);
		$row['param_id'] = $rowParam['id'];
		$rowParam['name_' . mainLang()] = stripslashes($rowParam['name_' . mainLang()]);
		//var_dump ($rowParam);

		if (strstr($rubs,'r' . $rowParam['group_id'] . 'i') === FALSE) {
			$tbl .= '<tr><td colspan=2><br/><strong>' . RubName($TABLE_PARAMS_GROUPS, $rowParam['group_id'], 'name_' . mainLang()) . '</strong></td></tr>';
			$rubs .= 'r' . $rowParam['group_id'] . 'i';
		}

		$query3 = 'SELECT pa.*, r.name_' . mainLang() . " as range_name_1
                FROM $TABLE_PARAMS_PROD_ASSOC pa
                LEFT JOIN $TABLE_PARAMS_VALUES v ON (v.id = pa.value_id) 
                LEFT JOIN $TABLE_PARAMS_RANGES r ON (r.id = v.range_id) 
                    WHERE pa.param_id = $rowParam[id] AND pa.product_id = $product_id";

		$result3 = mQuery($query3);

		if (mError() != '') {
			echo $query3 . ' -> ' . mError();
		}

		$num3 = mNumRows($result3);

		$multiSavedValues = [];

		if ($num3 > 0) {
			for ($i3 = 0; $i3 < $num3; $i3++) {
				$rowSavedValue = mFetchArray($result3);
				$multiSavedValues[$i3] = $rowSavedValue['value_id'];
			}
		}
		//No value saved
		else {
			$rowSavedValue = [];
		}

		$query4 = 'SELECT v.*, r.name_' . mainLang() . " as range_name_1 
                    FROM $TABLE_PARAMS_VALUES v
                    LEFT JOIN $TABLE_PARAMS_RANGES r ON (r.id = v.range_id)
                    WHERE v.param_id = $rowParam[id] 
                    ORDER by " . ${'admin_' . $TABLE_PARAMS_VALUES}->SORT;

		$result4 = mQuery($query4);
		$num4 = mNumRows($result4);

		$tbl .= '<tr><td width="150">' . $rowParam['name_' . mainLang()] . '</td><td width="200">';

		//Multi variants selection
		if (isset($rowParam['multi_sel']) && $rowParam['multi_sel'] == 1) {
			$sbody = '';
			for ($i4 = 0; $i4 < $num4; $i4++) {
				$rowValue = mFetchArray($result4);
				$sel = '';
				if (is_array($multiSavedValues) && in_array($rowValue['id'],$multiSavedValues)) {
					$sel = 'checked="checked"';
				} else {
					$sel = '';
				}
				$sbody .= "<input name=\"param_$row[param_id]_1[$i4]\" type=\"checkbox\" value=\"$rowValue[id]\" $sel> " . stripslashes($rowValue['name_' . mainLang()] . (!empty($rowValue['range_name_1'])?' (' . $rowValue['range_name_1'] . ')':'')) . '<br>
                ';
			}
			$tbl .= $sbody . " <input name=\"paramvar_$row[param_id]\" type=\"hidden\" id=\"paramvar_$row[param_id]\" value=\"1\" />";
		}
		//Classic Select
		else {
			$sbody = '';

			//var_dump($rowSavedValue);

			for ($i4 = 0; $i4 < $num4; $i4++) {
				$rowValue = mFetchArray($result4);

				$sel = '';

				if (!empty($rowSavedValue['value_id']) && $rowSavedValue['value_id'] == $rowValue['id']) {
					$sel = 'selected';
				} else {
					$sel = '';
				}

				$sbody .= '<option value="' . $rowValue['id'] . '" ' . $sel . ' rel="' . $rowValue['range_name_1'] . '">' . stripslashes($rowValue['name_' . mainLang()]) . (!empty($rowValue['range_name_1'])?' (' . $rowValue['range_name_1'] . ')':'') . '</option>';
			}
			$tbl .= "
            <SELECT title=\"Вариант значения\" name=\"param_$row[param_id]_1\" id=\"param_$row[param_id]_1\" onchange=\"selectParam('$row[param_id]_1')\">
            <option value=0>Выбор...</option>" . $sbody . "</SELECT>
            <input name=\"paramvar_$row[param_id]\" type=\"hidden\" id=\"paramvar_$row[param_id]\" value=\"1\" />";
		}

		//New value
		$tbl .= "</td>
        <td>
        <input title=\"Новый вариант значения\" style=\"width:150px;\" name=\"parami_$row[param_id]_1\" type=\"text\" id=\"parami_$row[param_id]_1\" size=\"50\" maxlength=\"255\" value=\"\"/>";
		//if (${"admin_$tablei"}->EXTRA_PARAMS==2)	$tbl.="<input name=\"parami_$row[param_id]_2\" type=\"text\" id=\"parami_$row[param_id]_2\" size=\"50\" maxlength=\"255\" value=\"\"/>";

		$tbl .= '</td>';

		//Range
		if (!empty($USE_PARAMS_RANGES)) {
			$tbl .= "<td>
			<input title=\"Диапазон (группа значений)\" style=\"width:150px;\" name=\"param_range_$row[param_id]_1\" type=\"text\" id=\"param_range_$row[param_id]_1\" size=\"50\" 
				maxlength=\"255\" value=\"" . (!empty($rowSavedValue['range_name_1'])?$rowSavedValue['range_name_1']:'') . '"/>
			</td>';
		}

		$tbl .= '</tr>';

		unset($rowSavedValue,$multiSavedValues);
	} //end params list

	$tbl .= '</table>';

	return $tbl;
}

//Saving params
function saveExtraParamsProduct($cat_id,$product_id)
{
	if (!($cat_id > 0)) {
		$cat_id = -1;
	}
	if (!($product_id > 0)) {
		$product_id = 0;
	}

	global $rowi,$tablei,${"admin_$tablei"},$word,$ALANG;

	$TABLE_PARAMS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS;
	$TABLE_PARAMS_GROUPS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_GROUPS;
	$TABLE_PARAMS_ASSOC_RUBS = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_ASSOC;
	$TABLE_PARAMS_VALUES = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_VALUES;
	$TABLE_PARAMS_PROD_ASSOC = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_PRODUCTS_ASSOC;
	$TABLE_PARAMS_RANGES = ${'admin_' . $tablei}->TABLE_EXTRA_PARAMS_RANGES;

	//Cleaning
	$query = "DELETE FROM $TABLE_PARAMS_PROD_ASSOC 
            WHERE product_id=$product_id";
	$result = mQuery($query);

	$query = "SELECT a.*, p.is_float 
            FROM $TABLE_PARAMS_ASSOC_RUBS a 
            LEFT JOIN $TABLE_PARAMS p ON (p.id = a.param_id)
            WHERE cat_id = $cat_id";
	$result = mQuery($query);
	$num = mNumRows($result);

	//prosmotr v POSTe vseh zna4eniy
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		//var_dump($row);

		if (!empty($_POST['param_' . $row['param_id'] . '_1']) || !empty($_POST['parami_' . $row['param_id'] . '_1'])) {
			$param = $_POST['param_' . $row['param_id'] . '_1'];

			//Value entered && param is float
			if (!empty($_POST['parami_' . $row['param_id'] . '_1']) && $row['is_float'] == 1) {
				$value_float = floatval($_POST['parami_' . $row['param_id'] . '_1']);
				//echo "VALUE F = " . $value_float;
			} else {
				$value_float = 'NULL';
			}

			//Мультиселект
			if (is_array($param)) {
				foreach ($param as $val) {
					$query = "INSERT INTO $TABLE_PARAMS_PROD_ASSOC (`product_id` ,`param_id` ,`value_id`) VALUES ('$product_id' , '$row[param_id]', '$val')";
					//echo $query.";<br>";
					mQuery($query);
					echo mError();
				}
			}
			//Одно значение
			else {
				//Range
				if ($value_float != 'NULL' || !empty($_POST['param_range_' . $row['param_id'] . '_1'])) {
					$range_name_1 = $_POST['param_range_' . $row['param_id'] . '_1'];

					//Поиск по цифровому диапазону
					if ($row['is_float'] == 1) {
						$qs = "SELECT * FROM $TABLE_PARAMS_RANGES 
                                WHERE value_min <= '" . $value_float . "' AND value_max >= '" . $value_float . "' 
                                AND param_id=" . $row['param_id'];
					}
					//Поиск по названию диапазона
					else {
						$qs = "SELECT * FROM $TABLE_PARAMS_RANGES 
                                WHERE name_1 = '" . $range_name_1 . "' AND param_id = " . $row['param_id'];
					}

					$ress = mQuery($qs);
					//echo $qs.mError();
					$nums = mNumRows($ress);
					//Есть такой вариант в таблице
					if ($nums > 0) {
						$rows = mFetchAssoc($ress);
						$range_id = $rows['id'];
						//echo ' Range founded ';
					}
					//Такого варианта еще нет, создаем новый
					else {
						if (($row['is_float'] == 1) && strpos($range_name_1, '-') !== false) {
							$t = explode('-',$range_name_1);
							$value_min = floatval($t[0]);
							$value_max = floatval($t[1]);

							$qi = "INSERT INTO $TABLE_PARAMS_RANGES 
                            SET `name_1`='" . $range_name_1 . "', 
                            `value_min`='" . $value_min . "', `value_max`='" . $value_max . "', 
                            `param_id`=" . $row['param_id'];
						} else {
							$qi = "INSERT INTO $TABLE_PARAMS_RANGES 
                            SET `name_1`='$range_name_1', param_id=" . $row['param_id'];
						}
						$resi = mQuery($qi);
						//echo $qi.mError();

						$range_id = mInsertId();

						mQuery("UPDATE $TABLE_PARAMS_RANGES 
                                        SET sort = $range_id WHERE id = $range_id");
						//echo ' Range added ';
					}
				} else {
					$range_id = 0;
				}

				//Value ID
				$val = addslashes($_POST['param_' . $row['param_id'] . '_1']);

				//Text values
				$vali = addslashes($_POST['parami_' . $row['param_id'] . '_1']);
				$vali2 = isset($_POST['parami_' . $row['param_id'] . '_2'])?addslashes($_POST['parami_' . $row['param_id'] . '_2']):'';

				if (!empty($vali)) {
					//Проверка и создание варианта значения
					$qs = "SELECT * FROM $TABLE_PARAMS_VALUES 
                        WHERE name_1='$vali' AND param_id=" . $row['param_id'];
					$ress = mQuery($qs);
					echo mError();
					$nums = mNumRows($ress);
					//Есть такой вариант в таблице
					if ($nums > 0) {
						$rows = mFetchAssoc($ress);
						$val = $rows['id'];

						//Renew Float value
						mQuery("UPDATE $TABLE_PARAMS_VALUES 
                                    SET value_float = " . $value_float . "
                                    WHERE id = $val");

						//Renew range for old value
						if ($range_id > 0 && $range_id != $rows['range_id']) {
							$resUpRange = mQuery("UPDATE $TABLE_PARAMS_VALUES SET range_id = $range_id
                                                        WHERE id = $val");
							/*if ($resUpRange == 1)
							    echo " Param val's range updated ";*/
						}
					}
					//Такого value еще нет, создаем новый
					else {
						$qi = "INSERT INTO $TABLE_PARAMS_VALUES 
                            SET `name_1`='$vali', `value_float` = " . $value_float . ', 
                            param_id=' . $row['param_id'] . ', range_id = ' . $range_id;
						$resi = mQuery($qi);
						echo mError();
						$val = mInsertId();

						mQuery("UPDATE $TABLE_PARAMS_VALUES 
                                        SET sort = $val WHERE id = $val");
					}
				} else {
					//Renew range for old value

					if ($range_id > 0) {
						$resUpRange = mQuery("UPDATE $TABLE_PARAMS_VALUES SET range_id = $range_id
                                                WHERE id = $val");
					}
					/*if ($resUpRange == 1)
					    echo " Param val's range updated ";*/
				}

				if (!($val > 0)) {
					echo ' <strong>PARAM ' . $row['param_id'] . ' EMPTY!</strong> ';
				}

				//paramvar -> Variant!
				//if ($_POST['paramvar_'.$row['param_id']] == '1')
				$query = "INSERT INTO $TABLE_PARAMS_PROD_ASSOC (`product_id` ,`param_id` ,`value_id`) 
                            VALUES ('$product_id' , '$row[param_id]', '$val')";
				//echo $query . '<br/>';
				mQuery($query);
				unset($val,$vali,$vali2);
			}
			//echo mError();
		}
	}

	return $result;
}

//Rekursivniy poisk parenta
function getRoot($table_rubs,$id,$end = -1)
{
	static $countw;
	$query = "SELECT * FROM $table_rubs WHERE id=$id;";
	//echo $query;
	$result = mQuery($query);
	$row = mFetchArray($result);
//	$row=I_ReplSls($row);
	$countw++;
	//echo '->'.$row['under'].'<br>';
	if ($row['under'] == $end) {
		$countw = 0;
		return $row;
	} else if ($row['under'] < 1 || $countw >= 10) {
		$countw = 0;
		return 0;
	}
	return getRoot($table_rubs,$row['under'],$end);
}

//image folder
function GetImageFolder($table,$img_id)
{
	global ${"admin_$table"};
	if (isset(${"admin_$table"}->FOLDER_IMAGES) && (${"admin_$table"}->FOLDER_IMAGES == 1 || ${"admin_$table"}->FOLDER_IMAGES == 3)) {
		if (!isset(${"admin_$table"}->IMG_FIELD) || ${"admin_$table"}->IMG_FIELD == 'creation_time') {
			$folder = $table . '/' . date('Y-m',$img_id) . '/';
		} else {
			$f = (int)($img_id / 1000);
			$folder = $table . '/' . $f . '/';
		}
	} elseif (isset(${"admin_$table"}->FOLDER_IMAGES) && ${"admin_$table"}->FOLDER_IMAGES == 2) {
		$folder = $table . '/';
	} else {
		$folder = '';
	}
	return $folder;
}

//Multi access File Upload
function MaFU($filepath,$filename,$folder = 'images',$filemask = '')
{
	global $pref,$FOLDER_IMAGES,$serv,$snum;
	if ($snum > 0) {
		if (function_exists('ftp_connect')) {
			for ($i = 2; $i <= $snum + 1; $i++) {
				$tempname = "$pref$FOLDER_IMAGES/tmp/$filename";
				if (!file_exists("$pref$FOLDER_IMAGES/tmp/")) {
					mkdir("$pref$FOLDER_IMAGES/tmp/",0777);
				}
				//if (file_exists("../img/mask_$i.png") && !ereg('.s.',$filename)) ImgDrawMask($filepath,$tempname,"../img/mask_$i.png");
				if (!empty($filemask) && file_exists($filemask) && !preg_match('/\.s\./', $filename)) {
					ImgDrawMask($filepath,$tempname,$filemask);
				} else {
					copy($filepath,$tempname);
				}

				// Connect server
				$conn_id = ftp_connect($serv[$i]['host']);

				// Open a session to an external ftp site
				$login_result = ftp_login($conn_id, $serv[$i]['login'], $serv[$i]['pass']);

				// Check open
				if ((!$conn_id) || (!$login_result)) {
					//echo "Ftp-connect failed!"; die;
				} else {
					//echo "Connected.";
				}

				// turn on passive mode transfers
				ftp_pasv($conn_id, true);

				ftp_pwd($conn_id); //echo "Текущая директория: " . ftp_pwd($conn_id) . "\n";

				// попытка сделать somedir текущей
				if (ftp_chdir($conn_id, $serv[$i]['dir'] . '/' . $folder)) {
					ftp_pwd($conn_id); //echo "Новая текущая директория: " . ftp_pwd($conn_id) . "\n";
				} else {
					//echo "Не удалось сменить директорию\n";
				}

				// загрузка файла
				if (ftp_put($conn_id, $filename, $tempname, FTP_BINARY)) {
					// echo "$filename загружен на сервер. ";
				} else {
					// echo "Не удалось загрузить $filename на сервер. ";
				}

				ftp_close($conn_id);
				unlink($tempname);
			}
		} else {
			echo ' FTP not supported! ';
		}
	}
}
//end MaFU

//Multi access File Delete
function MaFD($filename, $folder = 'images')
{
	global $serv,$snum;
	if ($snum > 0) {
		if (function_exists('ftp_connect')) {
			for ($i = 2; $i <= $snum + 1; $i++) {
				// Connect server
				$conn_id = ftp_connect($serv[$i]['host']);

				// Open a session to an external ftp site
				$login_result = ftp_login($conn_id, $serv[$i]['login'], $serv[$i]['pass']);

				// Check open
				if ((!$conn_id) || (!$login_result)) {
					//echo "Ftp-connect failed!"; die;
				} else {
					//echo "Connected.";
				}

				// turn on passive mode transfers
				ftp_pasv($conn_id, true);

				ftp_pwd($conn_id); //echo "Текущая директория: " . ftp_pwd($conn_id) . "\n";

				// попытка сделать somedir текущей
				if (ftp_chdir($conn_id, $serv[$i]['dir'] . '/' . $folder)) {
					ftp_pwd($conn_id); //echo "Новая текущая директория: " . ftp_pwd($conn_id) . "\n";
				} else {
					//echo "Не удалось сменить директорию\n";
				}

				// удаление файла

				if (is_array($filename)) {
					foreach ($filename as $file) {
						@ftp_delete($conn_id, $file);
					}
				} else {
					@ftp_delete($conn_id, $filename);
				}

				ftp_close($conn_id);
			}
		} else {
			echo ' FTP not supported! ';
		}
	}
}
//end MaFU

function addLog($table,$action,$recID)
{
	global $TABLE_ADMINS_LOG;

	if (isset($TABLE_ADMINS_LOG)) {
		if ($_SERVER['REMOTE_ADDR'] != '') {
			$ip = ip2long($_SERVER['REMOTE_ADDR']);
		} elseif ($_SERVER['HTTP_X_REAL_IP']) {
			$ip = ip2long($_SERVER['HTTP_X_REAL_IP']);
		}

		$adminLogin = $_SESSION['admin']['name']
			?? ($_SESSION['admin_name'] ?? '');

		$q = "INSERT INTO `$TABLE_ADMINS_LOG` (`id` ,`login` ,`ip`,`table_name` ,`action` ,`rec_id` ,`creation_time`)
VALUES (NULL , '" . $adminLogin . "', '" . $ip . "', '" . $table . "', '" . $action . "', '" . $recID . "', '" . date('U') . "');";

		$res = pdoExec($q);

		if ($res === false) {
			var_dump(pdoError());
		}
	}

	return;
}

function getLog($table, $recID)
{
	global $TABLE_ADMINS_LOG;

	$q = "SELECT * FROM $TABLE_ADMINS_LOG 
			WHERE table_name = '$table' AND rec_id='$recID' ORDER by id DESC";
	$res = pdoFetchAll($q);

	return $res;
}

function genLog($table, $recID)
{
	global $word, $ALANG;
	$log = getLog($table, $recID);
	if (count($log) > 0) {
		echo '<p>' . $word[$ALANG]['first_edit'] . ': <strong>' . end($log)['login'] . '</strong> - ' . date('d-m-Y H:i', end($log)['creation_time']) . '</p>';
	}
	if (count($log) > 1) {
		echo '<p>' . $word[$ALANG]['last_edit'] . ': <strong>' . $log[0]['login'] . '</strong> - ' . date('d-m-Y H:i', $log[0]['creation_time']) . '</p>';
	}
}

function  str_to_l($q)
{
	$q = strtolower($q);
	$q = str_replace('А','а',$q);
	$q = str_replace('Б','б',$q);
	$q = str_replace('В','в',$q);
	$q = str_replace('Г','г',$q);
	$q = str_replace('Д','д',$q);
	$q = str_replace('Е','е',$q);
	$q = str_replace('Ж','ж',$q);
	$q = str_replace('З','з',$q);
	$q = str_replace('И','и',$q);
	$q = str_replace('Й','й',$q);
	$q = str_replace('І','і',$q);
	$q = str_replace('Ї','ї',$q);
	$q = str_replace('К','к',$q);
	$q = str_replace('Л','л',$q);
	$q = str_replace('М','м',$q);
	$q = str_replace('Н','н',$q);
	$q = str_replace('О','о',$q);
	$q = str_replace('П','п',$q);
	$q = str_replace('Р','р',$q);
	$q = str_replace('С','с',$q);
	$q = str_replace('Т','т',$q);
	$q = str_replace('У','к',$q);
	$q = str_replace('Ф','ф',$q);
	$q = str_replace('Х','х',$q);
	$q = str_replace('Ц','ц',$q);
	$q = str_replace('Ч','ч',$q);
	$q = str_replace('Ч','ч',$q);
	$q = str_replace('Ш','ш',$q);
	$q = str_replace('Щ','щ',$q);
	$q = str_replace('Э','э',$q);
	$q = str_replace('Ю','ю',$q);
	$q = str_replace('Я','я',$q);
	$q = str_replace('Ь','ь',$q);
	$q = str_replace('Ъ','ъ',$q);
	$q = str_replace('Ы','ы',$q);

	return $q;
}

function  Translit($q)
{
	$q = trim($q);

	$q = str_replace('А','A',$q);
	$q = str_replace('Б','B',$q);
	$q = str_replace('В','V',$q);
	$q = str_replace('Г','G',$q);
	$q = str_replace('Д','D',$q);
	$q = str_replace('Е','E',$q);
	$q = str_replace('Є','E',$q);
	$q = str_replace('Ж','Zh',$q);
	$q = str_replace('З','Z',$q);
	$q = str_replace('И','I',$q);
	$q = str_replace('Й','J',$q);
	$q = str_replace('І','I',$q);
	$q = str_replace('Ї','I',$q);
	$q = str_replace('К','K',$q);
	$q = str_replace('Л','L',$q);
	$q = str_replace('М','M',$q);
	$q = str_replace('Н','N',$q);
	$q = str_replace('О','O',$q);
	$q = str_replace('П','P',$q);
	$q = str_replace('Р','R',$q);
	$q = str_replace('С','S',$q);
	$q = str_replace('Т','T',$q);
	$q = str_replace('У','U',$q);
	$q = str_replace('Ф','F',$q);
	$q = str_replace('Х','H',$q);
	$q = str_replace('Ц','C',$q);
	$q = str_replace('Ч','Ch',$q);
	$q = str_replace('Ш','Sh',$q);
	$q = str_replace('Щ','Sch',$q);
	$q = str_replace('Э','E',$q);
	$q = str_replace('Ю','U',$q);
	$q = str_replace('Я','Ya',$q);
	$q = str_replace('Ь','',$q);
	$q = str_replace('Ъ','',$q);
	$q = str_replace('Ы','u',$q);

	$q = str_replace('а','a',$q);
	$q = str_replace('б','b',$q);
	$q = str_replace('в','v',$q);
	$q = str_replace('г','g',$q);
	$q = str_replace('д','d',$q);
	$q = str_replace('е','e',$q);
	$q = str_replace('є','e',$q);
	$q = str_replace('ж','zh',$q);
	$q = str_replace('з','z',$q);
	$q = str_replace('и','i',$q);
	$q = str_replace('й','j',$q);
	$q = str_replace('і','i',$q);
	$q = str_replace('ї','i',$q);
	$q = str_replace('к','k',$q);
	$q = str_replace('л','l',$q);
	$q = str_replace('м','m',$q);
	$q = str_replace('н','n',$q);
	$q = str_replace('о','o',$q);
	$q = str_replace('п','p',$q);
	$q = str_replace('р','r',$q);
	$q = str_replace('с','s',$q);
	$q = str_replace('т','t',$q);
	$q = str_replace('у','u',$q);
	$q = str_replace('ф','f',$q);
	$q = str_replace('х','h',$q);
	$q = str_replace('ц','c',$q);
	$q = str_replace('ч','ch',$q);
	$q = str_replace('ш','sh',$q);
	$q = str_replace('щ','sch',$q);
	$q = str_replace('э','e',$q);
	$q = str_replace('ю','u',$q);
	$q = str_replace('я','ya',$q);
	$q = str_replace('ь','',$q);
	$q = str_replace('ъ','',$q);
	$q = str_replace('ы','u',$q);

	$q = str_replace('(',' ',$q);
	$q = str_replace(')',' ',$q);
	//$q = str_replace("."," ",$q);
	$q = str_replace(',',' ',$q);
	$q = str_replace(':',' ',$q);
	$q = str_replace(';',' ',$q);
	$q = str_replace('@',' ',$q);
	$q = str_replace('!',' ',$q);
	$q = str_replace('`',' ',$q);
	$q = str_replace("'",' ',$q);
	$q = str_replace('"',' ',$q);
	$q = str_replace('#',' ',$q);
	$q = str_replace('$',' ',$q);
	$q = str_replace('%',' ',$q);
	$q = str_replace('^',' ',$q);
	$q = str_replace('&',' ',$q);
	$q = str_replace('*',' ',$q);
	$q = str_replace('_',' ',$q);
	$q = str_replace('=',' ',$q);
	$q = str_replace('+',' ',$q);
	$q = str_replace('<',' ',$q);
	$q = str_replace('>',' ',$q);
	$q = str_replace('?',' ',$q);
	$q = str_replace('/',' ',$q);

	$q = trim($q);

	$q = str_replace('  ',' ',$q);
	$q = str_replace(' ','-',$q);
	return strtolower($q);
}

//Images and files upload
function uploadImagesAndFiles($table,$creation_time,$copy = 0,$sub = NULL)
{
	global $pref,$FOLDER_FILES,$FOLDER_IMAGES,$NO_ADMIN,$_FILES,${"admin_$table"},$row,$filenamepos,$formatpos,$preview_pos;

	if (!is_object(${"admin_$table"})) {
		$cfn = 'admin_' . $table;
		${'admin_' . $table} = new $cfn();
	}

	if (isset(${"admin_$table"}->IMG_NUM) && ${"admin_$table"}->IMG_NUM > 0) {
		$imgnum = ${"admin_$table"}->IMG_NUM;
	} else {
		$imgnum = 0;
	}

	if (isset(${"admin_$table"}->IMG_TYPE)) {
		$imgType = ${"admin_$table"}->IMG_TYPE;
	} else {
		$imgType = 'jpg';
	}

	if (!empty(${"admin_$table"}->IMG_MASK) && file_exists("$pref$FOLDER_IMAGES/" . ${"admin_$table"}->IMG_MASK)) {
		$imgMask = "$pref$FOLDER_IMAGES/" . ${"admin_$table"}->IMG_MASK;
	} else {
		$imgMask = '';
	}

	if (!file_exists("$pref$FOLDER_IMAGES/")) {
		mkdir("$pref$FOLDER_IMAGES/",0777);
	}

	$folder = GetImageFolder($table,$creation_time);
	if ($folder != '') {
		if (!file_exists("$pref$FOLDER_IMAGES/$table")) {
			mkdir("$pref$FOLDER_IMAGES/$table",0777);
		}
		if (!file_exists("$pref$FOLDER_IMAGES/$folder")) {
			mkdir("$pref$FOLDER_IMAGES/$folder",0777);
		}
	}

	//All images variants
	if (isset(${"admin_$table"}->FOLDER_IMAGES) && ${"admin_$table"}->FOLDER_IMAGES >= 2) {
		$filePref = '';
	} else {
		$filePref = $table . '.';
	}

	$lastImgIndex = 0;

	for ($i = 1; $i <= $imgnum; $i++) {
		$filename0 = $filePref . $creation_time . ".$i.s0.jpg";
		$filename1 = $filePref . $creation_time . ".$i.s.jpg";
		$filename2 = $filePref . $creation_time . ".$i.b." . $imgType;
		$filenameM2 = $filePref . $creation_time . ".$i.m.b.jpg";

		$newname0 = "$pref$FOLDER_IMAGES/$folder$filename0";
		$newname1 = "$pref$FOLDER_IMAGES/$folder$filename1";
		$newname2 = "$pref$FOLDER_IMAGES/$folder$filename2";
		$newnameM2 = "$pref$FOLDER_IMAGES/$folder$filenameM2";

		if (is_file($newname1)) {
			$lastImgIndex = $i;
		}

		//var_dump($_FILES);
		//Subitems
		if ($sub !== NULL) {
			$SRC = $_FILES['imgb_' . $sub]['tmp_name'];
			//echo $SRC.' -> '.$newname1;
		}
		//Normal
		else {
			$oldname = 'imgb' . $i;
			if (!empty($_FILES[$oldname]['tmp_name'])) {
				$SRC = $_FILES[$oldname]['tmp_name'];

				$copy = 0;
			} elseif (!empty($_POST['imgb_url_' . $i])) {
				$localPath = $pref . $FOLDER_IMAGES . '/temp.jpg';
				@unlink($pref . $FOLDER_IMAGES . '/temp.jpg');
				file_put_contents($localPath, file_get_contents($_POST['imgb_url_' . $i]));

				$SRC = $localPath;
				$copy = 1;
			} else {
				$SRC = '';
			}
		}

		//Хоть какая-то картинка есть
		if (!empty($SRC)) {
			//echo 'OK';
			@unlink($newname0);
			@unlink($newname1);
			@unlink($newname2);
			@unlink($newnameM2);

			//Супер малая
			if (!empty(${"admin_$table"}->IMG_SML_SIZE)) {
				ImgResize($SRC,${"admin_$table"}->IMG_SML_SIZE,$newname0,${"admin_$table"}->IMG_RESIZE_TYPE);
			}

			//Если из ПОСТа
			if ($copy == 0) {
				move_uploaded_file($SRC,$newname1);
			}
			//Если копируем
			else {
				copy($SRC,$newname1);
			}

			//Копируем
			copy($newname1, $newname2);

			//Права
			//@chmod($newname1,0644);chmod($newname2,0644);

			//Ресайзим маленький
			ImgResize($newname1,${"admin_$table"}->IMG_SIZE,$newname1,${"admin_$table"}->IMG_RESIZE_TYPE, ${"admin_$table"}->IMG_VSIZE);

			//Ресайзим большой если jpg
			if ($imgType == 'jpg') {
				ImgResize2($newname2,${"admin_$table"}->IMG_BIG_SIZE,${"admin_$table"}->IMG_BIG_VSIZE,$newname2);
			}

			if (!empty(${"admin_$table"}->IMG_TXT)) {
				$len1 = strlen(${"admin_$table"}->IMG_TXT);
				$len2 = strlen(${"admin_$table"}->IMG_TXT2);
				if ($len1 > $len2) {
					$len = $len1;
				} else {
					$len = $len2;
				}
				$len = $len * 8;
				ImgDrawText($newname2,$newname2,10,10,$len,20,3,${"admin_$table"}->IMG_TXT,${"admin_$table"}->IMG_TXT2);
			}

			// Mask
			if (!empty($imgMask)) {
				ImgDrawMask($newname2, $newname2, $imgMask, ${"admin_$table"}->IMG_MASK_TYPE);
			}

			//@move_uploaded_file ($imgb, $newname2);
			//@chmod($newname2,0644);

			$lastImgIndex = $i;
		}
	}

	// Multi images upload
	if (isset($_FILES['images_multi']['tmp_name']) && count($_FILES['images_multi']['tmp_name']) > 0) {
		$i = $lastImgIndex + 1;

		foreach ($_FILES['images_multi']['tmp_name'] as $SRC) {
			$filename1 = $filePref . $creation_time . ".$i.s.jpg";
			$filename2 = $filePref . $creation_time . ".$i.b." . $imgType;
			$newname1 = $pref . $FOLDER_IMAGES . '/' . $folder . $filename1;
			$newname2 = $pref . $FOLDER_IMAGES . '/' . $folder . $filename2;

			move_uploaded_file($SRC, $newname1);

			// Копируем
			copy($newname1, $newname2);

			// Ресайзим маленький
			ImgResize($newname1,${"admin_$table"}->IMG_SIZE,$newname1,${"admin_$table"}->IMG_RESIZE_TYPE, ${"admin_$table"}->IMG_VSIZE);

			// Ресайзим большой если jpg
			if ($imgType == 'jpg') {
				ImgResize2($newname2,${"admin_$table"}->IMG_BIG_SIZE,${"admin_$table"}->IMG_BIG_VSIZE,$newname2);
			}

			// Mask
			if (!empty($imgMask)) {
				ImgDrawMask($newname2, $newname2, $imgMask, ${"admin_$table"}->IMG_MASK_TYPE);
			}

			$i++;
		}
	}

	// Upload main file with format

	if (!empty($_FILES['upfile']['tmp_name'])) {
		@unlink($pref . "$FOLDER_FILES/" . $row['format'] . '/' . $row['filename']);
		@unlink($pref . "$FOLDER_FILES/" . $row['format'] . '/' . $row['creation_time'] . '.' . $row['format']);
		$afile = new AdminFile($creation_time,$_FILES);
		//var_dump($afile);

		if ($filenamepos !== null) {
			$filename = $afile->upload($_FILES['upfile']['name']);
		} else {
			$filename = $afile->upload();
		}

		if (($afile->format == 'jpg' || $afile->format == 'gif' || $afile->format == 'png') && $preview_pos > 0) {
			if (!is_dir($pref . "$FOLDER_FILES/preview/")) {
				mkdir($pref . "$FOLDER_FILES/preview/",0777);
			}

			ImgResize($pref . "$FOLDER_FILES/" . $afile->format . '/' . $filename,100,$pref . "$FOLDER_FILES/preview/" . $creation_time . '.jpg',3);
			${"admin_$table"}->fld[$preview_pos]->val = $creation_time . '.jpg';
		}

		${"admin_$table"}->fld[$formatpos]->val = $afile->format;
		${"admin_$table"}->fld[$filenamepos]->val = $filename;
		unset($afile);
	}

	//Uploading files uni
	$i = 0;
	while (isset(${"admin_$table"}->fld[$i]->name)) {
		if (${"admin_$table"}->fld[$i]->type == 11) {
			if ($_FILES[${"admin_$table"}->fld[$i]->name]['tmp_name'] != '') {
				$afile = new AdminFile($creation_time,$_FILES,${"admin_$table"}->fld[$i]->name);
				@unlink($pref . "$FOLDER_FILES/" . $afile->format . '/' . $row[${"admin_$table"}->fld[$i]->name]);
				${"admin_$table"}->fld[$i]->val = $afile->upload($_FILES[${"admin_$table"}->fld[$i]->name]['name']);
				unset($afile);
			}
		}
		$i++;
	}
	//end files
}

//O4istka cache
function CleanSiteCache()
{
	$dir = '../cache';
	if (file_exists($dir)) {
		$path = "$dir/.";
		$d = dir("$path");
		$d->rewind();
		while ($file = $d->read()) {
			if (($file != '..') && ($file != '.')) {
				@unlink("$dir/$file");
			}
		}
	}
}

function Gen_CheckBox($varname,$itemv)
{
	if ($itemv == 1) {
		$chk = 'checked=checked';
	} else {
		$chk = '';
	}
	return "<input name=\"$varname\" type=\"checkbox\" id=\"$varname\" value=\"1\" $chk>";
}

function Gen_SelectItems($table,$tabler,$under,$varname,$itemv) // Gen select ~ under...
{
	$sbody = '';
	if ($under) {
		$wh = "WHERE under='$under'";
	} else {
		$under = 'WHERE under>-2';
	}
	$query = "SELECT * FROM $table $wh ORDER BY under asc, name_1 asc";
	//echo $query;
	$result = mQuery($query);
	$num = mNumRows($result);
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$sel = '';
		if ($itemv == $row['id']) {
			$sel = 'selected';
		}
		$sbody .= '<option value=' . $row['id'] . " $sel>";
		if ($tabler != '') {
			$sbody .= RubName($tabler,$row['under']);
		}
		$sbody .= "/$row[name]$row[name_1]$row[zag_1]</option>";
	}
	return "<SELECT name=\"$varname\"><option value=-1>...</option>" . $sbody . '</SELECT>';
}

function Gen_Select($table,$under,$varname,$itemv,$fld = NULL, $disabled = '') // Gen select ~ under...
{
	global ${"admin_$table"}, $_SERVER, $_GET, $_REQUEST;

	$langJoin = genLangJoin($table);

	if (!is_object(${"admin_$table"})) {
		$cfn = 'admin_' . $table;
		if (class_exists($cfn)) {
			${'admin_' . $table} = new $cfn();
		} else {
			echo 'no class in config_admin ' . $table;
		}
	}

	if (isset(${'admin_' . $table}->FIELD_UNDER)) {
		$under_name = ${'admin_' . $table}->FIELD_UNDER;
	} else {
		$under_name = 'under';
	}

	$level = 4;
	if (is_object($fld)) {
		if (isset($fld->extra_param['LEVELS'])) {
			$level = $fld->extra_param['LEVELS'];
		} elseif ($fld->extra_param != NULL && !is_array($fld->extra_param)) {
			$params = explode(';',$fld->extra_param);

			if ($params[0] > 0) {
				$level = $params[0];
			}
		}
	}

	if ($fld->table_field != '') {
		$fname = $fld->table_field;
	} else {
		$fname = 'name_' . mainLang();
	}

	if (isset($params[1]) && $params[1] == 'NO_EDIT') {
		if ($itemv > 0) {
			$query = "SELECT * FROM $table WHERE id=$itemv";
			$result = mQuery($query);
			$num = mNumRows($result);
			$row = mFetchArray($result);
			return $row[$fname] . '<input type="hidden" name="' . $varname . '" value="' . $itemv . '"/>';
		} else {
			return '';
		}
	} else {
		$sbody = '';
		$whId = '';
		if ($under !== NULL) {
			$wh = "WHERE $under_name='$under'";
			//Do not link to self
			/*if (strstr($_SERVER['REQUEST_URI'],'catalog.php'))
				$whId = " AND $table.id != '".$_REQUEST['id']."'";
				$wh .= $whId; */
		} else {
			$wh = '';
		}

		if (isset($fld->extra_param['whereGenerator'])) {
			$gObj = new $fld->extra_param['whereGenerator']['class']();
			$wh = $gObj->{$fld->extra_param['whereGenerator']['method']}();
		}

		if (isset($fld->extra_param['ORDER'])) {
			$orderBy = $fld->extra_param['ORDER'];
		} else {
			$orderBy = (isset(${"admin_$table"}->SORT)?str_replace('%20',' ',${"admin_$table"}->SORT):'id asc');
		}
		/*if ($under!=0)
		{
			$wh="WHERE under='$under'";
			if (ereg('catalog.php',$_SERVER['REQUEST_URI'])) $wh.=" AND id <>".$_REQUEST['id'];
		}
		else
		{
			if (ereg('catalog.php',$_SERVER['REQUEST_URI'])) $wh.="WHERE  id <>".$_REQUEST['id'];
			else $wh="";
		}*/

		$query = "SELECT * FROM $table $langJoin $wh ORDER BY " . (isset($orderBy)?$orderBy:'id asc');
		//echo $query;
		$result = mQuery($query);
		$num = mNumRows($result);
		//echo $num;
		echo mError();

		for ($i = 0; $i < $num; $i++) {
			$row = mFetchArray($result);
			$sel = '';
			//echo $itemv.'=='.$row['id'].'<br />';

			if (trim((string)$itemv) == trim((string)$row['id'])) {
				$sel = 'selected';
			}
			//echo $itemv.$row['default'].' - ';
			if ($itemv == 0 && isset($row['default']) && $row['default'] == 1) {
				$sel = 'selected';
			}

			if ($under == -1 && $level > 1) {
				$sbody .= "<option value=\"$row[id]\" $sel>" . mb_substr(RubName($table,$row[$under_name]),0,20,'utf-8') . '/' . mb_substr($row[$fname],0,50,'utf-8') . '</option>';
				$query2 = "SELECT * FROM $table $langJoin WHERE $under_name=$row[id] $whId ORDER BY " . str_replace('%20',' ',${"admin_$table"}->SORT);
				//echo $query2;
				$result2 = mQuery($query2);
				$num2 = mNumRows($result2);
				for ($i2 = 0; $i2 < $num2; $i2++) {
					$row2 = mFetchArray($result2);
					$sel = '';
					if ($itemv == $row2['id']) {
						$sel = 'selected';
					}
					$sbody .= "<option value=$row2[id] $sel>" . mb_substr($row[$fname],0,20,'utf-8') . '/' . mb_substr($row2[$fname],0,50,'utf-8') . '</option>';
					$query3 = "SELECT * FROM $table $langJoin WHERE $under_name=$row2[id] $whId ORDER BY " . str_replace('%20',' ',${"admin_$table"}->SORT);
					//echo $query;
					$result3 = mQuery($query3);
					$num3 = mNumRows($result3);
					for ($i3 = 0; $i3 < $num3; $i3++) {
						$row3 = mFetchArray($result3);
						$sel = '';
						if ($itemv == $row3['id']) {
							$sel = 'selected';
						}
						$sbody .= "<option value=$row3[id] $sel>/" . mb_substr($row2[$fname],0,20,'utf-8') . '/' . mb_substr($row3[$fname],0,50,'utf-8') . '</option>';
						$query4 = "SELECT * FROM $table $langJoin WHERE $under_name=$row3[id] $whId ORDER BY " . str_replace('%20',' ',${"admin_$table"}->SORT);
						//echo $query;
						$result4 = mQuery($query4);
						$num4 = mNumRows($result4);
						for ($i4 = 0; $i4 < $num4; $i4++) {
							$row4 = mFetchArray($result4);
							$sel = '';
							if ($itemv == $row4['id']) {
								$sel = 'selected';
							}
							$sbody .= "<option value=$row4[id] $sel>/" . mb_substr($row3[$fname],0,20,'utf-8') . '/' . mb_substr($row4[$fname],0,50,'utf-8') . '</option>';
							$query5 = "SELECT * FROM $table $langJoin WHERE $under_name=$row4[id] $whId ORDER BY " . str_replace('%20',' ',${"admin_$table"}->SORT);
							//echo $query;
							$result5 = mQuery($query5);
							$num5 = mNumRows($result5);
							for ($i5 = 0; $i5 < $num5; $i5++) {
								$row5 = mFetchArray($result5);
								$sel = '';
								if ($itemv == $row5['id']) {
									$sel = 'selected';
								}
								$sbody .= "<option value=$row5[id] $sel>/" . mb_substr($row4[$fname],0,15,'utf-8') . '/' . mb_substr($row5[$fname],0,50,'utf-8') . '</option>';
							}
						}
					}
				}
			} else {
				$sbody .= "<option value=\"$row[id]\" $sel>$row[$fname]</option>";
			}
		}

		//Не даем возможность не привязать
		if (strpos($wh, ' =') === false && empty($fld->extra_param['required'])) {
			$rootVal = '<option value=-1>...</option>';
		} else {
			$rootVal = '';
		}

		if (!empty($fld->extra_param['attributes'])) {
			$attributes = $fld->extra_param['attributes'];
		} else {
			$attributes = '';
		}

		return "<SELECT name=\"$varname\" $disabled class=\"form-control\" $attributes >" . $rootVal . $sbody . '</SELECT>';
	}
}

function Gen_Select_Val($table,$under,$varname,$itemv) // Gen select ~ under...
{
	global ${"admin_$table"}, $_SERVER, $_GET, $_REQUEST;
	$sbody = '';
	if ($under) {
		$wh = "WHERE under='$under'";
	}

	$query = "SELECT * FROM $table $wh ORDER BY " . (isset(${"admin_$table"}->SORT)?str_replace('%20',' ',${"admin_$table"}->SORT):'id asc');
	//echo $query;
	$result = mQuery($query);
	$num = mNumRows($result);
	//echo $num;
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$sel = '';
		//echo $itemv.$row['name_'.mainLang()].strCaseCmp($itemv,$row['name_'.mainLang()]);
		if (strCaseCmp($itemv,$row['name_' . mainLang()]) == 0) {
			$sel = 'selected';
		}
		$sbody .= '<option value="' . $row['name_1'] . "\" $sel>" . substr($row['name_1'],0,50) . '</option>';
	}
	return "<SELECT name=\"$varname\"><option value=''>...</option>" . $sbody . '</SELECT>';
}

function Gen_SelectStatic($field,$itemv) // Gen select ~ under...
{
	$varname = $field->name;

	$sbody = '';

	foreach ($field->extra_param['values'] as $var) {
		if ($var == $itemv) {
			$sel = 'selected';
		} else {
			$sel = '';
		}

		$sbody .= "<option value=\"$var\" $sel>" . $var . '</option>';
	}
	return "<select name=\"$varname\" class=\"form-control\"><option value=''>...</option>" . $sbody . '</select>';
}

function Gen_SelectFinal($table,$under,$varname,$itemv) // Gen select ~ under...
{
	global ${"admin_$table"};
	$sbody = '';

	if ($under) {
		$wh = "WHERE under='$under'";
	} else {
		$under = 'WHERE under>-2';
	}

	$query = "SELECT * FROM $table $wh ORDER BY " . ${"admin_$table"}->SORT;
	//echo $query;

	$result = mQuery($query);
	$num = mNumRows($result);
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$sel = '';
		$query2 = "SELECT * FROM $table WHERE under=$row[id] ORDER BY " . ${"admin_$table"}->SORT;
		//echo $query;
		$result2 = mQuery($query2);
		$num2 = mNumRows($result2);
		if ($num2 > 0) {
			for ($i2 = 0; $i2 < $num2; $i2++) {
				$row2 = mFetchArray($result2);
				$sel = '';
				if ($itemv == $row2['id']) {
					$sel = 'selected';
				}
				$sbody .= '<option value="' . $row2['id'] . "\" $sel>";
				if ($row2['under'] != $under) {
					$sbody .= RubName($table,$row2['under']);
				}
				$sbody .= "/$row2[name]$row2[name_1]$row2[zag_1]</option>";
			}
		} else {
			if ($itemv == $row['id']) {
				$sel = 'selected';
			}
			$sbody .= '<option value="' . $row['id'] . "\" $sel>";
			if ($row['under'] != $under) {
				$sbody .= RubName($table,$row['under']);
			}
			$sbody .= "/$row[name]$row[name_1]$row[zag_1]</option>";
		}
	}
	return "<SELECT name=\"$varname\"><option value=-1>...</option>" . $sbody . '</SELECT>';
}

function ImgDrawText($file,$name,$x,$y,$x2,$y2,$fontsize,$txt1,$txt2)
{
	$array = getimagesize($file);
	$height = $array[1];
	$width = $array[0];

	$types = [
		1 => 'imagecreatefromgif',
		2 => 'imagecreatefromjpeg',
		3 => 'imagecreatefrompng',
	];

	$im = $types[$array[2]]($file);
	$sim = imagecreatetruecolor($width, $height);
	$black = imagecolorallocate($sim, 0, 0, 0);
	$cccc = imagecolorallocate($sim, 250, 250, 250);
	$white = imagecolorallocate($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));
	ImageString($sim,$fontsize,$x - 1, $y + 1, $txt1, $black);
	ImageString($sim,$fontsize,$x, $y, $txt1, $cccc);
	ImageString($sim,$fontsize,$x - 1, $y + 16, $txt2, $black);
	ImageString($sim,$fontsize,$x, $y + 15, $txt2, $cccc);

	ImageString($sim,$fontsize,$width - $x2 - 1, $height - $y2 - 14, $txt1, $black);
	ImageString($sim,$fontsize,$width - $x2, $height - $y2 - 15, $txt1, $cccc);
	ImageString($sim,$fontsize,$width - $x2 - 1, $height - $y2 + 1, $txt2, $black);
	ImageString($sim,$fontsize,$width - $x2, $height - $y2, $txt2, $cccc);
	imagejpeg($sim,$name,90);

	imagedestroy($sim);
	imagedestroy($im);

	return getimagesize($name);
}

function ifNotExistsMakeImgWithMask($table,$creation_time, $imgmask = 'mask.png', $i = 1)
{
	global $pref,$FOLDER_IMAGES;

	$folder = GetImageFolder($table,$creation_time);

	$filename2 = "$table.$creation_time.$i.b.jpg";
	$filenameM2 = "$table.$creation_time.$i.m.b.jpg";
	$newname2 = "$pref$FOLDER_IMAGES/$folder$filename2";
	$newnameM2 = "$pref$FOLDER_IMAGES/$folder$filenameM2";

	if (file_exists($newname2) && !file_exists($newnameM2)) {
		if (!empty($imgmask) && file_exists("$pref$FOLDER_IMAGES/" . $imgmask)) {
			$imgmask = "$pref$FOLDER_IMAGES/" . $imgmask;
			ImgDrawMask($newname2,$newnameM2,$imgmask,2);
		}
	}
}

function ImgDrawMask($file, $name, $pngfile, $type = 1)
{
	global $pref,$FOLDER_IMAGES;

	if (empty($pngfile)) {
		return;
	}

	$folder_clean = "$pref$FOLDER_IMAGES/clean/";
	$folder_normal = "$pref$FOLDER_IMAGES/";
	if (!file_exists($folder_clean)) {
		mkdir($folder_clean,0777);
	}
	copy($file,str_replace($folder_normal,$folder_clean,$file));
	$array = getimagesize($file);
	$arraypng = getimagesize($pngfile);

	$height = $array[1];
	$width = $array[0];

	$heightpng = $arraypng[1];
	$widthpng = $arraypng[0];

	$types = [
		1 => 'imagecreatefromgif',
		2 => 'imagecreatefromjpeg',
		3 => 'imagecreatefrompng',
	];

	//$im = $types[$array[2]]($file);
	$im = imagecreatefromjpeg($file);
	$impng = imagecreatefrompng($pngfile);

	$sim = imagecreatetruecolor($width, $height);
	$black = imagecolorallocate($sim, 0, 0, 0);
	$cccc = imagecolorallocate($sim, 150, 150, 150);
	$white = imagecolorallocate($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	if ($type == 2) { // mask in left top and right bottom corners of the picture
		$mask_place_w = $widthpng;
		if ($mask_place_w > $width) {
			$mask_place_w = ceil($width / 2);
		}
		$mask_place_h = ceil($mask_place_w * $heightpng / $widthpng);

		imagecopyresampled($sim, $im, 0, 0, 0, 0, $width, $height, $width, $height);
		imagecopyresampled($sim, $impng, 7, 7, 0, 0, $mask_place_w, $mask_place_h, $widthpng, $heightpng);
		imagecopyresampled($sim, $impng, $width - $widthpng - 7, $height - $heightpng - 7, 0, 0, $widthpng, $heightpng, $widthpng, $heightpng);
	} else { // mask in a middle of the picture
		imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));
		imagecopyresampled($sim,$impng,($width - $widthpng) / 2,($height - $heightpng) / 2,0,0,$widthpng,$heightpng,$widthpng,$heightpng);
	}

	imagejpeg($sim,$name,90);
	imagedestroy($sim);
	imagedestroy($im);

	return getimagesize($name);
}

function ImgResizeHor($file,$width,$name)
{
	$array = getimagesize($file);

	if ($array[0] <= $width) {
		$width = $array[0];
	}

	$height = $array[1] / ($array[0] / $width);

	$types = [
		1 => 'imagecreatefromgif',
		2 => 'imagecreatefromjpeg',
		3 => 'imagecreatefrompng',
	];

	$im = $types[$array[2]]($file);
	$sim = imagecreatetruecolor($width, $height);
	$white = imagecolorallocate($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));

	imagejpeg($sim,$name,90);

	imagedestroy($sim);
	imagedestroy($im);

	return getimagesize($name);
}

function ImgResizeVert($file,$height,$name)
{
	$array = getimagesize($file);

	if ($array[1] <= $height) {
		$height = $array[1];
	}

	$width = $array[0] / ($array[1] / $height);

	$types = [
		1 => 'imagecreatefromgif',
		2 => 'imagecreatefromjpeg',
		3 => 'imagecreatefrompng',
	];

	//var_dump($array);
	$im = $types[$array[2]]($file);
	$sim = imagecreatetruecolor($width, $height);
	$white = imagecolorallocate($sim, 255, 255, 255);
	imagefill($sim,0,0,$white);

	imagecopyresampled($sim,$im,0,0,0,0,($width),($height),($array[0]),($array[1]));

	imagejpeg($sim,$name,90);

	imagedestroy($sim);
	imagedestroy($im);

	return getimagesize($name);

	@move_uploaded_file($file,$name);
	@copy($file,$name);
	return 1;
}

function ImgResizeQuad($file,$size,$name)
{
	$array = getimagesize($file);
	if ($array[0] > $array[1]) {
		return ImgResizeHor($file,$size,$name);
	} else {
		return ImgResizeVert($file,$size,$name);
	}
}

function ImgResizeQuadCrop($fname, $side, $new_fname = NULL)
{
	if (!file_exists($fname)) {
		return 0;
	}
	if ($new_fname == NULL) {
		$new_fname = $fname;
	}
	$size = getimagesize($fname);
	$yet = (int)(abs($size[0] - $size[1]) / 2);
	if ($size[0] > $size[1]) {
		$point = [$yet, 0, $size[1], $size[1]];
	} else {
		$point = [0, $yet, $size[0], $size[0]];
	}
	switch ($size[2]) {
		case 1: $function = 'imagecreatefromgif';
			break;
		case 2: $function = 'imagecreatefromjpeg';
			break;
		case 3: $function = 'imagecreatefrompng';
			break;
	}
	$old_img = $function($fname);
	$new_img = imagecreatetruecolor($side, $side);
	imagecopyresampled($new_img, $old_img, 0, 0, $point[0], $point[1], $side, $side, $point[2], $point[3]);
	imagejpeg($new_img, $new_fname, 90);
	imagedestroy($old_img);
	imagedestroy($new_img);
	return 1;
}

function ImgResizeCrop($fname, $side, $vside, $new_fname = NULL)
{
	$k = $vside / $side;
	if (!file_exists($fname)) {
		return 0;
	}
	if ($new_fname == NULL) {
		$new_fname = $fname;
	}
	$size = getimagesize($fname);

	// Горизонтально вытянутая
	if ($size[0] * $k > $size[1]) {
		$innerPadding = (int)(($size[0] - $size[1] * $k) / 2);
		$cropPoints = ['src_x' => $innerPadding, 'src_y' => 0, 'w' => $size[1], 'h' => $size[1] * $k];
	}
	// Вертикальная
	else {
		$innerPadding = (int)(abs($size[0] * $k - $size[1]) / 2);
		$cropPoints = ['src_x' => 0, 'src_y' => $innerPadding, 'w' => $size[0], 'h' => $size[0] * $k];
	}

	switch ($size[2]) {
		case 1: $function = 'imagecreatefromgif';
			break;
		case 2: $function = 'imagecreatefromjpeg';
			break;
		case 3: $function = 'imagecreatefrompng';
			break;
	}

	$old_img = $function($fname);
	$new_img = imagecreatetruecolor($side, $side * $k);
	imagecopyresampled($new_img, $old_img, $dest_x = 0, $dest_y = 0, $cropPoints['src_x'], $cropPoints['src_y'], $dest_w = $side, $dest_h = $vside, $cropPoints['w'], $cropPoints['h']);
	imagejpeg($new_img, $new_fname, 90);
	imagedestroy($old_img);
	imagedestroy($new_img);
	return 1;
}

function ImgResize($file,$size,$name,$type,$vsize = 0)
{
	switch($type) {
		case 1:return ImgResizeHor($file,$size,$name);
		case 2:return ImgResizeVert($file,$size,$name);
		case 3:return ImgResizeQuad($file,$size,$name);
		case 4:return ImgResizeQuadCrop($file,$size,$name);
		case 5:return ImgResizeCrop($file,$size,$vsize,$name);
		default: return 0;
	}
}

function ImgResize2($file,$size1,$size2,$name)
{
	$array = getimagesize($file);
	if (($array[0] <= $size1) && ($array[1] <= $size2)) {
		if (strstr($file,'../upload/') !== FALSE) {
			@copy($file,$name);
		} else {
			@move_uploaded_file($file, $name);
		}
		return 0;
	} else {
		$d1 = $array[0] / $array[1];
		$d2 = $size1 / $size2;
		if ($d1 > $d2) {
			return ImgResizeHor($file,$size1,$name);
		} else {
			return ImgResizeVert($file,$size2,$name);
		}
	}
}

function RubName($table, $id, $echo_name = 'name')
{
	if ($table == '') {
		return;
	}
	if ($id == -1) {
		return '/';
	} else {
		$className = "admin_$table";

		if (class_exists($className)) {
			if (!isset(${$className})) {
				${$className} = new $className();
			}
		} else {
			echo 'No class ' . $className;
		}

		$rubsLangJoin = genLangJoin($table, ${'admin_' . $table});

		$query = "SELECT * FROM $table " . $rubsLangJoin . " WHERE `id`='$id'";
		$result = mQuery($query);
		$row = mFetchArray($result);
		return $row[$echo_name];
	}
}

function FetchID($table, $id)
{
	if ($table == '') {
		return '';
	} else {
		global ${"admin_$table"};

		if (!isset(${"admin_$table"})) {
			$fcn = "admin_$table";
			${"admin_$table"} = new $fcn();
		}

		$query = "SELECT * FROM $table WHERE id='$id'";
		//echo $query;
		/*$result= mQuery($query);

        if (mError() != '')
            echo mError();
        */
		$row = current(pdoFetchAll($query));

		if (!empty($row) && !empty(${"admin_$table"}->MULTI_LANG)) {
			global $LANGS;

			$multiLangFields = [];

			foreach (${"admin_$table"}->fld as $field) {
				if ($field->multi_lang == 1 && empty($field->extra_param['fromTable'])) {
					$multiLangFields[] = $field->name;
				}
			}

			foreach ($LANGS as $lang => $langName) {
				$q = 'SELECT ' . implode(',', $multiLangFields) . ' FROM ' . $table . '_info WHERE record_id = ' . $id . " AND lang = '" . $lang . "'";
				$rowInfo = pdoFetchAll($q);
				if (count($rowInfo) > 0) {
					foreach ($rowInfo[0] as $field => $value) {
						$row[$field . '_' . $lang] = $value;
					}
				}
			}
		}

		// Extra join
		if (!empty($row) && !empty(${"admin_$table"}->EXTRA_TABLES)) {
			global $LANGS;

			foreach (${"admin_$table"}->EXTRA_TABLES as $extraTable => $joinRule) {
				if (!isset(${"admin_$table"})) {
					$fcn = "admin_$table";
					${"admin_$table"} = new $fcn();
				}

				$multiLangFields = [];

				foreach (${"admin_$table"}->fld as $field) {
					if ($field->multi_lang == 1 && !empty($field->extra_param['fromTable'])) {
						$multiLangFields[] = $field->name;
					}
				}

				$id = AdminTable::getRelatedId($row, $extraTable, $joinRule);

				if ($id > 0) {
					foreach ($LANGS as $lang => $langName) {
						$q = 'SELECT ' . implode(',', $multiLangFields) . ' FROM ' . $extraTable . '_info WHERE record_id = ' . $id . " AND lang = '" . $lang . "'";
						$rowInfo = pdoFetchAll($q);
						if (count($rowInfo) > 0) {
							foreach ($rowInfo[0] as $field => $value) {
								$row[$field . '_' . $lang] = $value;
							}
						}
					}
				}
			}
		}

		return $row;
	}
}

// Multiply image selector
function printImageMultiInput()
{
	echo '<div>
		 <strong>Пакетная загрузка изображений (до ' . round(getFileUploadMaxSize() / 1000000) . ' мб)</strong>
		 <input type="file" id="images_multi" name="images_multi[]" multiple />
		 </div><br/><br/>';
}

//Inputs for image
function printImageInputs($src, $table, $row, $i, $fl = 'b')
{
	global $pref,$pref_url,$FOLDER_IMAGES,$FOLDER_IMAGES_FRONTEND,$tabler,${"admin_$table"},$under,$word,$ALANG;

	if (count($row) > 2) {
		if (!isset(${"admin_$table"}->IMG_FIELD) || ${"admin_$table"}->IMG_FIELD == 'creation_time') {
			$img_id = $row['creation_time'];
		} else {
			$img_id = $row[${"admin_$table"}->IMG_FIELD];
		}
		if (isset(${"admin_$table"}->IMG_TYPE)) {
			$img_type = ${"admin_$table"}->IMG_TYPE;
		} else {
			$img_type = 'jpg';
		}

		$folder = GetImageFolder($table, $img_id);

		if (is_file("$pref$FOLDER_IMAGES/$folder$img_id.$i.s.jpg")) {
			$imgUrl = "$pref_url$FOLDER_IMAGES_FRONTEND/$folder$img_id.$i.s.jpg";
			$imgBig = "$pref$FOLDER_IMAGES/$folder$img_id.$i.b." . $img_type;
			$imgBigUrl = "$pref_url$FOLDER_IMAGES_FRONTEND/$folder$img_id.$i.b." . $img_type;
			$imgFsShort = "$pref$FOLDER_IMAGES/$folder$img_id.$i";
		} elseif (is_file("$pref$FOLDER_IMAGES/$folder$table.$img_id.$i.s.jpg")) {
			$imgUrl = "$pref_url$FOLDER_IMAGES_FRONTEND/$folder$table.$img_id.$i.s.jpg";
			$imgBig = "$pref$FOLDER_IMAGES/$folder$table.$img_id.$i.b." . $img_type;
			$imgBigUrl = "$pref_url$FOLDER_IMAGES_FRONTEND/$folder$table.$img_id.$i.b." . $img_type;
			$imgFsShort = "$pref$FOLDER_IMAGES/$folder$table.$img_id.$i";
		}

		if (isset($imgBig) && ($fs = @filesize($imgBig)) > 1) {
			$fs = (int)($fs / 1024);
			if ($fs > 1050) {
				echo '<script language="javascript">alert("' . $word[$ALANG]['so_big_img'] . '");</script>';
			}
			echo " $fs kb <a class=\"grouped_elements\" rel=\"group_b\" title=\"Photo №$i\" href=\"$imgBigUrl\" target=\"_blank\"><img src=\"$imgUrl\" border=1 style=\"height:15px;\"></a> [ <a href=\"$src?id=$row[id]&tablei=$table&tabler=$tabler&under=$under&delimg=" . urlencode($imgFsShort) . '">' . imdelm . '</a> ]';
		}
	}

	echo '<br><input name="imgb' . $i . '" type="file" class="window" id="imgb' . $i . '">';
	echo '<label for="imgb_url_' . $i . '">Адрес картинки в интернете начиная с http://</label>
	<input name="imgb_url_' . $i . '" type="text" class="form-control" id="imgb_url_' . $i . '">';
}

function printUpFile($row,$file_an)
{
	global $word,$ALANG,$pref,$pref_url,$FOLDER_FILES;

	$menu = '';
	echo '<p class="txt"><strong>' . $file_an . '</strong>';

	//файл загружен
	if (!empty($row['format'])) {
		if (!empty($row['filename'])) {
			$filename = $pref . $FOLDER_FILES . '/' . $row['format'] . '/' . $row['filename'];
			$fs = (int)(@filesize($filename) / 1024);
			if (($row['format'] == 'jpg' || $row['format'] == 'gif') && $fs > 501) {
				echo '<script language="javascript">alert("' . $word[$ALANG]['so_big_img'] . '");</script>';
			}
			$menu = $fs . ' kb [ '; //<a href=\"$filename\" target=_blank>".imlook."</a>";
		} else {
			$filename = $pref . $FOLDER_FILES . '/' . $row['format'] . '/' . $row['creation_time'] . '.' . $row['format'];
			if (!is_file($filename)) {
				$filename = $pref . $FOLDER_FILES . '/' . $row['format'] . '/' . $row['id'] . '.' . $row['format'];
			}

			if (is_file($filename)) {
				$fs = (int)(@filesize($filename) / 1024);

				if (($row['format'] == 'jpg' || $row['format'] == 'gif') && $fs > 501) {
					echo '<script language="javascript">alert("' . $word[$ALANG]['so_big_img'] . '");</script>';
				}

				$menu = $fs . ' kb [ '; //<a href=\"$filename\" target=_blank>".imlook."</a>";
			} else {
				$menu = '';
			}
		}

		if (!empty($row['preview_name'])) {
			echo'<br />
    <a href="' . $pref_url . $FOLDER_FILES . '/' . $row['format'] . '/' . ($row['filename'] ?: ($row['creation_time'] . '.' . $row['format'])) . '" target=_blank><img src="' . $pref_url . $FOLDER_FILES . '/preview/' . $row['preview_name'] . '" border="1" /></a>';
		}

		$menu .= ' <input type="checkbox" name="deldocsfile" value="1"> ' . $word[$ALANG]['del'] . ' ]';
	}

	echo '<br><input name="upfile" type="file" class="window" id="upfile" size="25" maxlength="255"> ';

	if (isset($fs) && $fs > 0) {
		$urlPath = $pref_url . $FOLDER_FILES . '/' . $row['format'] . '/' . ($row['filename'] ?: ($row['creation_time'] . '.' . $row['format']));
		echo"<br /><strong>Url:</strong> <a href=\"$urlPath\" target=_blank>$urlPath</a> " . $menu;
	}
	echo '</p>';
}

function alertPdoError()
{
	echo '<script>alert(' . str_replace("'",'`',print_r(pdoError(), true)) . ')</script>';
}

function crtQuery($table,$fld) // creating TABLE query
{
	echo '<br/>Creating... ';
	global ${'admin_' . $table};

	if (isset(${'admin_' . $table}->ALT_ID)) {
		$idname = ${'admin_' . $table}->ALT_ID;
	} else {
		$idname = 'id';
	}

	$q = "CREATE TABLE `$table` (
`$idname` INT NOT NULL AUTO_INCREMENT, ";

	$ql = '';

	$i = 0;
	while (isset($fld[$i])) {
		$comment = "COMMENT '" . str_replace("'",'`',$fld[$i]->txt) . "'";

		if ($fld[$i]->multi_lang == 0) {
			if ($fld[$i]->type == 0) {
				$q .= '`' . $fld[$i]->name . "` FLOAT NOT NULL $comment, ";
			}
			if ($fld[$i]->type == 6) {
				$q .= '`' . $fld[$i]->name . "` INT(11) NOT NULL $comment, ";
			}
			if ($fld[$i]->type == 9) {
				$q .= '`' . $fld[$i]->name . "` INT(11) NOT NULL $comment, ";
			}
			if (($fld[$i]->type == 1) || ($fld[$i]->type == 3) || ($fld[$i]->type == 4) || ($fld[$i]->type == 10) || ($fld[$i]->type == 11)) {
				if (($fld[$i]->name == 'under') || ($fld[$i]->name == 'sort')
				|| ($fld[$i]->name == 'creation_time') || ($fld[$i]->name == 'update_time')) {
					$q .= '`' . $fld[$i]->name . "` BIGINT(50) NOT NULL $comment, ";
				} else {
					$q .= '`' . $fld[$i]->name . "` VARCHAR(250) NOT NULL $comment, ";
				}
			}

			if (($fld[$i]->type == 2) || ($fld[$i]->type == 7) || ($fld[$i]->type == 16)) {
				$q .= '`' . $fld[$i]->name . "` TEXT NOT NULL $comment, ";
			}
			if ($fld[$i]->type == 13) {
				$q .= '`' . $fld[$i]->name . "` DATE NOT NULL $comment, ";
			}
		} //Alt table
		else {
			if ($fld[$i]->type == 1) {
				$ql .= '`' . $fld[$i]->name . "` VARCHAR(250) NOT NULL $comment, ";
			} elseif (($fld[$i]->type == 2) || ($fld[$i]->type == 7) || ($fld[$i]->type == 16)) {
				$ql .= '`' . $fld[$i]->name . "` TEXT NOT NULL $comment, ";
			}
		}

		$i++;
	}
	$q .= "PRIMARY KEY (`$idname`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

	echo $q;

	$res = pdoExec($q);
	if ($res === false) {
		alertPdoError();
	} else {
		if (!empty($ql)) {
			echo $ql = 'CREATE TABLE `' . $table . '_info` (
		`record_id` INT NOT NULL, 
		`lang` varchar(3) NOT NULL, 
		' . $ql . ' PRIMARY KEY (`record_id`,`lang`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8';

			pdoExec($ql);

			echo $qfk = 'ALTER TABLE `' . $table . '_info` ADD FOREIGN KEY ( `record_id` ) REFERENCES `' . $table . '` (
		`' . $idname . '`
		) ON DELETE CASCADE ON UPDATE CASCADE';
			$res = pdoExec($qfk);
			if ($res == false) {
				alertPdoError();
			}
		}
	}
	return $res;
}

function AddFields($table,$fld,$row,$id,$n = NULL) //Zapolnyaet formu
{
	global $src,${"admin_$table"},$under,$word,$ALANG,$LANGS,$FOLDER_FILES,$levelOfRub;

	$i = 0;
	$langTabs = [];
	while (isset($fld[$i])) {
		if (isset($levelOfRub) && isset($fld[$i]->extra_param['levelOfRubs']) && $levelOfRub != $fld[$i]->extra_param['levelOfRubs']) {
			$i++;
			continue;
		}

		if (!isset($row[$fld[$i]->name])) {
			$row[$fld[$i]->name] = NULL;
		}
		if ($n !== NULL) {
			$f_dop = "[$n]";
			$f_dopn = "_$n";
		} else {
			$f_dop = '';
			$f_dopn = '';
		}

		if (method_exists(${"admin_$table"},'showed_' . ${"admin_$table"}->fld[$i]->name)) {
			echo ${"admin_$table"}->{'showed_' . ${"admin_$table"}->fld[$i]->name}($row);
		} else {
			if (method_exists(${"admin_$table"},'disableGen') && ${"admin_$table"}->disableGen($fld, $row, $i)) {
				$disabled = 'disabled="disabled"';
			} else {
				$disabled = '';
			}

			if ($fld[$i]->type == 0) {
				$row[$fld[$i]->name] = stripslashes($row[$fld[$i]->name]);
				$row[$fld[$i]->name] = str_replace('"','&#8221;',$row[$fld[$i]->name]);
				echo '
			<div class="form-group">
			<label for="' . $fld[$i]->name . $f_dop . '">' . $fld[$i]->txt . '</label>';

				if ($fld[$i]->table_val == 'NO_EDIT') {
					if (isset($row[$fld[$i]->name])) {
						$itemv = $row[$fld[$i]->name];
					} else {
						$itemv = $_GET[$fld[$i]->name];
					}
					echo $itemv . '<input type="hidden" name="' . $fld[$i]->name . $f_dop . '" value="' . $itemv . '"/>';
				} else {
					echo '<input name="' . $fld[$i]->name . $f_dop . '" id="' . $fld[$i]->name . $f_dop . "\" type=\"text\" $disabled class=\"form-control\" value=\"";

					if (!empty($row[$fld[$i]->name])) {
						echo $row[$fld[$i]->name];
					} elseif (!empty($_GET[$fld[$i]->name])) {
						echo $_GET[$fld[$i]->name];
					}
					echo '">';
				}
				echo '</div>';
			}
			if ($fld[$i]->type == 1) {
				if (strstr(';',$fld[$i]->txt) !== FALSE) {
					$arr = explode(';',$fld[$i]->txt);
					if ($under > 0) {
						$fld[$i]->txt = $arr[1];
					} else {
						$fld[$i]->txt = $arr[0];
					}
				}

				if ($fld[$i]->multi_lang == 1) {
					foreach ($LANGS as $lang => $langName) {
						$input = $fld[$i]->genInput(['row' => $row, 'f_dop' => '_' . $lang, 'name_dop' => $langName, 'disabled' => $disabled]);

						$langTabs[$lang][] = $input;

						if ($lang == 1) {
							echo $input;
						}
					}
				} elseif (strpos($fld[$i]->name, '_' . array_keys($LANGS)[1]) > 0) {
					$langTabs[array_keys($LANGS)[1]][] = $fld[$i]->genInput(['row' => $row, 'disabled' => $disabled]);
				} elseif (strpos($fld[$i]->name, '_' . array_keys($LANGS)[2]) > 0) {
					$langTabs[array_keys($LANGS)[2]][] = $fld[$i]->genInput(['row' => $row, 'disabled' => $disabled]);
				} else {
					echo $fld[$i]->genInput(['row' => $row, 'disabled' => $disabled]);
				}
			}
			if ($fld[$i]->type == 2) {
				if ($fld[$i]->multi_lang == 1) {
					foreach ($LANGS as $lang => $langName) {
						$input = $fld[$i]->genInput(['tableObj' => ${"admin_$table"}, 'row' => $row, 'f_dop' => '_' . $lang, 'name_dop' => $langName, 'disabled' => $disabled]);

						$langTabs[$lang][] = $input;

						if ($lang == 1) {
							echo $input;
						}
					}
				} elseif (strpos($fld[$i]->name, '_' . array_keys($LANGS)[1]) > 0) {
					$langTabs[array_keys($LANGS)[1]][] = $fld[$i]->genInput(['tableObj' => ${"admin_$table"}, 'row' => $row, 'disabled' => $disabled]);
				} elseif (strpos($fld[$i]->name, '_' . array_keys($LANGS)[2]) > 0) {
					$langTabs[array_keys($LANGS)[2]][] = $fld[$i]->genInput(['tableObj' => ${"admin_$table"}, 'row' => $row, 'disabled' => $disabled]);
				} else {
					echo $fld[$i]->genInput(['tableObj' => ${"admin_$table"}, 'row' => $row, 'disabled' => $disabled]);
				}
			}
			if ($fld[$i]->type == 3) {
				//echo "<p>".$fld[$i]->txt."<br>";
				echo '
			<input name="' . $fld[$i]->name . $f_dop . '" type="hidden" class="window" value="';
				if (!empty($row[$fld[$i]->name])) {
					echo $row[$fld[$i]->name];
				} else if (!empty($_GET[$fld[$i]->name])) {
					echo $_GET[$fld[$i]->name];
				}
				echo '">';
			}
			if ($fld[$i]->type == 5) { //Vse, 4to ugodno
				echo '<div class="form-group"><label>' . $fld[$i]->txt . '</label>';
				//echo $fld[$i]->name;
				echo '</div>';
			}
			if ($fld[$i]->type == 6) {
				echo '<div class="checkbox">' . $fld[$i]->txt . ' ';
				if (isset($row[$fld[$i]->name]) && $row[$fld[$i]->name] == 1) {
					$chk = 'checked=checked';
				} else {
					$chk = '';
				}
				echo '<label style="margin-bottom:-5px;" for="' . $fld[$i]->name . '">' . '<input name="' . $fld[$i]->name . "_control\" class=\"checkbox\" $disabled type=\"checkbox\" id=\"" . $fld[$i]->name . "_control\" onClick=\"applyCheckBoxStatus('" . $fld[$i]->name . "')\" value=\"1\" $chk>";
				echo '<input name="' . $fld[$i]->name . $f_dop . '" id="' . $fld[$i]->name . $f_dop . '"  type="hidden" class="window" value="';
				if (!empty($row[$fld[$i]->name])) {
					echo $row[$fld[$i]->name];
				} else {
					echo '0';
				}
				echo '">';

				echo '</label>';
				echo '</div>';
			}
			if ($fld[$i]->type == 7) {
				$row[$fld[$i]->name] = stripslashes($row[$fld[$i]->name]);
				echo '<div class="form-group"><label for="' . $fld[$i]->name . $f_dop . '">' . $fld[$i]->txt . '</label> ';
				echo '<textarea class="form-control" name="' . $fld[$i]->name . $f_dop . '" id="' . $fld[$i]->name . $f_dop . "\"  $disabled cols=\"70\" rows=\"" . (!empty(${"admin_$table"}->TEXTARR)?${"admin_$table"}->TEXTARR:3) . '">
';
				if ($id && !isset(${"admin_$table"}->TEXTARR_NO_BR)) {
					echo str_ireplace(['<br />', '<br/>', '<br>'], "\n", $row[$fld[$i]->name]);
				} elseif ($id) {
					echo $row[$fld[$i]->name];
				}
				echo '</textarea>
			</div>';
			}
			if ($fld[$i]->type == 8) {
				if ($div_opened == 1) {
					echo '</div>';
					$div_opened = 0;
				}
				if ($fld[$i]->txt != '') {
					echo '<hr size=1 /><h3 style="background-color:#F9F9F9; cursor: pointer;" onclick="popUp(\'' . $fld[$i]->name . '\')">' . $fld[$i]->txt . '</h3>';
					echo '<div style="background-color:#F9F9F9; display:none;" id="' . $fld[$i]->name . '">';
					$div_opened = 1;
				} else {
					echo '<hr size=1 />';
				}
			}
			/*Выпадашка вариантов*/
			if ($fld[$i]->type == 9) {
				echo '<div class="form-group"><label for="' . $fld[$i]->name . '">' . $fld[$i]->txt . '</label>';

				if (isset($fld[$i]->extra_param['NO_EDIT']) && $fld[$i]->extra_param['NO_EDIT'] == 1) {
					if (!empty($row[$fld[$i]->name])) {
						$val = pdoFetchAll('SELECT ' . $fld[$i]->table_field . ' FROM ' . $fld[$i]->table_val . " WHERE id = '" . $row[$fld[$i]->name] . "'");
						$itemv = $val[0][$fld[$i]->table_field];
					} else {
						$itemv = 'not set';
					}
					echo $itemv . '<input type="hidden" name="' . $fld[$i]->name . '" value="' . $row[$fld[$i]->name] . '"/>';
				} else {
					echo Gen_Select($fld[$i]->table_val,$fld[$i]->table_under,$fld[$i]->name,$row[$fld[$i]->name],$fld[$i], $disabled);
				}
				echo '</div>';
			}
			// Перечень статических вариантов
			if ($fld[$i]->type == 10) {
				echo '<div class="form-group"><label for="' . $fld[$i]->name . '">' . $fld[$i]->txt . '</label>';

				echo Gen_SelectStatic($fld[$i],$row[$fld[$i]->name]);

				echo '</div>';
			}

			/*Динамический файл*/
			if ($fld[$i]->type == 11) {
				$input = $fld[$i]->genInput(['row' => $row, 'f_dop' => '', 'name_dop' => '']);
				if (!empty($fld[$i]->extra_param['tab'])) {
					$langTabs[$fld[$i]->extra_param['tab']][] = $input;
				} else {
					echo $input;
				}
			}

			/*Выпадашка итемов*/
			if ($fld[$i]->type == 12) {
				echo '<p class="txt"><strong>' . $fld[$i]->txt . '</strong><br> ' . Gen_Select_Val($fld[$i]->table_val,$fld[$i]->table_under,$fld[$i]->name,$row[$fld[$i]->name]) . '</p>';
			}

			/*DATE*/
			if ($fld[$i]->type == 13) {
				$row[$fld[$i]->name] = (isset($row[$fld[$i]->name])?stripslashes($row[$fld[$i]->name]):'');
				$row[$fld[$i]->name] = str_replace('"','&#8221;',$row[$fld[$i]->name]);

				echo '<div class="form-group"><label for="' . $fld[$i]->name . '">' . $fld[$i]->txt . '</label><br/>';

				if ($fld[$i]->table_val == 'NO_EDIT') {
					if (isset($row[$fld[$i]->name])) {
						$itemv = $row[$fld[$i]->name];
					} else {
						$itemv = $_GET[$fld[$i]->name];
					}
					echo $itemv . '<input type="hidden" name="' . $fld[$i]->name . '" value="' . $itemv . '"/>';
				} else {
					echo '<script type="text/javascript">
			$(function(){
				// Datepicker
				$(\'#' . $fld[$i]->name . '\').datepicker({
					format: \'yyyy-mm-dd\'
				});
			});
		</script>';
					echo '<input name="' . $fld[$i]->name . '" id="' . $fld[$i]->name . "\" $disabled type=\"text\" size=\"25\" class=\"window\" value=\"";
					if (isset($row[$fld[$i]->name])) {
						echo $row[$fld[$i]->name];
					} else {
						echo $_GET[$fld[$i]->name];
					}
					echo '">';
				}
				echo '</div>';
			}
			//COlOR
			if ($fld[$i]->type == 15) {
				$row[$fld[$i]->name] = (isset($row[$fld[$i]->name])?stripslashes($row[$fld[$i]->name]):'');
				$row[$fld[$i]->name] = str_replace('"','&#8221;',$row[$fld[$i]->name]);
				if (strpos($fld[$i]->txt, ';') !== false) {
					$arr = explode(';',$fld[$i]->txt);
					if ($under > 0) {
						$fld[$i]->txt = $arr[1];
					} else {
						$fld[$i]->txt = $arr[0];
					}
				}
				echo '<div class="form-group"><label for="' . $fld[$i]->name . '">' . $fld[$i]->txt . '</label><br/>';

				echo '
					<div id="' . $fld[$i]->name . $f_dopn . '" class="input-group colorpicker-component">
					<span class="input-group-addon"><i></i></span> 
					<input name="' . $fld[$i]->name . $f_dop . '" type="text" class="form-control" value="';
				if (isset($row[$fld[$i]->name])) {
					echo $row[$fld[$i]->name];
				} else {
					echo $_GET[$fld[$i]->name];
				}
				echo '">

				 </div>';

				echo '<script type="text/javascript">
				$(function() { $(\'#' . $fld[$i]->name . $f_dopn . '\').colorpicker(); }); 
            </script>
			';
				echo '</div>';
			}
			//RAW TEXT
			else if ($fld[$i]->type == 16) {
				if ($fld[$i]->multi_lang == 1) {
					foreach ($LANGS as $lang => $langName) {
						$input = $fld[$i]->genInput(['row' => $row, 'f_dop' => '_' . $lang, 'name_dop' => $langName]);

						$langTabs[$lang][] = $input;

						if ($lang == 1) {
							echo $input;
						}
					}
				} else {
					$input = $fld[$i]->genInput(['row' => $row, 'f_dop' => '', 'name_dop' => '']);
					if (!empty($fld[$i]->extra_param['tab'])) {
						$langTabs[$fld[$i]->extra_param['tab']][] = $input;
					} else {
						echo $input;
					}
					/*$row[$fld[$i]->name]=stripslashes($row[$fld[$i]->name]);
					echo '<div class="form-group"><label for="'.$fld[$i]->name.'">'.$fld[$i]->txt.'</label><br/>';

					echo "<textarea name=\"".$fld[$i]->name.$f_dop."\"  cols=\"70\" rows=\"".((!empty(${"admin_$table"}->TEXTARR))?${"admin_$table"}->TEXTARR:3)."\">
	";
					echo $row[$fld[$i]->name];
					echo '</textarea></div>';*/
				}
			}
			//echo 'show_'.${"admin_$table"}->fld[$i]->name;
			if (method_exists(${"admin_$table"},'show_' . ${"admin_$table"}->fld[$i]->name)) {
				echo ${"admin_$table"}->{'show_' . ${"admin_$table"}->fld[$i]->name}($row);
			}
		}
		$i++;
	}
	return $langTabs;
}

function printDynamicFields($table, $fields, $id, $domainId)
{
	$lang = 1;

	foreach ($fields as $fld) {
		// Значения полей
		if ($id > 0) {
			$rows = pdoFetchAll('SELECT * FROM domains_texts 
                                    WHERE domain_id = ' . $domainId . " AND entity = '" . $table . "' AND record_id = '" . $id . "' 
                                    AND alias = '" . $fld->alias . "' AND language_id = " . $lang);
			if (count($rows) > 0) {
				$rowf = $rows[0];
				//echo '||' . mb_detect_encoding($row['text']) . '=' . $row['text'] . '||';
				$rowf['text'] = stripslashes($rowf['text']);
				unset($rows);
			} else {
				$rowf['text'] = '';
			}
		} else {
			$rowf['text'] = '';
		}

		echo '<span class=txt><b>' . $fld->label . '</b></span> ';
		if (!empty($fld->extra_param['forEntity'])) {
			$whUnder = '';
			if (!empty($fld->extra_param['forEntityFields'])) {
				foreach ($fld->extra_param['forEntityFields'] as $key => $fieldData) {
					$whUnder .= ' AND `' . $fieldData['field'] . '`=\'' . $fieldData['value'] . '\' ';
				}
			}

			// <23092014> get certain records via table relation
			if (!empty($fld->extra_param['forEntityTableRelation']) && ($id > 0)) {
				$whUnder .= ' AND id IN (
									SELECT DISTINCT `' . $fld->extra_param['forEntityTableRelationField'] . '`
									FROM ' . $fld->extra_param['forEntityTableRelation'] . '
									WHERE `' . $fld->extra_param['forEntityTableRelationFieldBy'] . '` = "' . $id . '"
								) ';
			}
			// </23092014>

			$q = 'SELECT * FROM ' . $fld->extra_param['forEntity'] . ' WHERE 1 ' . $whUnder . ' ORDER by id ASC';
			$forItems = pdoFetchAll($q);

			if (count($forItems) > 0) {
				echo '<strong>/ <a onclick="popUp(\'forEntity_' . $fld->extra_param['forEntity'] . '_' . $fld->alias . '_' . $domainId . '\')">Показать для ' . $fld->extra_param['forEntity'] . '</a></strong>

                <div id="forEntity_' . $fld->extra_param['forEntity'] . '_' . $fld->alias . '_' . $domainId . '" style="border:1px dashed #CCC;display:none">';
				foreach ($forItems as $forItem) {
					// Значения полей
					if ($id > 0) {
						$rows = pdoFetchAll('SELECT * FROM domains_entity_texts 
                                            WHERE domain_id = ' . $domainId . " AND entity = '" . $table . "' AND record_id = '" . $id . "' 
                                            AND alias = '" . $fld->alias . "' AND for_entity = '" . $fld->extra_param['forEntity'] . "' AND for_entity_id = " . $forItem['id']);
						if (count($rows) > 0) {
							$row = $rows[0];
							//echo '||' . mb_detect_encoding($row['text']) . '=' . $row['text'] . '||';
							$row['text'] = stripslashes($row['text']);
							unset($rows);
						} else {
							$row['text'] = '';
						}
					} else {
						$row['text'] = '';
					}

					$inputName = $fld->alias . '_' . $domainId . '_' . $lang . '_' . $fld->extra_param['forEntity'] . '_' . $forItem['id'];

					echo '<span class=txt><b>' . $forItem['name_' . mainLang()] . '</b> ' . $fld->label . '</span><br>';

					if ($fld->type == 1) {
						echo '<input name="' . $inputName . '" id="' . $inputName . '" type="text" value="' . $row['text'] . '" />';
					} elseif ($fld->type == 2) {
						$size = 'cols="70" rows="5"';
						echo '<textarea name="' . $inputName . '" id="' . $inputName . "\"  wrap=\"VIRTUAL\" $size>
";
						echo $row['text'];

						echo '</textarea><script>
				var ed' . $inputName . " = CKEDITOR.replace('" . $inputName . "', {
					filebrowserImageUploadUrl: '/admin/ajax/ckupload.php?type=Images',
					filebrowserUploadUrl:      '/admin/ajax/ckupload.php?type=Files'
				});
                </script><br>";
					}
				}
				echo '<span class=txt><b>' . $fld->label . '</b></span> ';
				echo '</div>';
			} // itemsCount
		}

		echo '<br/>';

		$inputName = $fld->alias . '_' . $domainId . '_' . $lang;

		if ($fld->type == 1) {
			$size = 'size="100"';

			echo '<input type="text" name="' . $inputName . '" id="' . $inputName . "\"  $size value=\"" . $rowf['text'] . '" />
            <br>';
		} elseif ($fld->type == 2) {
			$size = 'cols="70" rows="5"';

			echo '<textarea name="' . $inputName . '" id="' . $inputName . "\"  wrap=\"VIRTUAL\" $size>
";
			echo $rowf['text'];

			echo '</textarea><script>
			var ed' . $inputName . " = CKEDITOR.replace('" . $inputName . "', {
				filebrowserImageUploadUrl: '/admin/ajax/ckupload.php?type=Images',
				filebrowserUploadUrl:      '/admin/ajax/ckupload.php?type=Files'
			});
			</script><br>";
		}
	} // end foreach fields
}

function saveTags($table, $id)
{
	// global ${'admin_'.$table};
	pdoExec("DELETE FROM tags_assoc 
                                    WHERE table_name = '" . $table . "' 
                                    AND record_id = '" . $id . "'");
	if (!empty($_POST['tags_values'])) {
		foreach ($_POST['tags_values'] as $value) {
			//echo 'insert! ';
			if (!empty($value)) {
				$qi = "INSERT INTO tags_assoc SET
                                        table_name = '" . $table . "' , record_id = '" . $id . "', tag_id = " . $value;
				pdoExec($qi);
			}
		}
	}
}
function saveDomainsTexts($table, $id)
{
	global ${'admin_' . $table};

	$siteDomains = pdoFetchAll('SELECT * FROM domains ORDER by id ASC');
	$lang = 1;
	$now = date('U');
	foreach ($siteDomains as $domain) {
		pdoExec('DELETE FROM domains_texts 
                                    WHERE domain_id = ' . $domain['id'] . " AND entity = '" . $table . "' 
                                    AND record_id = '" . $id . "'");

		foreach (${'admin_' . $table}->dynamicFields as $fld) {
			$inputName = $fld->alias . '_' . $domain['id'] . '_' . $lang;
			$value = addslashes($_POST[$inputName]);

			$encodingTest = mb_detect_encoding($_POST[$inputName]);
			//echo '|' . $encodingTest . '=' . $_POST[$inputName] . '|';
			//if($encodingTest == 'ASCII') {
			//$value = mb_convert_encoding($value, 'UTF-8');
			//$value = iconv('ASCII', 'UTF-8', $value);
			//}

			if (!empty($value)) {
				pdoExec('INSERT INTO domains_texts SET
                                        domain_id = ' . $domain['id'] . ", entity = '" . $table . "' , record_id = '" . $id . "', 
                                        alias = '" . $fld->alias . "', language_id = " . $lang . ", text = '" . $value . "', updated=" . $now);
			}

			if (!empty($fld->extra_param['forEntity'])) {
				pdoExec('DELETE FROM domains_entity_texts 
                                    WHERE domain_id = ' . $domain['id'] . " AND entity = '" . $table . "' AND alias = '" . $fld->alias . "' 
                                    AND for_entity='" . $fld->extra_param['forEntity'] . "' AND record_id = '" . $id . "'");

				$whUnder = '';
				if (!empty($fld->extra_param['forEntityFields'])) {
					foreach ($fld->extra_param['forEntityFields'] as $key => $fieldData) {
						$whUnder .= ' AND `' . $fieldData['field'] . '`=\'' . $fieldData['value'] . '\' ';
					}
				}

				// <25092014> get certain records via table relation
				if (!empty($fld->extra_param['forEntityTableRelation']) && ($id > 0)) {
					$whUnder .= ' AND id IN (
										SELECT DISTINCT `' . $fld->extra_param['forEntityTableRelationField'] . '`
										FROM ' . $fld->extra_param['forEntityTableRelation'] . '
										WHERE `' . $fld->extra_param['forEntityTableRelationFieldBy'] . '` = "' . $id . '"
									) ';
				}
				// </25092014>

				$q = 'SELECT * FROM ' . $fld->extra_param['forEntity'] . ' WHERE 1 ' . $whUnder . ' ORDER by id ASC';
				$forItems = pdoFetchAll($q);

				foreach ($forItems as $forItem) {
					$inputName = $fld->alias . '_' . $domain['id'] . '_' . $lang . '_' . $fld->extra_param['forEntity'] . '_' . $forItem['id'];

					$value = addslashes($_POST[$inputName]);

					if (!empty($value)) {
						$qi = 'INSERT INTO domains_entity_texts SET
                                        domain_id = ' . $domain['id'] . ", entity = '" . $table . "' , record_id = '" . $id . "', 
                                        alias = '" . $fld->alias . "', for_entity = '" . $fld->extra_param['forEntity'] . "',
                                         for_entity_id = " . $forItem['id'] . ", text = '" . $value . "', updated=" . $now;

						/* if ($fld->type == 1)
							echo $qi.'<br/>';
						*/
						pdoExec($qi);
					}
				}
			}
		}
	}
}

function addingQuery($table,$fld) //generates ADDING QUERY
{
	global ${'admin_' . $table};
	$query = "INSERT INTO `$table` (";
	$i = 0;

	$query .= '`' . $fld[$i]->name . '`';

	$i++;
	while (isset($fld[$i])) {
		if (($fld[$i]->type) != 5 && ($fld[$i]->type) != 8 && ($fld[$i]->type) != 14) {
			$query .= ',`' . $fld[$i]->name . '`';
		}
		$i++;
	}

	$query .= ') VALUES (';
	$i = 0;

	if (($fld[$i]->type) == 1) {
		$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);
	}

	$escapedVal = pdoEscape($fld[$i]->val);
	// Remove the quotes added by pdoEscape as we'll add them in the query
	$escapedVal = substr($escapedVal, 1, -1);
	$query .= "'" . $escapedVal . "'";
	$i++;

	while (isset($fld[$i])) {
		if (($fld[$i]->type) == 0) {
			$fld[$i]->val = str_replace(',','.',$fld[$i]->val);
		}
		if (($fld[$i]->type) == 1) {
			$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);
		}

		if (($fld[$i]->type) == 7 && !isset(${'admin_' . $table}->TEXTARR_NO_BR)) {
			$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);
			$fld[$i]->val = str_ireplace("\n",'<br/>',$fld[$i]->val);
		}

		if (($fld[$i]->type) == 2) {
			$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);

			//$fld[$i]->val=preg_replace("/style=\"(.*)\"/U","",$fld[$i]->val);

			$fld[$i]->val = preg_replace('/class="(.*)"/U','',$fld[$i]->val);
			$fld[$i]->val = preg_replace('/lang="(.*)"/U','',$fld[$i]->val);
			$fld[$i]->val = preg_replace('/<!--(.*)-->/U','',$fld[$i]->val);
			$fld[$i]->val = str_ireplace("\<\/o:p\>",'',$fld[$i]->val);
			$fld[$i]->val = str_ireplace("\<o:p\>",'',$fld[$i]->val);
		}

		if (($fld[$i]->type) != 5 && ($fld[$i]->type) != 8 && ($fld[$i]->type) != 14) {
			$escapedVal = pdoEscape($fld[$i]->val);
			// Remove the quotes added by pdoEscape as we'll add them in the query
			$escapedVal = substr($escapedVal, 1, -1);
			$query .= ",'" . $escapedVal . "'";
		}
		$i++;
	}

	$query .= ');';
	return $query;
}

function updQuery($table,$fld,$id) //generates UPD QUERY "UPDATE `$table` SET `html`='$html', `zag` = '$zag', `an`='$an', `txt`='$txt' WHERE id='$id'";
{
	global ${'admin_' . $table};

	if (isset(${'admin_' . $table}->ALT_ID)) {
		$idname = ${'admin_' . $table}->ALT_ID;
	} else {
		$idname = 'id';
	}

	$query = "UPDATE `$table` SET ";
	$i = 0;

	// if (($fld[$i]->type)==1) $fld[$i]->val=str_replace(array("'", '&quot;'), array('`', '"'), $fld[$i]->val);

	//echo $query.="`".$fld[$i]->name."`='".$fld[$i]->val."'";
	$i++;
	while (isset($fld[$i])) {
		// skips external fields
		if ($fld[$i]->multi_lang == 1 || (empty($fld[$i]->val) && !isset($_POST[$fld[$i]->name]))) {
			$i++;
			continue;
		}

		if (($fld[$i]->type) == 0) {
			$fld[$i]->val = str_replace(',','.',$fld[$i]->val);
		}
		//if (($fld[$i]->type)==1) $fld[$i]->val=str_replace(array("'",'&quot;'),'"',$fld[$i]->val);
		if (($fld[$i]->type) == 2) {
			$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);
			//$fld[$i]->val=preg_replace("/style=\"(.*)\"/U","",$fld[$i]->val);
			//$fld[$i]->val=preg_replace("/class=\"(.*)\"/U","",$fld[$i]->val);
			$fld[$i]->val = preg_replace('/lang="(.*)"/U','',$fld[$i]->val);
			$fld[$i]->val = preg_replace('/<!--(.*)-->/U','',$fld[$i]->val);
			$fld[$i]->val = str_ireplace("\<\/o:p\>",'',$fld[$i]->val);
			$fld[$i]->val = str_ireplace("\<o:p\>",'',$fld[$i]->val);
		}
		if (($fld[$i]->type) == 7 && !isset(${'admin_' . $table}->TEXTARR_NO_BR)) {
			$fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $fld[$i]->val);
			$fld[$i]->val = str_ireplace("\n",'<br/>',$fld[$i]->val);
		}

		if ($fld[$i]->name == 'update_time') {
			$fld[$i]->val = date('U');
		}

		if (($fld[$i]->type) != 5 && ($fld[$i]->type) != 8 && ($fld[$i]->type) != 14) {
			$escapedVal = pdoEscape($fld[$i]->val);
			// Remove the quotes added by pdoEscape as we'll add them in the query
			$escapedVal = substr($escapedVal, 1, -1);
			$query .= ',`' . $fld[$i]->name . "`='" . $escapedVal . "'";
		}
		$i++;
	}
	$query .= " WHERE $idname='$id'";
	return $query;
}

function delrow($table, $id) // del
{
	global ${'admin_' . $table},$pref,$FOLDER_IMAGES,$FOLDER_FILES;

	if (method_exists(${"admin_$table"},'onDelete')) {
		${"admin_$table"}->onDelete($id);
	}

	if (isset(${'admin_' . $table}->ALT_ID)) {
		$idname = ${'admin_' . $table}->ALT_ID;
	} else {
		$idname = 'id';
	}

	// Getting info about deleting object
	$query2 = "SELECT * FROM $table WHERE `$idname`='$id'";
	$result2 = mQuery($query2);
	$row2 = mFetchArray($result2);

	// Deleting object
	$query = "DELETE FROM $table WHERE $idname='$id'";
	$result = mQuery($query);

	if (!isset(${"admin_$table"}->IMG_FIELD)) {
		$img_field = 'id';
	} else {
		$img_field = ${"admin_$table"}->IMG_FIELD;
	}

	//Delete images
	if (!empty($row2[$img_field]) || !empty($row2['creation_time'])) {
		$folder = GetImageFolder($table,$row2[$img_field]);

		@unlink("$pref$FOLDER_FILES/preview/$row2[creation_time].jpg");

		if (!empty($row2['filename']) || !empty($row2['format'])) {
			@unlink("$pref$FOLDER_FILES/$row2[format]/$row2[filename]");
			@unlink("$pref$FOLDER_FILES/$row2[format]/$row2[creation_time].$row2[format]");

			MaFD(["$row2[filename]", "$row2[creation_time].$row2[format]"],'files');
		}

		for ($i = 1; $i <= 10; $i++) {
			@unlink("$pref$FOLDER_IMAGES/$folder$table.$row2[$img_field].$i.s.jpg");
			@unlink("$pref$FOLDER_IMAGES/$folder$row2[$img_field].$i.s.jpg");
			@unlink("$pref$FOLDER_IMAGES/$folder$table.$row2[$img_field].$i.b.jpg");
			//echo "$pref$FOLDER_IMAGES/$folder.$row2[$img_field].$i.b.jpg<br/>";
			@unlink("$pref$FOLDER_IMAGES/$folder$row2[$img_field].$i.b.jpg");
			MaFD(["$folder$table.$row2[$img_field].$i.s.jpg", "$folder$table.$row2[$img_field].$i.b.jpg"]);
		}
	}
	return $result;
}

/*function delunder($table, $id) // del under new
{
	$query2="SELECT * FROM $table WHERE `under`='$id'";
	$result2 = mQuery($query2);
	$num2=mNumRows($result2);

	for ($i=0; $i<$num2;$i++) {
		$row2=mFetchArray($result2);
		$query = "DELETE FROM $table WHERE id='$id'";
		$result = mQuery($query);
		if ($row2[creation_time]) { @unlink("$pref$FOLDER_IMAGES/$table.$row2[creation_time].1.s.jpg"); @unlink("$pref$FOLDER_IMAGES/$table.$row2[creation_time].1.b.jpg"); }
	}

	return $result;
}

function delunder2($table, $under) // udalayet vse, 4to pod
{
$query = "SELECT * FROM $table WHERE under='$under'";
$result = mQuery($query);
$num=mNumRows($result);
for ($i=0; $i<$num;$i++) {
$row=mFetchArray($result);
if ($row[creation_time]) { @unlink("$pref$FOLDER_IMAGES/$table.$row2[creation_time].1.s.jpg"); @unlink("$pref$FOLDER_IMAGES/$table.$row2[creation_time].1.b.jpg"); }
}
$query = "DELETE FROM $table WHERE under='$under'";
$result = mQuery($query);
return $result;
}*/

//rekurs del rub&items
function DelUni($tabler, $tablei = '', $rub = 0)
{
	//echo "$tabler,$tablei,$rub";
	global $pref,$FOLDER_IMAGES, ${'admin_' . $tabler}, ${'admin_' . $tablei};

	//Parent rub ~ under
	if (!empty($tabler) && isset(${'admin_' . $tabler}->FIELD_UNDER)) {
		$under_name = ${'admin_' . $tabler}->FIELD_UNDER;
	} else {
		$under_name = 'under';
	}

	$query = "SELECT * FROM $tabler WHERE `" . $under_name . "` = '$rub'";
	$result = mQuery($query);
	$num = mNumRows($result);

	// Нету подрубрик - Удаляем товары
	if ($num == 0 && $tablei != '') {
		if (isset(${'admin_' . $tablei}->FIELD_UNDER)) {
			$under_item_name = ${'admin_' . $tablei}->FIELD_UNDER;
		} else {
			$under_item_name = 'under';
		}

		$query = "SELECT * FROM $tablei WHERE `" . $under_item_name . "` = '$rub'";
		$result = mQuery($query);
		$num = mNumRows($result);
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchArray($result);
			mQuery("DELETE FROM $tablei WHERE id=$row[id]");

			if (isset(${'admin_' . $tablei}->IMG_FIELD)) {
				$imgId = ${'admin_' . $tablei}->IMG_FIELD;
			} else {
				$imgId = 'id';
			}

			if (!empty($row[$imgId])) {
				$folder = GetImageFolder($tablei,$row[$imgId]);

				for ($im = 1; $im <= 10; $im++) {
					@unlink("$pref$FOLDER_IMAGES/$folder$tablei." . $row[$imgId] . ".$im.s0.jpg");
					@unlink("$pref$FOLDER_IMAGES/$folder$tablei." . $row[$imgId] . ".$im.s.jpg");
					@unlink("$pref$FOLDER_IMAGES/$folder$tablei." . $row[$imgId] . ".$im.b.jpg");
					@unlink("$pref$FOLDER_IMAGES/$folder$tablei." . $row[$imgId] . ".$im.orig.jpg");
					@unlink("$pref$FOLDER_USERFILES/$row[creation_time].$row[format]");
					//MaFD(array("$tablei.$row[creation_time].$im.s0.jpg","$tablei.$row[creation_time].$im.s.jpg","$tablei.$row[creation_time].$im.b.jpg","$tablei.$row[creation_time].$im.orig.jpg"));
					//if ($row[format]!="")  MaFD("$row[creation_time].$row[format]","files");
				}
			}
		}
	}
	// Есть подрубрики
	else {
		// Перебор подрубрик
		for ($i = 0; $i < $num; $i++) {
			$rowSub = mFetchArray($result);

			// Рекурсивный вызов для удаления подрубрик и товаров в них
			DelUni($tabler,$tablei,$rowSub['id']);
		}
	}

	// Удаляем саму рубрику
	$query = "SELECT * FROM $tabler WHERE `id`='$rub'";
	$result = mQuery($query);
	$rowr = mFetchArray($result);

	if (isset(${'admin_' . $tabler}->IMG_FIELD)) {
		$imgId = ${'admin_' . $tabler}->IMG_FIELD;
	} else {
		$imgId = 'id';
	}

	// $rowr may be null if the parent rub was already gone; skip image cleanup.
	if (is_array($rowr) && isset($rowr[$imgId])) {
		$folder = GetImageFolder($tabler, $rowr[$imgId]);

		@unlink("$pref$FOLDER_IMAGES/$folder$tabler." . $rowr[$imgId] . '.1.s.jpg');
		@unlink("$pref$FOLDER_IMAGES/$folder$tabler." . $rowr[$imgId] . '.1.b.jpg');
	}

	return mQuery("DELETE FROM $tabler WHERE id='$rub'");
}

function delfile($name) // del file
{
	global $pref,$FOLDER_IMAGES,$FOLDER_FILES;

	if (strstr($name,'images') !== false) {
		MaFD(str_replace("$pref$FOLDER_IMAGES/",'',$name));
	}

	if (strstr($name,'files') !== false) {
		MaFD(str_replace("$pref$FOLDER_FILES/",'',$name),'files');
	}

	return @unlink($name);
}

function getPosInFld($name,$fld)
{
	$i = 0;
	while (isset($fld[$i]->name)) {
		if ($fld[$i]->name == $name) {
			return $i;
		}
		$i++;
	}
	return NULL;
}

//------------------LIST ITEMS---------
function Gen_SelectJ($tablei,$table,$under,$varname,$itemv,$rowid,$fld = NULL) // Gen select ~ $under_name...
{
	$cfn = 'admin_' . $table;
	${"admin_$table"} = new $cfn();

	if ($fld->table_field != '') {
		$fname = $fld->table_field;
	} else {
		$fname = 'name_' . mainLang();
	}

	if (isset(${'admin_' . $table}->FIELD_UNDER)) {
		$under_name = ${'admin_' . $table}->FIELD_UNDER;
	} else {
		$under_name = 'under';
	}

	$sbody = '';
	if ($under) {
		$wh = "WHERE $under_name='$under'";
	} else {
		$wh = '';
	}
	$query = "SELECT * FROM $table $wh ORDER BY " . str_replace('%20',' ',${"admin_$table"}->SORT);
	//echo $query;
	$result = mQuery($query);
	$num = mNumRows($result);
	//echo $num;
	for ($i = 0; $i < $num; $i++) {
		$row = mFetchArray($result);
		$sel = '';
		//if ($varname=='currency_opl') echo '<br>'.$itemv.'=='.$row[id].' !';
		if ($itemv == $row['id']) {
			$sel = 'selected';
		}
		$sbody .= "<option value=\"$row[id]\" $sel>$row[$fname]</option>";
	}
	return '<SELECT name="' . $varname . $row['id'] . '" id="' . $varname . $rowid . "\" onChange=\"updateItemSel('" . $tablei . "','" . $varname . "','" . $varname . $rowid . "','" . $rowid . "');\" style=\"font-size:12px;border:0px;\"><option value=-1>...</option>" . $sbody . '</SELECT>';
}

function genLangJoin($table, $obj = null)
{
	global $LANGS;

	if (!$obj) {
		global ${"admin_$table"};
		$obj = ${"admin_$table"};
		if (empty($obj)) {
			$fcn = "admin_$table";
			$obj = new $fcn();
		}
	}

	if (!empty($obj->MULTI_LANG)) {
		$langJoin = 'INNER JOIN ' . $table . '_info ON (' . $table . '_info.record_id = ' . $table . '.id AND ' . $table . "_info.lang = '" . array_keys($LANGS)[0] . "')";
	} else {
		//echo 'NO langs!';
		$langJoin = '';
	}

	return $langJoin;
}

// --- vivodit items
function listtable($act, $table, $tabler, $tablei, $under, $sort, $ipage, $echoid = 'id', $noedit = FALSE)
{
	global ${"admin_$table"},${"admin_$tabler"},${"admin_$tablei"},$src,$srci,$word,$ALANG,$FOLDER_IMAGES,$FOLDER_IMAGES_FRONTEND,$FOLDER_FILES,$NO_ADMIN,$_SERVER,$_GET,$RIGHTS,$RUBS_LIST_TYPE;
	if ($table == $tablei) {
		$type = 1;
	} //ITEMS
	else {
		$type = 2;
	} //RUBS

	if ($type == 1 && isset($_GET['sort_items']) && $_GET['sort_items'] != '') {
		$sort = $_GET['sort_items'];
	}

	if ($NO_ADMIN == 1) {
		$pref = '';
	} else {
		$pref = '../';
	}

	//if (isset(${"admin_$table"}->IMG_TYPE)) $img_type=${"admin_$table"}->IMG_TYPE;
	//else
	// Small images always jpg
	$img_type = 'jpg';
	//echo $_SERVER['REMOTE_USER'];

	$sortpos = null;

	if ($type == 1 && $tabler != '' && $under > 0) {
		$rowrub = FetchID($tabler,$under);
	}

	/*Выборка сложных полей*/
	$f = 1;
	$spf = '';
	while (isset(${"admin_$table"}->fld[$f]->name)) {
		if (${"admin_$table"}->fld[$f]->type == 14) {
			$spf .= ',(' . ${"admin_$table"}->fld[$f]->extra_param['subQuery'] . ') as ' . ${"admin_$table"}->fld[$f]->name;
		}
		$f++;
	}
	/**/

	if (isset(${'admin_' . $table}->FIELD_ID)) {
		$idname = ${'admin_' . $table}->FIELD_ID;
	} else {
		$idname = 'id';
	}

	if (isset(${'admin_' . $table}->FIELD_UNDER)) {
		$under_name = ${'admin_' . $table}->FIELD_UNDER;
	} else {
		$under_name = 'under';
	}

	if (isset(${'admin_' . $tablei}->FIELD_UNDER)) {
		$under_item_name = ${'admin_' . $tablei}->FIELD_UNDER;
	} else {
		$under_item_name = 'under';
	}

	if (isset(${"admin_$table"}->ECHO_NAME)) {
		$echo_item_name = ${"admin_$table"}->ECHO_NAME;
	} else {
		$echo_item_name = 'name_1';
	}

	/*Если СОРТ по порядку 1,2,3*/
	if (isset(${"admin_$table"}->SORT) && strstr(strtolower(${"admin_$table"}->SORT),'asc')) {
		$sort_set = 'asc';
	} else {
		$sort_set = 'desc';
	}

	$sortpos = getPosInFld('sort',${"admin_$table"}->fld);
	if ($tabler != '' || isset(${"admin_$table"}->FIELD_UNDER)) {
		$under_pos = getPosInFld($under_name,${"admin_$table"}->fld);
	} else {
		$under_pos = 0;
	}

	// Перед сводной таблицей выводим дополнительный блок
	if (method_exists(${"admin_$table"},'preTable')) {
		echo ${"admin_$table"}->preTable();
	}

	$tdbg = ''; //"bgcolor=\"#c6d1de\"";

	if (isset(${"admin_$table"}->SEARCH_FIELD)) {
		$search_field = ${"admin_$table"}->SEARCH_FIELD;
	} else {
		$search_field = $echo_item_name;
	}
	if (isset(${"admin_$table"}->SHOW_NUM)) {
		$shownum = ${"admin_$table"}->SHOW_NUM;
	} else {
		$shownum = 25;
	}

	if (!($ipage > 0)) {
		$ipage = 0;
	}
	$starts = $ipage * $shownum;

	$_tables = "tabler=$tabler&tablei=$tablei";

	if (!empty($RUBS_LIST_TYPE) && $RUBS_LIST_TYPE !== 'tree') {
		$_tables .= '&rubs_list_type=' . $RUBS_LIST_TYPE;
	}

	$langJoin = genLangJoin($table);

	// Если 1-уровневый каталог рубрик без андера
	if (isset(${"admin_$table"}->FIELD_UNDER) && ${"admin_$table"}->FIELD_UNDER == '') {
		$wh_under = '';
	}
	// Если выводим все списком с фильтром рубрик через селект
	elseif (isset($RUBS_LIST_TYPE) && $RUBS_LIST_TYPE == 'select' && $under == -1) {
		$wh_under = '';
	} elseif (isset($RUBS_LIST_TYPE) && $RUBS_LIST_TYPE == 'select' && $under > 0) {
		$subRubs = pdoFetchAll("SELECT id FROM $tabler WHERE parent_id = '$under'");
		$subRubsIds = [];
		foreach ($subRubs as $subRub) {
			$subRubsIds[] = $subRub['id'];
		}

		if (!empty($subRubsIds)) {
			$wh_under = 'AND `' . $under_name . '`=' . $under . ' OR `' . $under_name . '` IN (' . implode(',', $subRubsIds) . ')';
		} else {
			$wh_under = 'AND `' . $under_name . '`=' . $under;
		}
	} elseif (isset(${"admin_$table"}->FIELD_UNDER) && $under_pos > 0 && $under > 0) {
		$wh_under = "AND `$under_name`='$under'";
	} elseif ($tabler != '' && !isset(${"admin_$table"}->TABLE_RUB_ASSOC) && !($type == 2 && isset(${"admin_$table"}->RUBS_NO_UNDER) && !($under > 0))) {
		$wh_under = "AND `$under_name`='$under'";
	} elseif (isset(${"admin_$table"}->TABLE_RUB_ASSOC) && $under_pos > 0) {
		$wh_under = "AND `$under_name`='$under'";
	} else {
		$wh_under = '';
	}

	if (isset(${"admin_$table"}->SP_WHERE_AND)) {
		$sp_wh_and = ${"admin_$table"}->SP_WHERE_AND;
	} else {
		$sp_wh_and = '';
	}

	//Permisions AND ...
	$wh_rights = '';

	//Если SP WHERE
	if ($type == 1 && isset($rowrub['sp_where'])) {
		$query = "SELECT * FROM $table WHERE " . $rowrub['sp_where'] . " LIMIT $starts, $shownum";
	}
	//Если SP Query
	elseif (isset(${"admin_$table"}->SP_QUERY)) {
		$query = ${"admin_$table"}->SP_QUERY . " LIMIT $starts, $shownum";
	}
	//Если посик - то смотрим везде
	elseif (isset($_GET['search_w']) && $_GET['search_w'] != '') {
		$shownum = '1000';

		$sfs = explode(',',$search_field);
		$wh_s = '';
		foreach ($sfs as $sf) {
			//Если таблица.поле - берем . в `
			if (strstr($sf,'.') !== false) {
				$sf = str_replace('.','`.`',$sf);
			}
			//Формируем WHERE
			$wh_s .= ' OR `' . trim($sf) . "` LIKE '%" . $_GET['search_w'] . "%'";
		}
		//JOIN
		if (!empty(${"admin_$table"}->SEARCH_JOIN)) {
			$sjoin = ${"admin_$table"}->SEARCH_JOIN;
		} else {
			$sjoin = '';
		}
		//Q
		$query = "SELECT *$spf FROM $table $langJoin $sjoin WHERE ($table.id = '" . $_GET['search_w'] . "' $wh_s) $sp_wh_and ORDER by $sort LIMIT $starts, $shownum";
		//echo $query;
	} else
		if ((isset(${"admin_$table"}->SHOW_ALL) && ${"admin_$table"}->SHOW_ALL == 1) && ($under == -1)) {  //Если стоит флаг показывать все в корне
			$query = "SELECT *$spf FROM $table WHERE id<>'' ORDER by $sort";
		} else
			if ($type != 1 && isset(${"admin_$table"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$table"}->TABLE_UNDER_DOP) || ${"admin_$table"}->TABLE_UNDER_DOP == $tabler)) { //mnogo $under_name RUBS
				$query = "SELECT distinct $table.*$spf FROM $table LEFT JOIN  " . ${"admin_$table"}->TABLE_RUB_ASSOC . " as admin_assoc ON ($table.id=admin_assoc.recID) WHERE admin_assoc.$under_name=$under OR $table.$under_name=$under ORDER by $sort LIMIT $starts, $shownum";
			}
	// Parent_id + Assoc table
	elseif (isset(${"admin_$table"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$table"}->TABLE_UNDER_DOP) || ${"admin_$table"}->TABLE_UNDER_DOP == $tabler)) { //mnogo $under_name
		if (isset(${"admin_$table"}->ASSOC_FIELD_ID)) {
			$afid = ${"admin_$table"}->ASSOC_FIELD_ID;
		} else {
			$afid = 'recID';
		}
		if (isset(${"admin_$table"}->ASSOC_FIELD_UNDER)) {
			$afunder = ${"admin_$table"}->ASSOC_FIELD_UNDER;
		} else {
			$afunder = $under_name;
		}

		//$query="SELECT $table.* FROM ".${"admin_$table"}->TABLE_RUB_ASSOC." as admin_assoc LEFT JOIN  $table ON  ($table.id=admin_assoc.$afid) WHERE admin_assoc.$afunder=$under ORDER by $sort LIMIT $starts, $shownum";
		$query = "SELECT *$spf FROM $table $langJoin WHERE id<>'' $wh_under OR id IN (SELECT $afid FROM " . ${"admin_$table"}->TABLE_RUB_ASSOC . " as admin_assoc WHERE admin_assoc.$afunder=$under) ORDER by $sort LIMIT $starts, $shownum";

		//echo $query;
	}
	// Regular query
	else {
		//echo 'standart';
		$query = "SELECT *$spf FROM $table $langJoin 
			WHERE 1 $wh_under $sp_wh_and ORDER by $sort LIMIT $starts, $shownum"; // Если все обычно
	}

	//echo ${"admin_$tablei"}->SHOW_ALL."-".$query;
	$result = mQuery($query);

	if (isset($_GET['show_q'])) {
		echo $query . ' -> ' . mError();
	}

	$num = mNumRows($result);

	//Если вообще есть строки
	if ($num > 0) {
		$f = 0;
		$params = '';
		foreach ($_GET as $key => $value) {
			if (!is_array($value) && $key != 'ipage' && $key != 'rpage' && $key != 'sort_items') {
				$value = str_replace(' ','%20',$value);
				$params .= '&' . $key . '=' . $value;
			}
		}

		/*ШАПКА ТАБЛИЦЫ*/
		echo '<div id="AjaxResult"></div>';
		echo '<table id="table-' . $type . '" class="table table-striped table-bordered table-condensed">';

		//Fields showed Number
		$fn = 0;
		echo "<thead>
		<th width=25>&nbsp;</th>
		<th><a href=\"catalog.php?sort=null$params\">" . (${"admin_$table"}->fld[getPosInFld(${"admin_$table"}->ECHO_NAME,${"admin_$table"}->fld)]->txt) . '</a></th>';
		$fn += 2;

		if (!empty(${"admin_$table"}->ECHO_ID)) {
			echo '<th>' . $echoid . '</th>';
			$fn++;
		}

		while (isset(${"admin_$table"}->fld[$f]->name)) {
			if (!empty($_GET['sort_items']) && strstr($_GET['sort_items'],'DESC')) {
				$sort_order = 'ASC';
				$sort_arr = '&darr;';
			} else {
				$sort_order = 'DESC';
				$sort_arr = '&uarr;';
			}

			if (!empty($_GET['sort_items']) && strstr($_GET['sort_items'],${"admin_$table"}->fld[$f]->name) !== false) {
				$h_style = 'style="font-weight:bold;"';
			} else {
				$h_style = '';
				$sort_arr = '';
			}

			//Sort for items only
			if ($type == 1) {
				$sort_href = 'catalog.php?sort_items=' . ${"admin_$table"}->fld[$f]->name . ' ' . $sort_order . $params;
			}
			//Checkbox
			if (${"admin_$table"}->fld[$f]->type == 6 && (${"admin_$table"}->fld[$f]->show_in_list == 1)) {
				echo '<th  style="text-align:center">';

				if (!empty($sort_href)) {
					echo '<a href="' . $sort_href . '" ' . $h_style . '>';
				}

				echo ${"admin_$table"}->fld[$f]->txt;

				if (!empty($sort_href)) {
					echo '</a> ' . $sort_arr;
				}

				if (!isset($_GET['search_w']) && ${"admin_$table"}->fld[$f]->edit_in_list == 1) {
					echo '<br/><a title="' . $word[$ALANG]['set_1'] . "\" onClick=\"confirming('" . $word[$ALANG]['set_1'] . "?','catalog.php?$_tables&act_table=$table&under=$under&set_1=" . ${"admin_$table"}->fld[$f]->name . "#header')\" href=#header ><img src=\"img/checkbox_1.gif\" border=\"0\" width=\"10\" /></a> <a title=\"" . $word[$ALANG]['set_0'] . "\" onClick=\"confirming('" . $word[$ALANG]['set_0'] . "?','catalog.php?$_tables&act_table=$table&under=$under&set_0=" . ${"admin_$table"}->fld[$f]->name . "#header')\" href=#header ><img src=\"img/checkbox_0.gif\" border=\"0\" width=\"10\"/></a>";
				}
				echo '</th>';
			} elseif (${"admin_$table"}->fld[$f]->show_in_list == 1) {
				echo '<th  style="text-align:center">';
				if (!empty($sort_href)) {
					echo '<a href="' . $sort_href . '" ' . $h_style . '>';
				}

				echo ${"admin_$table"}->fld[$f]->txt;

				if (!empty($sort_href)) {
					echo '</a> ' . $sort_arr;
				}
				echo '</th>';
			}

			//Count of printed colums
			if (${"admin_$table"}->fld[$f]->show_in_list == 1) {
				$fn++;
			}

			$f++;
		}

		echo '<th align=center><i class="glyphicon glyphicon-pencil"></i></th>';
		$fn++;

		//if ($pricepos>0) echo "<th  align=center width=50>Цена</th>";
		if ($sortpos > 0) {
			echo '<th align="center" width="40"><i class="glyphicon glyphicon-sort"></i></th>';
			$sort_max = 0;
			$sort_min = 999999;

			$fn++;
		}
		echo '<th align=center><a href="javascript:selectAllChbx(\'chbx_' . $type . '\')">all</a></th>';
		echo '</thead>';

		/*КОНЕЦ ШАПКИ ТАБЛИЦЫ*/

		echo '<tbody>';

		$disabled = ($noedit) ? 'disabled' : '';

		//Вывод тела таблицы
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchArray($result);

			//TR COLOR
			if (method_exists(${"admin_$table"},'rowColor')) {
				$rowcolor = ${"admin_$table"}->rowColor($row,$i);
			} elseif (($i % 2) == 0) {
				$rowcolor = '#e9e9e9';
			} else {
				$rowcolor = '#f5f5f5';
			}

			//Image preview
			$image = '';
			if (!empty($row['preview_name']) && is_file($pref . $FOLDER_FILES . '/preview/' . $row['preview_name'])) {
				$image = '<img src="' . $pref . $FOLDER_FILES . '/preview/' . $row['preview_name'] . '" border="0" width="50">';
			} elseif (isset($row['creation_time']) && is_file($pref . $FOLDER_FILES . '/preview/' . $row['creation_time'] . '.jpg')) {
				$image = '<img src="' . $pref . $FOLDER_FILES . '/preview/' . $row['creation_time'] . '.jpg" border="0" width="50">';
			} elseif (!empty($row['format']) && is_file('img/filetypes/' . $row['format'] . '.png')) {
				$image = '<img src="img/filetypes/' . $row['format'] . '.png" style="width:25px;" border="0">';
			} elseif ($type == 1) {
				$image = '<i class="glyphicon glyphicon-edit" style="color:#000"></i>';
			} else {
				$image = '<i class="glyphicon glyphicon-folder-open" style="color:#f3bd31"></i>';
			}

			if (isset(${"admin_$table"}->IMG_FIELD)) {
				$img_id = $row[${"admin_$table"}->IMG_FIELD];
			} elseif (!isset(${"admin_$table"}->IMG_FIELD) && isset($row['creation_time'])) {
				$img_id = $row['creation_time'];
			} else {
				$img_id = NULL;
			}

			if ($img_id != NULL) {
				$folder = GetImageFolder($table,$img_id);
				// W/O table
				if ((@filesize("../$FOLDER_IMAGES/$folder$img_id.1.s.$img_type")) > 1) {
					$image = "<img src=../$FOLDER_IMAGES_FRONTEND/$folder$img_id.1.s.$img_type border=0 width=" . ${"admin_$table"}->IMG_LIST_WIDTH . '>';
				}
				// W table
				elseif ((@filesize("../$FOLDER_IMAGES/$folder$table.$img_id.1.s.$img_type")) > 1) {
					$image = "<img src=../$FOLDER_IMAGES_FRONTEND/$folder$table.$img_id.1.s.$img_type border=0 width=" . ${"admin_$table"}->IMG_LIST_WIDTH . '>';
				}
			}
			/*********************/

			if ($type == 1) { //ITEM
				$_id = "id=$row[$idname]";
				$_edid = "id=$row[$idname]";

				if (!empty($row[$under_name])) {
					$_UNDER = "under=$row[$under_name]";
				} elseif (!empty($under)) {
					$_UNDER = "under=$under";
				} else {
					$_UNDER = '';
				}

				$_del = "delitem=$row[$idname]";

				if (!empty($row[$under_name])) {
					$_delunder = "under=$row[$under_name]";
				} else {
					$_delunder = 'under=-1';
				}

				$page = 'ipage';
				$_sortmode = '';
			} else { //RUB
				$_id = '';
				$_edid = "id=$row[$idname]";
				$_UNDER = "under=$row[$idname]";
				$_del = "del=$row[$idname]";
				$_delunder = 'under=-1';
				$page = 'rpage';
				$_sortmode = '&sortmode=rub';
			}
			$_srci = "srci=$srci";

			$row[$echo_item_name] = stripslashes($row[$echo_item_name]);

			if (!empty($row[$echoid])) {
				$echoID = $row[$echoid];
			} else {
				$echoID = '';
			}

			if (isset(${"admin_$table"}->ECHO_ID2)) {
				$echoID .= '_' . $row[${"admin_$table"}->ECHO_ID2];
			}

			if ($tabler != '' && $type != 1) {
				if (empty(${"admin_$table"}->RUBS_NO_UNDER)) {
					$qch = "SELECT count(id) FROM $tabler WHERE $under_name=$row[$idname]";
					$resch = mQuery($qch);
					$rowchr = mFetchArray($resch);
				} else {
					$rowchr[0] = 0;
				}

				if (!empty($tablei)) {
					$qch = "SELECT count(id) FROM $tablei WHERE $under_item_name=$row[$idname]";
					$resch = mQuery($qch);
					$rowchi = mFetchArray($resch);
				} else {
					$rowchi[0] = 0;
				}
				/*
				//If items or rubs inside folder
				if ((!empty($tablei) && $rowchi[0]>0) || (!empty($tabler) && $rowchr[0]>0) ) {
					$name_link="$act?$_tables&$_srci&$_UNDER#header";
				}
				else $name_link="$act?$_tables&$_srci&$_edid#header";*/

				$name_link = "$act?$_tables&$_srci&$_UNDER#header";

				if (strstr($image,'preview') != FALSE) {
					$img_link = "$act?$_tables&$_srci&$_edid#header";
				} else {
					$img_link = "$act?$_tables&$_srci&$_id&$_UNDER#header";
				}
			} else { //no tabler
				$img_link = "$act?$_tables&$_srci&$_edid#header";
				$name_link = "$act?$_tables&$_srci&$_edid#header";
			}
			echo "\n<tr id=\"" . $row['id'] . '" ><td>';
			if (!$noedit) {
				echo "<a href=\"$img_link\">$image</a>";
			}
			echo '</td>';
			echo '<td><strong>';
			if ($noedit) {
				echo '<a style="cursor:default; color:#333;">';
			} else {
				echo "<a href=\"$name_link\">";
			}

			echo ${"admin_$table"}->getRowName($row);

			echo '</a></strong></td>';

			if (!empty(${"admin_$table"}->ECHO_ID)) {
				echo "<td>$echoID</td>";
			}

			//Skip name
			$f = 1;

			while (isset(${"admin_$table"}->fld[$f]->name)) {
				if ((${"admin_$table"}->fld[$f]->show_in_list == 1) && method_exists(${"admin_$table"},'showit_' . ${"admin_$table"}->fld[$f]->name)) {
					echo '<td align=center>' . ${"admin_$table"}->{'showit_' . ${"admin_$table"}->fld[$f]->name}($row) . '</td>';
				} else
					if ((${"admin_$table"}->fld[$f]->show_in_list == 1) && method_exists(${"admin_$table"},'show_' . ${"admin_$table"}->fld[$f]->name)) {
						echo '<td align=center>' . ${"admin_$table"}->{'show_' . ${"admin_$table"}->fld[$f]->name}($row) . '</td>';
					} else {
						if (${"admin_$table"}->fld[$f]->type == 6 && ${"admin_$table"}->fld[$f]->show_in_list == 1) {
							if (${"admin_$table"}->fld[$f]->edit_in_list == 1) {
								if ($row[${"admin_$table"}->fld[$f]->name] == 1) {
									$chkd = 'checked="checked"';
								} else {
									$chkd = '';
								}

								echo "<td align=center><input $disabled name=\"" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . '" type="checkbox" id="' . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "\" value=\"1\" $chkd onclick=\"updateItem('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "','" . $row['id'] . "');\"/></td>";
							} else {
								if ($row[${"admin_$table"}->fld[$f]->name] == 1) {
									$chkd = '<span aria-hidden="true" class="glyphicon glyphicon-plus"></span>';
								} else {
									$chkd = '<span aria-hidden="true" class="glyphicon glyphicon-minus"></span>';
								}

								echo '<td align=center>' . $chkd . '</td>';
							}
						}
						if ((${"admin_$table"}->fld[$f]->type == 9) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list == 0)) {
							$row_assoc_item = FetchID(${"admin_$table"}->fld[$f]->table_val,$row[${"admin_$table"}->fld[$f]->name]);
							// FetchID returns false when the referenced record no longer
							// exists (orphan FK); keep rendering a blank cell instead of
							// warning in PHP 8+.
							$val = '';
							if (is_array($row_assoc_item)) {
								$field = ${"admin_$table"}->fld[$f]->table_field;
								$val   = $row_assoc_item[$field]
									?? $row_assoc_item[$field . '_1']
									?? '';
							}
							echo '<td align=center>' . $val . '</td>';
						}
						if ((${"admin_$table"}->fld[$f]->type == 9) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list == 1)) {
							$row_assoc_item = FetchID(${"admin_$table"}->fld[$f]->table_val,$row[${"admin_$table"}->fld[$f]->name]);
							$assocId = is_array($row_assoc_item) ? ($row_assoc_item['id'] ?? 0) : 0;
							echo '<td align=center>' . Gen_SelectJ($table,${"admin_$table"}->fld[$f]->table_val,${"admin_$table"}->fld[$f]->table_under,${"admin_$table"}->fld[$f]->name,$assocId,$row['id'],${"admin_$table"}->fld[$f]) . '</td>';
						}
						//echo ${"admin_$table"}->fld[$f]->name.'->'.${"admin_$table"}->fld[$f]->type.${"admin_$table"}->fld[$f]->show_in_list.${"admin_$table"}->fld[$f]->edit_in_list.' | ';

						// String no edit
						if ((${"admin_$table"}->fld[$f]->type <= 1 || ${"admin_$table"}->fld[$f]->type == 10) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list != 1)) {
							//echo ${"admin_$table"}->fld[$f]->name;
							$row[${"admin_$table"}->fld[$f]->name] = stripslashes($row[${"admin_$table"}->fld[$f]->name]);
							echo '<td align=center>' . $row[${"admin_$table"}->fld[$f]->name] . '</td>';
						}
						// Digit
						elseif ((${"admin_$table"}->fld[$f]->type == 0) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list == 1)) {
							echo '<td align=center>
                    <input name="' . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . '" 
                    type="text" id="' . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . '" 
                    value="' . $row[${"admin_$table"}->fld[$f]->name] . "\" 
                    title=\"Сохранение двойным щелчком\" 
                    onblur=\"updateItemVal('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "','" . $row['id'] . "');\" 
                    ondblclick=\"updateItemVal('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "','" . $row['id'] . "');\" 
                    style=\"width:40px;\"/>
                    </td>";
						}
						// String
						elseif ((${"admin_$table"}->fld[$f]->type == 1) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list == 1)) {
							if (!empty(${"admin_$table"}->fld[$f]->extra_param['inputWidthInList'])) {
								$width = ${"admin_$table"}->fld[$f]->extra_param['inputWidthInList'];
							} else {
								$width = 90;
							}
							echo '<td align=center>
                    <input name="' . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . '" 
                    type="text" id="' . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . '" 
                    value="' . $row[${"admin_$table"}->fld[$f]->name] . "\" title=\"Сохранение двойным щелчком\" 
                    onblur=\"updateItemVal('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "','" . $row['id'] . "');\" 
                    ondblclick=\"updateItemVal('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . str_replace(' ','_',$row['id']) . "','" . $row['id'] . "');\" 
                    style=\"width:" . $width . 'px;"/>
                    </td>';
						}
						if ((${"admin_$table"}->fld[$f]->type == 4) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list != 1)) {
							//echo ${"admin_$table"}->fld[$f]->name;
							$row[${"admin_$table"}->fld[$f]->name] = stripslashes($row[${"admin_$table"}->fld[$f]->name]);
							echo '<td align=center>' . $row[${"admin_$table"}->fld[$f]->name] . '</td>';
						}
						if ((${"admin_$table"}->fld[$f]->type == 2 || ${"admin_$table"}->fld[$f]->type == 7) && (${"admin_$table"}->fld[$f]->show_in_list == 1) && (${"admin_$table"}->fld[$f]->edit_in_list != 1)) {
							//echo ${"admin_$table"}->fld[$f]->name;
							$row[${"admin_$table"}->fld[$f]->name] = substr(strip_tags(stripslashes($row[${"admin_$table"}->fld[$f]->name])),0,250);
							echo '<td align=left>' . $row[${"admin_$table"}->fld[$f]->name] . '</td>';
						}
						if ((${"admin_$table"}->fld[$f]->type == 13) && (${"admin_$table"}->fld[$f]->show_in_list == 1)) {
							if (${"admin_$table"}->fld[$f]->edit_in_list == 0) {
								echo '<td align=center>' . $row[${"admin_$table"}->fld[$f]->name] . '</td>';
							} else if (${"admin_$table"}->fld[$f]->edit_in_list == 1) {
								echo '<td align=center><input name="' . ${"admin_$table"}->fld[$f]->name . $row['id'] . '" type="text" id="' . ${"admin_$table"}->fld[$f]->name . $row['id'] . '" value="' . $row[${"admin_$table"}->fld[$f]->name] . "\" onkeyup=\"updateItemVal('" . $table . "','" . ${"admin_$table"}->fld[$f]->name . "','" . ${"admin_$table"}->fld[$f]->name . $row['id'] . "','" . $row['id'] . "');\" style=\"width:90px;\"/></td>";
							}
						}
						if ((${"admin_$table"}->fld[$f]->type == 14) && (${"admin_$table"}->fld[$f]->show_in_list == 1)) {
							echo '<td align=center>' . $row[${"admin_$table"}->fld[$f]->name] . '</td>';
						}
						if ((${"admin_$table"}->fld[$f]->type == 15) && (${"admin_$table"}->fld[$f]->show_in_list == 1)) {
							echo '<td style="background-color:' . $row[${"admin_$table"}->fld[$f]->name] . '">&nbsp;</td>';
						}
					}
				//echo ${"admin_$table"}->fld[$f]->name.$f;

				//Count of fields in class
				$f++;
			} //Конец цикла вывода колонок в строке

			if (empty($_SESSION['admin']['group']['del_restrict']) && (!(isset($row['no_del']) && $row['no_del'] == 1))) {
				$del_pict = "<a onClick=\"confirming('" . $word[$ALANG]['del'] . ' ' . addslashes($row[${"admin_$table"}->fld[0]->name]) . "?','$src?$_tables&$_delunder&$_del')\" href=#><i class=\"glyphicon glyphicon-trash\"></i></a>";
				if ($noedit) {
					$del_pict = '';
				}
				$no_del = 0;
			} else {
				$del_pict = '<i class="glyphicon glyphicon-trash"></i>';
				$no_del = 1;
			}
			//Колонки управления
			if ($noedit) {
				echo '<td width=45 align=center>' . $del_pict . '</td>';
			} else {
				echo "<td width=45 align=center><a href=\"$act?$_tables&$_edid#header\"><i class=\"glyphicon glyphicon-edit\"></i></a>&nbsp;$del_pict</td>";
			}

			//Сортировка
			if ($sortpos > 0) {
				echo "<td width=\"59\">
			<a href=$src?srci=$srci&$_tables&under=$under&sort_set=$sort_set&sortid=$row[$idname]&$page=$ipage&rand=" . rand(100,1000) . "$_sortmode&sort=up#header><i class=\"glyphicon glyphicon-arrow-up\"></i></a> 
			<a href=$src?srci=$srci&$_tables&under=$under&sort_set=$sort_set&sortid=$row[$idname]&$page=$ipage&rand=" . rand(100,1000) . "$_sortmode&sort=down#header><i class=\"glyphicon glyphicon-arrow-down\"></i></a> 
			<a href=$src?srci=$srci&$_tables&under=$under&sort_set=$sort_set&sortid=$row[$idname]&$page=$ipage&rand=" . rand(100,1000) . "$_sortmode&sort=down_max#header><i class=\"glyphicon glyphicon-sort-by-attributes\"></i></a></td>";
				if ($row['sort'] > $sort_max) {
					$sort_max = $row['sort'];
				}
				if ($row['sort'] < $sort_min) {
					$sort_min = $row['sort'];
				}
			}

			//Птички
			echo '<td width=45 align=center>
		<input ' . $disabled . ' type="checkbox" class="chbx_' . $type . '" id="chbx_' . $row['id'] . '" value="1" rel="' . $row['id'] . '" no_del="' . $no_del . '" /></td>';

			echo '</tr>';
		}//Конец цикла вывода строк

		//Подчеркивание
		echo '</tbody>
	<tfoot>
	<tr><td ' . (isset($f)?('colspan="' . $fn . '"'):'') . '></td>
	<td align="center" class="text-nowrap">';

		if ($table == 'catalog_products') {
			echo "<a href=\"javascript:moveAllChbx('chbx_" . $type . "','" . $table . "')\"><i class=\"glyphicon glyphicon-log-out\"></i> Move</a><br/>";
		}

		if (!$noedit) {
			echo "<a href=\"javascript:delAllChbx('chbx_" . $type . "','" . $table . "')\"><i class=\"glyphicon glyphicon-trash\"></i> Del</a>";
		}
		echo '</td></tr>
    </tfoot>';
		echo '</table>';

		//Драг-енд-дроп
		if ($sortpos > 0) {
			echo "<script type=\"text/javascript\"> 
		$(document).ready(function() 
		{
			 $('#table-$type').tableDnD(
			 {
				  onDrop: function(table, row) {
	        //alert('Result of $.tableDnD.serialise() is '+$.tableDnD.serialize());
		    $('#AjaxResult').load('ajax/sort.php?table=$table&type=$type&sort_set=$sort_set&sort_max=$sort_max&sort_min=$sort_min&'+$.tableDnD.serialize());
				}
        });
		}
);
</script>";
		}

		//Pages
		if (!((isset(${"admin_$table"}->SHOW_ALL) && ${"admin_$table"}->SHOW_ALL == 1) && ($under == -1))) {
			$pages = '';

			if ($type == 1 && !empty($rowrub['sp_where'])) { //Если SP
				$query = "SELECT * FROM $table WHERE " . $rowrub['sp_where'] . '';
			} else
				if (isset($_GET['search_w']) && $_GET['search_w'] != '') { //Если посик - то смотрим везде
					$query = "SELECT * FROM $table $langJoin WHERE id='" . $_GET['search_w'] . "' OR $echo_item_name LIKE '%" . $_GET['search_w'] . "%' $wh_rights";
				} else
					if (isset(${"admin_$table"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$table"}->TABLE_UNDER_DOP) || ${"admin_$table"}->TABLE_UNDER_DOP == $tabler)) { //mnogo $under_name
						$query = "SELECT $table.* FROM " . ${"admin_$table"}->TABLE_RUB_ASSOC . " as admin_assoc RIGHT JOIN  $table ON  ($table.id=admin_assoc.$afid) WHERE admin_assoc.$afunder=$under OR $table.$under_name=$under";
						//echo $query;
					} else {
						$query = "SELECT * FROM $table WHERE id<>'' $wh_under $sp_wh_and $wh_rights";
					} // Если все обычно

			//Все GET-параметры
			$params = '';
			foreach ($_GET as $key => $value) {
				$value = str_replace(' ','%20',$value);

				if (!is_array($_GET[$key])) {
					$params .= '&' . $key . '=' . $value;
				} else {
					foreach ($_GET[$key] as $key2 => $val2) {
						$params .= '&' . $key . '[' . $key2 . ']' . '=' . $val2;
					}
				}
			}

			$result = mQuery($query);
			//echo $query.mError();
			$num = mNumRows($result);

			for ($i = 0; $i < ceil($num / $shownum); $i++) {
				$params_p = preg_replace("/$page=\d/",'',$params);
				if (($ipage) == $i) {
					$pages .= ' <strong>' . ($i + 1) . '</strong> ';
				} else {
					$pages .= " <a href=catalog.php?$page=$i" . $params_p . '#header>' . ($i + 1) . '</a> ';
				}
			}

			if ($i > 1) {
				echo imfile . ' ' . imfile . " $pages";
			}
		}

		// После сводной таблици выводим дополнительный блок
		if (method_exists(${"admin_$table"},'afterTable')) {
			echo ${"admin_$table"}->afterTable();
		}
	}

	//Нету строк в таблице
	else {
		echo '---';
	}
	return $result;
}

if (!function_exists('mb_ucfirst')) {
	function mb_ucfirst($text)
	{
		return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
	}
}

function varDump($var)
{
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
}

//-------------- <functions for models> --------------

function modelsMakeAliasForDomain($table, &$row, $domainId)
{
	if (!empty($table) && !empty($row) && !empty($domainId)) {
		$fieldSet = 'alias_' . $domainId;
		$fieldBy = 'name_' . $domainId;
		if (empty($row[$fieldSet])) {
			$row[$fieldSet] = $row[$fieldBy];
		}
		$row[$fieldSet] = strtolower(trim(Translit(trim($row[$fieldSet])), '-'));
		pdoExec('UPDATE ' . $table . ' SET ' . $fieldSet . " = '" . $row[$fieldSet] . "' WHERE id = " . $row['id']);
	}
}

function modelsMakeChpuUrlForDomain($url, $urlBase, $domainId)
{
	if (!empty($url) && !empty($urlBase) && !empty($domainId)) {
		$q = 'INSERT INTO bs_urls
                SET url = "' . $url . '", 
                	url_base = "' . $urlBase . '",
                	domain_id = "' . $domainId . '"
                ON DUPLICATE KEY UPDATE url = "' . $url . '"';
		//echo '!->'.$q;
		pdoExec($q);
	}
}

function orderConfirmed($id)
{
	if (!empty($id)) {
		$pathToRoot = GlobalConfiguration::getInstance()->getOtherData('dirs', 'pathToRoot');
		$mainAutoload = $pathToRoot . 'application/core/Autoload.php';

		$pathToModules = GlobalConfiguration::getInstance()->getOtherData('dirs', 'pathToModules');
		$AutoloadFile = $pathToModules . 'feedback/Autoload.php';

		if (file_exists($mainAutoload) && file_exists($AutoloadFile)) {
			require_once $mainAutoload;
			require_once $AutoloadFile;

			if (class_exists('ControllerFeedback')) {
				$cFeedback = new ControllerFeedback();

				$orderId = $id;
				$agencyId = 0;
				$checkLimit = 1;
				$res = $cFeedback->openOrderLK($orderId, $agencyId, $checkLimit);
				$cFeedback->updateRecommendedClinicsByNewOrder($orderId);
				//var_dump($res);
				unset($cFeedback);
				return true;
			}
		}
	}
	return false;
}

function copyOrder($id)
{
	if (!empty($id)) {
		$pathToRoot = GlobalConfiguration::getInstance()->getOtherData('dirs', 'pathToRoot');
		$mainAutoload = $pathToRoot . 'application/core/Autoload.php';

		$pathToModules = GlobalConfiguration::getInstance()->getOtherData('dirs', 'pathToModules');
		$AutoloadFile = $pathToModules . 'feedback/Autoload.php';

		if (file_exists($mainAutoload) && file_exists($AutoloadFile)) {
			require_once $mainAutoload;
			require_once $AutoloadFile;

			if (class_exists('ControllerFeedback')) {
				$cFeedback = new ControllerFeedback();

				$orderId = $id;
				$res = $cFeedback->copyOrder($orderId);
				//var_dump($res);
				unset($cFeedback);
				return true;
			}
		}
	}
	return false;
}

//30.12.2014
function printEditPriceDialog($entity)
{
	$epVals = ['price_from' => 0,
		'price_to' => 0,
		'texts' => [
			'price' => ['text' => '', 'title' => 'Цена текстом', 'id' => 'ep_' . $entity . '_price', 'type' => 1],
			'terms' => ['text' => '', 'title' => 'Сроки', 'id' => 'ep_' . $entity . '_terms', 'type' => 1],
			'descr' => ['text' => '', 'title' => 'Описание', 'id' => 'ep_' . $entity . '_descr', 'type' => 2],
		]
	];

	// Initialize row with default values
	$row = [
		'price_from' => '',
		'price_to' => ''
	];

	$html = '<div id="ep-tabs-' . $entity . '">
     <ul>
        <li><a href="#ep-tabs-' . $entity . '-1">Общие сведенья</a></li>';
	$siteDomains = pdoFetchAll('SELECT * FROM domains ORDER by id ASC');
	if (!empty($siteDomains)) {
		$tabIndex = 1;
		foreach ($siteDomains as $domain) {
			$tabIndex++;
			$html .= '<li><a href="#ep-tabs-' . $entity . '-' . $tabIndex . '">Домен: ' . $domain['name'] . '</a></li>';
		}
	}
	$html .= '</ul>
             <div id="ep-tabs-' . $entity . '-1">';

	$html .= 'Стоимость ОТ: <input type="text" id="' . $entity . '_price_from" value="' . $row['price_from'] . '"><br />
                 Стоимость ДО: <input type="text" id="' . $entity . '_price_to" value="' . $row['price_to'] . '"><br />';

	$html .= '</div>';
	if (!empty($siteDomains)) {
		$tabIndex = 1;
		foreach ($siteDomains as $domain) {
			$tabIndex++;
			$html .= '<div id="ep-tabs-' . $entity . '-' . $tabIndex . '">';
			foreach ($epVals['texts'] as $key => $value) {
				$inputName = '' . $entity . '_' . $key . '_' . $domain['id'] . '_text';
				if ($value['type'] == 1) {
					$size = 'size="40"';
					$html .= $value['title'] . ': <input type="text" data-type="1" name="' . $inputName . '" id="' . $inputName . '"  $size value="' . $value['text'] . '" />';
				}
				if ($value['type'] == 2) {
					$size = 'cols="70" rows="5"';
					$html .= $value['title'] . ': <textarea data-type="2" name="' . $inputName . '" id="' . $inputName . '"  wrap="VIRTUAL" $size >
                    ';
					$html .= $value['text'];
					$html .= '</textarea>';
					$html .= '</textarea><script>
                    var ed' . $inputName . ' = CKEDITOR.replace("' . $inputName . '", {
                        toolbarStartupExpanded: false,
                        filebrowserImageUploadUrl: "/admin/ajax/ckupload.php?type=Images",
                        filebrowserUploadUrl:      "/admin/ajax/ckupload.php?type=Files"
                    });
                CKEDITOR.instances["' . $inputName . '"].on("change", function() { CKEDITOR.instances["' . $inputName . '"].updateElement(); });
                    </script>';
				}
			}
			$html .= '</div>';
		}
	}
	$html .= '<input type="button" value="Сохранить" onclick=\'saveEntityPrice("' . $entity . '");\'>
                 <input type="button" value="Отмена" onclick=\'closeEditEntityPrice("' . $entity . '");\'>';
	$html .= '</div>';
	echo '<div id="ep_edit_' . $entity . '_container" style="display:none;">' . $html . '</div>
            <script>
                $(function() {
                    $("#ep-tabs-' . $entity . '").tabs();
                });
            </script>';
}
//end 30.12.2014
//-------------- </functions for models> --------------

//16.09.2014
function stripSingleTag($tag, $string)
{
	$string = preg_replace('/<' . $tag . '[^>]*>/i', '', $string);
	$string = preg_replace('/<\/' . $tag . '>/i', '', $string);
	return $string;
}
//end 16.09.2014

//06.01.2015
function getLinkToUserFile($format, $filename)
{
	global $pref,$FOLDER_FILES;
	$noPhotoName = '/frontend/no-image-blog-one.png';
	if (!empty($filename) && !empty($format)) {
		$file = $pref . $FOLDER_FILES . '/' . $format . '/' . $filename;
		$link = '/' . $FOLDER_FILES . '/' . $format . '/' . $filename;
		if (file_exists($file)) {
			return $link;
		} else {
			return $noPhotoName;
		}
	}
	return $noPhotoName;
}
//end 06.01.2015
