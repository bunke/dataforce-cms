<?php

/*
 * Core functions of admin
 * */

class AdminApp
{
	public function insertHead($cssOnly = false)
	{
		if (!$cssOnly) {
			include('inc/HeadCssJavaLinks.php');
		} else {
			echo '<link href="/admin/admin.css" rel="stylesheet" type="text/css">';
		}
	}
}

class Field
{
	public $name;
	public $txt;
	public $val;
	public $type;
	public $show_in_list;
	public $edit_in_list;
	public $table_val;
	public $table_under;
	public $table_field;
	public $multi_lang;
	public $extra_param;

	function __construct($_name, $_txt, $_type, $params = [])
	{
		$this->name = $_name;
		$this->txt = $_txt;
		$this->type = $_type;

		$this->show_in_list = isset($params['showInList']) ? $params['showInList'] : 0;
		$this->edit_in_list = isset($params['editInList']) ? $params['editInList'] : 0;
		$this->table_val = isset($params['valsFromTable']) ? $params['valsFromTable'] : '';
		$this->table_under = isset($params['valsFromCategory']) ? $params['valsFromCategory'] : NULL;
		$this->table_field = isset($params['valsEchoField']) ? $params['valsEchoField'] : 'name';
		$this->multi_lang = isset($params['multiLang']) ? $params['multiLang'] : 0;
		$this->extra_param = $params;
	}

	public function genInput($params)
	{
		global $FOLDER_FILES, $word, $ALANG;

		$row = $params['row'];
		$f_dop = (isset($params['f_dop'])?$params['f_dop']:'');
		$disabled = (isset($params['disabled'])?$params['disabled']:'');
		$name_dop = (isset($params['name_dop'])? ' ' . $params['name_dop']:'');
		$tableObj = (isset($params['tableObj'])?$params['tableObj']:null);

		$fieldName = $this->name . $f_dop;

		$r = '
			<div class="form-group">
			<label for="' . $fieldName . '">' . $this->txt . $name_dop . '</label>';

		if (!empty($this->extra_param['defaultValueGenerator']) && empty($row['id'])) {
			if (method_exists($tableObj, $this->extra_param['defaultValueGenerator'])) {
				$row[$fieldName] = $tableObj->{$this->extra_param['defaultValueGenerator']}();
			}
		}

		if ($this->type == 1) {
			if (isset($row[$fieldName])) {
				$itemv = $row[$fieldName];
			} elseif (isset($_GET[$fieldName])) {
				$itemv = $_GET[$fieldName];
			} else {
				$itemv = '';
			}

			$itemv = str_replace('"','&quot;',$itemv);

			if (!empty($this->extra_param['noEdit'])) {
				$r .= $itemv . '<input type="hidden" name="' . $fieldName . '" ' . $disabled . ' value="' . $itemv . '"/>';
			} else {
				$r .= '
				<input name="' . $fieldName . '" id="' . $fieldName . "\" $disabled type=\"text\" 
				class=\"form-control\" value=\"" . $itemv . '">';
			}
		} elseif ($this->type == 2) {
			$itemv = (isset($row[$fieldName])?stripslashes($row[$fieldName]):'');

			if (!empty($row['no_fck'])) {
				$size = 'cols="70" rows="7"';
			} else {
				$size = 'cols="70" rows="5"';
			}

			if ($disabled != '') {
				$r .= '<div style="background-color:#F1F1F1;border:1px solid #CCC;min-height:30px;">';
			} else {
				$r .= '<br><textarea name="' . $fieldName . "\"  wrap=\"VIRTUAL\" $disabled $size>
";
			}

			$r .= $itemv;

			if ($disabled != '') {
				$r .= '</div>';
			} elseif (!empty($row['no_fck'])) {
				$r .= '</textarea>';
			} else {
				$r .= '</textarea><script>
				var ed' . $fieldName . " = CKEDITOR.replace('" . $fieldName . "', {
					filebrowserImageUploadUrl: '/admin/ajax/ckupload.php?type=Images',
					filebrowserUploadUrl:      '/admin/ajax/ckupload.php?type=Files'
				});
			</script>";
			}
		} elseif ($this->type == 7) {
			$itemv = (isset($row[$fieldName])?stripslashes($row[$fieldName]):'');

			$size = 'cols="70" rows="7"';

			if ($disabled != '') {
				$r .= '<div style="background-color:#F1F1F1;border:1px solid #CCC;min-height:30px;">';
			} else {
				$r .= '<br><textarea name="' . $fieldName . "\"  wrap=\"VIRTUAL\" $disabled $size>
";
			}

			$r .= str_replace('<br/>', "\n", $itemv);

			if ($disabled != '') {
				$r .= '</div>';
			} else {
				$r .= '</textarea>';
			}
		} elseif ($this->type == 11) {
			$itemv = (isset($row[$fieldName])?stripslashes($row[$fieldName]):'');

			$r .= '<br><input name="' . $fieldName . '" type="file" class="window" id="' . $fieldName . '" size="25" maxlength="255"> ';
			if ($itemv != '') {
				$tmp = explode('.',$itemv);
				$tmp = array_reverse($tmp);
				$format = $tmp[0];

				$filename = '../' . $FOLDER_FILES . '/' . $format . '/' . $itemv;
				if (file_exists($filename)) {
					$fs = (int)(@filesize($filename) / 1024);

					$r .= $fs . " kb [ <a href=\"$filename\" target=_blank>" . imlook . '</a>';

					$r .= ' <input type="checkbox" name="del_' . $fieldName . '" value="1"> ' . $word[$ALANG]['del'] . ' ]';
				}
			}
			$r .= '</p>';
		} elseif ($this->type == 16) {
			$itemv = (isset($row[$fieldName])?stripslashes($row[$fieldName]):'');

			$size = 'cols="70" rows="5"';

			if ($disabled != '') {
				$r .= '<div style="background-color:#F1F1F1;border:1px solid #CCC;min-height:30px;">';
			} else {
				$r .= '<br><textarea name="' . $fieldName . "\"  wrap=\"VIRTUAL\" $disabled $size style=\"width:100%\">";
			}

			$r .= $itemv;

			if ($disabled != '') {
				$r .= '</div>';
			} else {
				$r .= '</textarea>';
			}
		}
		$r .= '</div>';

		return $r;
	}
}

// Динамические текстовые поля для сущностей
class DynamicField
{
	public $alias;
	//public $lang;
	public $label;
	public $value;
	public $type;
	public $extra_param;

	function __construct($_alias,$_label,$_type,$_extra_param = NULL)
	{
		$this->alias = $_alias;
		$this->label = $_label;
		$this->type = $_type;
		$this->extra_param = $_extra_param;
	}
}

//File operations class
class AdminFile
{
	public $crtDate;
	public $usersFileName;
	public $tmpFileName;
	public $format;
	public $newName;

	function __construct($inDate,$FILES,$fieldname = 'upfile')
	{
		$this->crtDate = $inDate;
		$this->usersFileName = strtolower($FILES[$fieldname]['name']);
		$this->tmpFileName = $FILES[$fieldname]['tmp_name'];
		$this->format = $this->getFormat();
	}

	function getFormat()
	{
		$formats = ['docx', 'xlsx', 'doc', 'xls', 'pdf', 'jpg', 'gif', 'png', 'zip', 'rar', 'djvu', 'swf', 'ppt', 'mp3', 'mp4', 'avi', 'wmv', 'wma', 'chm', 'csv'];
		foreach ($formats as $val) {
			if (strstr($this->usersFileName,".$val") !== FALSE) {
				return $val;
			}
		}
		return '';
	}

	function upload($newName = '')
	{
		global $pref,$FOLDER_FILES,$NO_ADMIN;

		if ($this->format == '') {
			echo '<br />Этот тип файлов не поддерживается!';
			return '';
		} else {
			if ($newName != '') {
				$this->newName = $newName;
			} else {
				$this->newName = $this->crtDate;
			}

			$this->newName = strtolower(Translit(str_replace(' ','_',$this->newName)));

			$this->newName = str_replace('.' . $this->format,'_' . $this->crtDate . '.' . $this->format,$this->newName);

			$folder = $pref . $FOLDER_FILES . '/' . $this->format . '/';

			if (!is_dir($folder)) {
				mkdir($folder,0777);
			}

			if (strstr($this->newName, $this->format) === FALSE) {
				$newname = $folder . $this->newName . '.' . $this->format;
				$this->newName = $this->newName . '.' . $this->format;
			} else {
				$newname = $folder . $this->newName;
			}

			@unlink($newname);
			move_uploaded_file($this->tmpFileName, $newname);
			@copy($this->tmpFileName, $newname);
			chmod($newname,0644);
			return $this->newName;
		}
	}
}

// Base class for models
class AdminTable
{
	public $TABLE;
	public $fld;
	public $SORT = 'sort desc';
	public $ECHO_ID = 'id';
	public $ECHO_NAME = 'name';
	public $NAME = 'Записи';
	public $NAME2 = 'запись';
	public $MULTI_LANG = 0;
	public $PSEUDO_MULTI_LANG = 0;
	public $USE_TAGS = 0;
	public $NO_EDIT = false;
	public $IMG_FIELD = 'id';
	public $FOLDER_IMAGES = 2;
	public $LEVEL_OF_RUBS = 0;
	public $IMG_NUM = 0;
	public $IMG_SIZE = 0;
	public $IMG_VSIZE = 0;
	public $IMG_MASK_TYPE = 1;
	public $IMG_LIST_WIDTH = 50;
	public $USE_EXTRA_PARAMS_RANGES = 0;
	public $EXTRA_TABLES = [];

	public function addSpFields($row,$under)
	{
		return;
	}
	public function getField($name)
	{
		if ($name == 'id') {
			return $this->fld[-1];
		} else {
			return $this->fld[getPosInFld($name, $this->fld)];
		}
	}
	public function getRowName($row)
	{
		if (!is_array($row)) {
			return '';
		}

		$echo_item_name = $this->ECHO_NAME;

		if (method_exists($this, 'show_' . $echo_item_name)) {
			return $this->{'show_' . $echo_item_name}($row);
		} elseif ($this->getField($echo_item_name)->type == 9) {
			$fkField = $this->getField($echo_item_name);
			$row_assoc_item = FetchID($fkField->table_val, $row[$fkField->name] ?? 0);

			if (is_array($row_assoc_item)) {
				return $row_assoc_item[$fkField->table_field]
					?? $row_assoc_item[$fkField->table_field . '_1']
					?? '';
			}
			return '';
		} else {
			if (empty($this->MULTI_LANG) || empty($row[$echo_item_name . '_1'])) {
				return $row[$echo_item_name] ?? '';
			}
			return $row[$echo_item_name . '_1'] ?? '';
		}
	}

	public function fillValuesFromPost()
	{
		global $LANGS;
		$i = 0;
		$formatpos = null;
		$sortpos = null;
		$filenamepos = null;
		$preview_pos = null;

		while (isset($this->fld[$i]->name)) {
			//Count positions of spec fields
			if ($this->fld[$i]->name == 'format') {
				$formatpos = $i;
			} elseif ($this->fld[$i]->name == 'filename') {
				$filenamepos = $i;
			} elseif ($this->fld[$i]->name == 'preview_name') {
				$preview_pos = $i;
			} elseif ($this->fld[$i]->name == 'sort') {
				$sortpos = $i;
			}

			if ($this->fld[$i]->multi_lang == 0) {
				$this->fld[$i]->val = isset($_POST[$this->fld[$i]->name])?$_POST[$this->fld[$i]->name]:'';
			} else {
				foreach ($LANGS as $lang => $langName) {
					$this->fld[$i]->val[$lang] = isset($_POST[$this->fld[$i]->name . '_' . $lang])?$_POST[$this->fld[$i]->name . '_' . $lang]:'';
				}
			}
			$i++;
		}
		//var_dump($this->fld);
		return ['formatpos' => $formatpos, 'filenamepos' => $filenamepos, 'preview_pos' => $preview_pos, 'sortpos' => $sortpos];
	}

	public function insertQuery()
	{
		global $LANGS;

		if (!empty($this->TABLE)) {
			$table = $this->TABLE;
		} else {
			$className = get_class($this);
			$table = str_replace('admin_', '', $className);
		}

		$query = "INSERT INTO `$table` (";
		$i = 0;
		$queryFields = [];
		$queryItems = [];
		$queryLangFields = [];
		$queryLangItems = [];

		while (isset($this->fld[$i])) {
			if ($this->fld[$i]->multi_lang == 0) {
				if ($this->fld[$i]->type != 5 && $this->fld[$i]->type != 8 && $this->fld[$i]->type != 14) {
					$queryFields[] = '`' . $this->fld[$i]->name . '`';
				}
			} elseif (empty($this->fld[$i]->extra_param['fromTable'])) {
				$queryLangFields[] = '`' . $this->fld[$i]->name . '`';
			}
			$i++;
		}

		$query .= implode(',', $queryFields) . ') VALUES (';

		$i = 0;

		while (isset($this->fld[$i])) {
			if ($this->fld[$i]->multi_lang == 0) {
				if ('sort' == $this->fld[$i]->name) {
					$this->fld[$i]->val = 0;
				}

				if (($this->fld[$i]->type) == 0) {
					$this->fld[$i]->val = str_replace(',','.',$this->fld[$i]->val);
				}
				if (($this->fld[$i]->type) == 1) {
					$this->fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $this->fld[$i]->val);
				}

				if (($this->fld[$i]->type) == 7 && !isset(${'admin_' . $table}->TEXTARR_NO_BR)) {
					$this->fld[$i]->val = stripslashes($this->fld[$i]->val);
					$this->fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $this->fld[$i]->val);
					$this->fld[$i]->val = str_ireplace("\n",'<br/>',$this->fld[$i]->val);
				}

				if (($this->fld[$i]->type) == 2) {
					$this->fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $this->fld[$i]->val);

					//$this->fld[$i]->val=preg_replace("/style=\"(.*)\"/U","",$this->fld[$i]->val);

					$this->fld[$i]->val = preg_replace('/class="(.*)"/U','',$this->fld[$i]->val);
					$this->fld[$i]->val = preg_replace('/lang="(.*)"/U','',$this->fld[$i]->val);
					$this->fld[$i]->val = preg_replace('/<!--(.*)-->/U','',$this->fld[$i]->val);
					$this->fld[$i]->val = str_ireplace("\<\/o:p\>",'',$this->fld[$i]->val);
					$this->fld[$i]->val = str_ireplace("\<o:p\>",'',$this->fld[$i]->val);

					//$this->fld[$i]->val = addslashes($this->fld[$i]->val);
				}
				//$this->fld[$i]->val = addslashes($this->fld[$i]->val);
				if (($this->fld[$i]->type) != 5 && ($this->fld[$i]->type) != 8 && ($this->fld[$i]->type) != 14) {
					$queryItems[] = "'" . $this->fld[$i]->val . "'";
				}
			} elseif (empty($this->fld[$i]->extra_param['fromTable'])) {
				foreach ($LANGS as $lang => $langName) {
					$val = $this->fld[$i]->val[$lang];

					if (($this->fld[$i]->type) == 1) {
						$val = str_replace(["'", '&quot;'], ['`', '"'], $val);
					} elseif (($this->fld[$i]->type) == 2) {
						$val = str_replace(["'", '&quot;'], ['`', '"'], $val);

						//$val=preg_replace("/style=\"(.*)\"/U","",$val);

						$val = preg_replace('/class="(.*)"/U','',$val);
						$val = preg_replace('/lang="(.*)"/U','',$val);
						$val = preg_replace('/<!--(.*)-->/U','',$val);
						$val = str_ireplace("\<\/o:p\>",'',$val);
						$val = str_ireplace("\<o:p\>",'',$val);

						$val = addslashes($this->fld[$i]->val[$lang]);
					}

					$queryLangItems[$lang][] = "'" . $val . "'";
				}
			}
			$i++;
		}

		$query .= implode(',', $queryItems) . ');';

		$res = pdoExec($query);
		//echo $query;

		//var_dump($res);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			$q = "SELECT * FROM $table ORDER by id DESC LIMIT 1";
			$rownew = current(pdoFetchAll($q));

			if ($rownew['id'] > 0 && $this->MULTI_LANG == 1) {
				$queryLangFields[] = 'record_id';
				$queryLangFields[] = 'lang';
				foreach ($LANGS as $lang => $langName) {
					$queryLangItems[$lang][] = "'" . $rownew['id'] . "'";
					$queryLangItems[$lang][] = "'" . $lang . "'";

					$q = 'INSERT INTO ' . $table . '_info (' . implode(',', $queryLangFields) . ') 
						VALUES (' . implode(',', $queryLangItems[$lang]) . ')';

					$res = pdoExec($q);
					//echo '<center>' . $q . '</center>';
					if ($res === false) {
						var_dump(pdoError());
					}
				}
			} else {
				//echo 'No new row!';
			}
		}

		return $rownew;
	}

	public static function getRelatedId($obj, $extraTable, $joinRule)
	{
		$joinFields = array_keys($joinRule);

		if (is_object($obj)) {
			$val = $obj->getField($joinRule[$joinFields[0]])->val;
		} elseif (is_array($obj)) {
			$val = $obj[$joinRule[$joinFields[0]]];
		}

		$q = 'SELECT id FROM ' . $extraTable . ' WHERE ' . $joinFields[0] . " = '" . $val . "' AND " . $joinFields[1] . " = '" . $joinRule[$joinFields[1]] . "'";

		$rowsExtra = pdoFetchAll($q);

		if (!empty($rowsExtra[0]['id'])) {
			return $rowsExtra[0]['id'];
		} else {
			return 0;
		}
	}

	public function updateQuery($id) //generates UPD QUERY "UPDATE `$table` SET `html`='$html', `zag` = '$zag', `an`='$an', `txt`='$txt' WHERE id='$id'";
	{
		global $LANGS;

		if (isset($this->ALT_ID)) {
			$idname = $this->ALT_ID;
		} else {
			$idname = 'id';
		}

		$this->fld[-1] = new Field('id','',0);
		$this->fld[-1]->val = $id;

		if (!empty($this->TABLE)) {
			$table = $this->TABLE;
		} else {
			$className = get_class($this);
			$table = str_replace('admin_', '', $className);
			$this->TABLE = $table;
		}

		$tables = [$table => []];
		$idByTable = [$table => $id];

		if (!empty($this->EXTRA_TABLES)) {
			$tables = array_merge($tables, $this->EXTRA_TABLES);
		}

		$query = 'UPDATE `' . $this->TABLE . '` SET ';

		foreach ($tables as $table => $joinRule) {
			// No recordId for update
			if (empty($idByTable[$table])) {
				// Extra table record search
				if (!empty($joinRule)) {
					$extraTable = $table;

					$relatedId = self::getRelatedId($this, $extraTable, $joinRule);

					// External row founded
					if ($relatedId > 0) {
						$idByTable[$extraTable] = $relatedId;
					}
					// Need insert!
					else {
						$joinFields = array_keys($joinRule);
						$q = 'INSERT INTO ' . $extraTable . ' SET ' . $joinFields[0] . " = '" . $this->getField($joinRule[$joinFields[0]])->val . "', "
						. $joinFields[1] . " = '" . $joinRule[$joinFields[1]] . "'";
						pdoExec($q);

						$relatedId = self::getRelatedId($this, $extraTable, $joinRule);

						$idByTable[$extraTable] = $relatedId;
					}
				}
				unset($extraTable);
			}
			//var_dump($idByTable);

			if ($this->MULTI_LANG == 1) {
				foreach ($LANGS as $lang => $langName) {
					$queryLang[$table][$lang] = 'INSERT INTO `' . $table . '_info` SET ';
				}
			}
		}

		$i = 0;
		while (isset($this->fld[$i])) {
			if ($this->fld[$i]->name == 'update_time') {
				$this->fld[$i]->val = date('U');
			} elseif (empty($this->fld[$i]->val) && !isset($_POST[$this->fld[$i]->name])) {
				$i++;
				continue;
			}

			// external fields
			if ($this->fld[$i]->multi_lang == 1) {
				$toTable = isset($this->fld[$i]->extra_param['fromTable'])?$this->fld[$i]->extra_param['fromTable']:$this->TABLE;

				foreach ($LANGS as $lang => $langName) {
					$val = $this->fld[$i]->val[$lang];

					if (($this->fld[$i]->type) == 1) {
						//$val = str_replace(array("'",'&quot;'), '"', $val);
					} elseif (($this->fld[$i]->type) == 2) {
						$val = str_replace(["'", '&quot;'], ['`', '"'], $val);
						$val = preg_replace('/lang="(.*)"/U','',$val);
						$val = preg_replace('/<!--(.*)-->/U','',$val);
						$val = str_ireplace("\<\/o:p\>",'',$val);
						$val = str_ireplace("\<o:p\>",'',$val);

						$val = addslashes($val);
					} elseif (($this->fld[$i]->type) == 7) {
						$val = str_ireplace("\n",'<br/>',$val);
						$val = addslashes($val);
					} elseif (($this->fld[$i]->type) == 16) {
						$val = addslashes($val);
					}

				//
					if (!empty($val)) {
						$queryLangItems[$toTable][$lang][] = '`' . $this->fld[$i]->name . "` = '" . $val . "'";
					}
				}

				$i++;
				continue;
			}

			if (($this->fld[$i]->type) == 0) {
				$this->fld[$i]->val = str_replace(',','.',$this->fld[$i]->val);
			}
			//if (($this->fld[$i]->type)==1) $this->fld[$i]->val=str_replace(array("'",'&quot;'),'"',$this->fld[$i]->val);
			if (($this->fld[$i]->type) == 2) {
				$this->fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $this->fld[$i]->val);
				//$this->fld[$i]->val=preg_replace("/style=\"(.*)\"/U","",$this->fld[$i]->val);
				//$this->fld[$i]->val=preg_replace("/class=\"(.*)\"/U","",$this->fld[$i]->val);
				$this->fld[$i]->val = preg_replace('/lang="(.*)"/U','',$this->fld[$i]->val);
				$this->fld[$i]->val = preg_replace('/<!--(.*)-->/U','',$this->fld[$i]->val);
				$this->fld[$i]->val = str_ireplace("\<\/o:p\>",'',$this->fld[$i]->val);
				$this->fld[$i]->val = str_ireplace("\<o:p\>",'',$this->fld[$i]->val);

				$this->fld[$i]->val = addslashes($this->fld[$i]->val);
			}
			if (($this->fld[$i]->type) == 7 && !isset($this->TEXTARR_NO_BR)) {
				$this->fld[$i]->val = str_replace(["'", '&quot;'], ['`', '"'], $this->fld[$i]->val);
				$this->fld[$i]->val = str_ireplace("\n",'<br/>',$this->fld[$i]->val);
			}

			//$this->fld[$i]->val = addslashes($this->fld[$i]->val);
			if (!empty($this->fld[$i]->name) && ($this->fld[$i]->type) != 5 && ($this->fld[$i]->type) != 8 && ($this->fld[$i]->type) != 14) {
				$val = addslashes($this->fld[$i]->val);
				$queryItems[] = '`' . $this->fld[$i]->name . "`='" . $val . "'";
			}

			$i++;
		}
		$query .= implode(',', $queryItems) . " WHERE $idname='$id'";

		//echo $query;

		pdoExec($query);

		if ($this->MULTI_LANG == 1) {
			foreach ($queryLangItems as $table => $tableItems) {
				foreach ($LANGS as $lang => $langName) {
					if (!empty($tableItems[$lang])) {
						$queryLang[$table][$lang] .= implode(',', $tableItems[$lang]) . ", record_id = '" . $idByTable[$table] . "', lang = '" . $lang . "'
				ON DUPLICATE KEY UPDATE " . implode(',', $tableItems[$lang]);

						//echo '<pre>'.$queryLang[$table][$lang] .'</pre>';

						pdoExec($queryLang[$table][$lang]);
					}
				}
			}
		}

		return $query;
	}
}
