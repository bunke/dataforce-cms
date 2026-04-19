<?php

header('Pragma: no-cache');
header('Cache-Control: no-cache');

//echo "listcat";

if (!empty($tabler)) {
	${"admin_$tabler"}->SORT = str_replace('%20',' ',${"admin_$tabler"}->SORT);

	if (isset(${"admin_$tabler"}->ECHO_NAME)) {
		$echo_rub_name = ${"admin_$tabler"}->ECHO_NAME;
	} else {
		$echo_rub_name = 'name_1';
	}

	if (${"admin_$tabler"}->MULTI_LANG) {
		$echo_rub_name_row = $echo_rub_name . '_1';
	} else {
		$echo_rub_name_row = $echo_rub_name;
	}

	if (isset(${"admin_$tabler"}->SHOW_NUM)) {
		$shownumr = ${"admin_$tabler"}->SHOW_NUM;
	} else {
		$shownumr = 25;
	}

	if (isset(${'admin_' . $tabler}->FIELD_UNDER)) {
		$under_name_r = ${'admin_' . $tabler}->FIELD_UNDER;
	} else {
		$under_name_r = 'under';
	}
}

$tdbg = ''; //"bgcolor=\"#c6d1de\"";
echo '<table class="table table-condensed">
<tbody>';

$levelOfRub = 0;

//Rubs
if (!empty($tabler)) {
	$langJoinR = genLangJoin($tabler);

	if (!($rpage > 0)) {
		$rpage = 0;
	}
	if (!($ipage > 0)) {
		$ipage = 0;
	}

	$startsr = intval($rpage) * $shownumr;

	if (isset(${"admin_$tabler"}->SP_WHERE_AND)) {
		$sp_wh_and = ${"admin_$tabler"}->SP_WHERE_AND;
	} else {
		$sp_wh_and = '';
	}

	// Access
	if (!empty($tabler) && $under == -1 && method_exists(${'admin_' . $tabler}, 'getRoot')) {
		$under = ${'admin_' . $tabler}->getRoot();
	}

	if ($under > 0) {
		$levelOfRub = 3;

		$row = FetchID($tabler, $under);

		if (!empty(${"admin_$tabler"}->RUBS_NO_UNDER) || $row[$under_name_r] == -1) {
			$levelOfRub = 1;
		} elseif ($row[$under_name_r] > 0) {
			$rowu = FetchID($tabler, $row[$under_name_r]);
			if ($rowu[$under_name_r] == -1) {
				$levelOfRub = 2;
			} elseif ($row[$under_name_r] > 0) {
				$rowuu = FetchID($tabler, $rowu[$under_name_r]);
				if ($rowuu[$under_name_r] == -1) {
					$levelOfRub = 3;
				}
			}
		}

		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-folder-open\"></i></td><td colspan=2><b><font color=#ff0000>" . stripslashes($row[$echo_rub_name_row]) . '</font></b></td><td colspan=2></td></tr>';
		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-level-up\"></i></td><td><a href=\"javascript:ListCat('$tabler','$tablei'," . (isset($row[$under_name_r])?$row[$under_name_r]:'-1') . ',0)"><b>/..</b></a></td><td colspan=3></td></tr>';
	} else {
		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-folder-open\"></i></td><td colspan=2><b><font color=#ff0000>" . $word[$ALANG]['root'] . '</font></b></td><td colspan=3></td></tr>';
	}

	if ((empty(${"admin_$tabler"}->RUBS_NO_UNDER) && !empty($under))
		|| (!empty(${"admin_$tabler"}->RUBS_NO_UNDER) && $under < 1)) {
		$query = 'SELECT *,'
  . (empty(${"admin_$tabler"}->RUBS_NO_UNDER)?"(SELECT count(id)
   FROM $tabler AS t2
   WHERE t2.$under_name_r=$tabler.id) ":'0 ')
   . "AS subnum
FROM $tabler $langJoinR
WHERE "
. (empty(${"admin_$tabler"}->RUBS_NO_UNDER)?"`$under_name_r`='$under'":"id != ''")
. " $sp_wh_and
ORDER BY " . ${"admin_$tabler"}->SORT . " LIMIT $startsr,
                                            $shownumr";
		//echo $query;
		$result = mQuery($query);
		//echo $query.mError();
		$num = mNumRows($result);

		//add new rub
		if (!is_array(${"admin_$tabler"}->NAME2) || isset(${"admin_$tabler"}->NAME2[$levelOfRub])) {
			echo "<tr $tdbg><td align=center><a><i class=\"glyphicon glyphicon-plus\"></i></a></td><td><a href=catalog.php?tabler=$tabler&tablei=$tablei&id=&under=$under&addid=$under>" . $word[$ALANG]['insertrub'] . '</a></td><td colspan=3></td></tr>';
		}

		if ($num) {
			for ($i = 0; $i < $num; $i++) {
				$row = mFetchArray($result);
				if (isset($row[$echo_rub_name])) {
					$row[$echo_rub_name] = stripslashes($row[$echo_rub_name]);
				}

				if (empty($_SESSION['admin']['group']['del_restrict']) && (!isset($row['no_del']) || (isset($row['no_del']) && $row['no_del'] != 1))) {
					$del_pict = "<a onClick=\"confirming('Delete  " . $row[$echo_rub_name] . "?','catalog.php?tabler=$tabler&tablei=$tablei&srci=items.php" . (isset($row['under'])?'&under=' . $row['under']:'') . "&del=$row[id]')\" href=#><i class=\"glyphicon glyphicon-trash\"></i></a>";
				} else {
					$del_pict = '';
				} //<i class="glyphicon glyphicon-trash"></i>

				echo "<tr $tdbg><td align=center><a href=\"javascript:ListCat('$tabler','$tablei',$under," . ($row['id'] == $sub_under?0:$row['id']) . ",$rpage,$ipage)\">" . ($row['subnum'] > 0?'<i class="glyphicon glyphicon-th-list"></i>':'<i class="glyphicon glyphicon-folder-close"></i>') . '</a></td><td>';
				echo "<a href=\"javascript:ListCat('$tabler','$tablei',$row[id],0,$ipage)\">" . mb_substr($row[$echo_rub_name],0,50,'utf-8') . "</a></td><td>#$row[id]</td>";
				/*if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && !isset(${"admin_$tablei"}->TABLE_UNDER_DOP)) echo "<td width=15><a href=\"javascript:setRub($row[id])\">&larr;</a></td>";
				else echo "<td width=15><a href=\"javascript:setInput('under','$row[id]')\">&larr;</a></td>";*/
				echo "<td width=45><a href=catalog.php?tabler=$tabler&tablei=$tablei&srci=items.php&id=$row[id]" . (isset($row['under'])?'&under=' . $row['under']:'') . "><i class=\"glyphicon glyphicon-edit\"></i></a> $del_pict</td></tr>";

				//Expanding
				if ($row['id'] == $sub_under && !isset(${'admin_' . $tabler}->RUBS_NO_UNDER)) {
					$query2 = "SELECT * FROM $tabler $langJoinR WHERE `$under_name_r`='" . $row['id'] . "' ORDER BY " . ${"admin_$tabler"}->SORT . ' LIMIT 100';
					//echo $query2;
					$result2 = mQuery($query2);
					$num2 = mNumRows($result2);
					//echo $num2;

					if ($num2) {
						for ($i2 = 0; $i2 < $num2; $i2++) {
							$row2 = mFetchArray($result2);
							$row2[$echo_rub_name] = stripslashes($row2[$echo_rub_name]);

							if (!isset($row2['no_del']) || (isset($row2['no_del']) && $row2['no_del'] != 1)) {
								$del_pict = "<a onClick=\"confirming('Delete  " . $row2[$echo_rub_name] . "?','catalog.php?tabler=$tabler&tablei=$tablei&srci=items.php&under=" . $row2[$under_name_r] . "&del=$row2[id]')\" href=#><i class=\"glyphicon glyphicon-trash\"></a>";
							} else {
								$del_pict = im_del_folder0;
							}

							echo "<tr $tdbg><td align=right>" . im_tree_ugol . "</td><td> <a href=\"javascript:ListCat('$tabler','$tablei',$row2[id],0,0,$ipage)\">" . mb_substr($row2[$echo_rub_name],0,50,'utf-8') . "</a></td><td>#$row2[id]</td>";
							/*if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && !isset(${"admin_$tablei"}->TABLE_UNDER_DOP)) echo "<td width=15><a href=\"javascript:setRub($row2[id])\">&larr;</a></td>";
							else echo "<td width=15><a href=\"javascript:setInput('under','$row2[id]')\">&larr;</a></td>";*/
							echo "<td width=45><a href=catalog.php?tabler=$tabler&tablei=$tablei&srci=items.php&id=$row2[id]&under=" . $row2[$under_name_r] . "><i class=\"glyphicon glyphicon-edit\"></i></a> $del_pict</td></tr>";
						}
					}
				}
			}
		}
	}

	//Pages
	$pages = '';

	$query = "SELECT * FROM $tabler WHERE " . (empty(${"admin_$tabler"}->RUBS_NO_UNDER)?"`$under_name_r`='$under'":"id != ''") . " $sp_wh_and ORDER BY " . ${"admin_$tabler"}->SORT;

	//echo $query;
	$result = mQuery($query);
	//echo mError();
	$num = mNumRows($result);

	for ($i = 0; $i < intval($num) / intval($shownumr); $i++) {
		if (($rpage) == $i) {
			$pages .= '<li><strong>' . ($i + 1) . '</strong></li>';
		} else {
			$pages .= "<li><a href=\"javascript:ListCat('$tabler','$tablei',$under,$sub_under,$i,$ipage)\">" . ($i + 1) . '</a></li>';
		}
	}
	echo '</tbody></table>';
	if (intval($num) / intval($shownumr) > 1) {
		echo '<nav class="text-center">
                    <ul class="pagination">' . $pages . '</ul>
          </nav>';
	}
}

//ITEMS
if (isset($tablei) && $tablei != '') {
	$langJoin = genLangJoin($tablei);

	if (isset(${"admin_$tablei"}->SP_WHERE_AND)) {
		$sp_wh_and = ${"admin_$tablei"}->SP_WHERE_AND;
	} else {
		$sp_wh_and = '';
	}

	//echo 'SP = ' . $sp_wh_and;

	if (isset(${"admin_$tablei"}->SHOWNUM)) {
		$shownum = ${"admin_$tablei"}->SHOWNUM;
	} else {
		$shownum = 25;
	}

	if (isset(${'admin_' . $tablei}->FIELD_UNDER)) {
		$under_name_i = ${'admin_' . $tablei}->FIELD_UNDER;
	} else {
		$under_name_i = 'under';
	}

	$starts = intval($ipage) * $shownum;

	if (!isset(${"admin_$tablei"}->SORT) || ${"admin_$tablei"}->SORT == '') {
		${"admin_$tablei"}->SORT = 'id desc';
	}

	$under_pos = getPosInFld($under_name_i,${"admin_$tablei"}->fld);

	if (($tabler != '' && $under && (!isset(${"admin_$tablei"}->TABLE_RUB_ASSOC))
		|| (isset(${"admin_$tablei"}->TABLE_UNDER_DOP) && ${"admin_$tablei"}->TABLE_UNDER_DOP != $tabler))) {
		$wh_under = "AND `$under_name_i`='$under'";
	} else if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && $under_pos > 0) {
		$wh_under = "`$under_name_i`='$under' OR";
	} else {
		$wh_under = '';
	}

	//echo 'whu='.$wh_under;

	if (isset(${'admin_' . $tablei}->ALT_ID)) {
		$idname = ${'admin_' . $tablei}->ALT_ID;
	} else {
		$idname = 'id';
	}

	if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$tablei"}->TABLE_UNDER_DOP) || ${"admin_$tablei"}->TABLE_UNDER_DOP == $tabler)) { //mnogo under
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

		$query = "SELECT $tablei.* FROM $tablei WHERE $wh_under id IN (SELECT $afid FROM " . ${"admin_$tablei"}->TABLE_RUB_ASSOC . " as admin_assoc WHERE admin_assoc.$afunder=$under) ORDER by  " . ${"admin_$tablei"}->SORT . " LIMIT $starts, $shownum";
		//echo $query;
	}
	//No assoc
	else {
		if ($under && $tabler != '') {
			$query = "SELECT * FROM $tablei $langJoin WHERE 1 $wh_under $sp_wh_and ORDER BY  " . ${"admin_$tablei"}->SORT . " LIMIT $starts, $shownum";
		} else {
			$query = "SELECT * FROM $tablei $langJoin WHERE 1 $sp_wh_and ORDER BY  " . ${"admin_$tablei"}->SORT . " LIMIT $starts, $shownum";
		}
	}

	$result = mQuery($query);
	//echo $query;

	if (isset(${"admin_$tablei"}->ECHO_NAME)) {
		$echo_item_name = ${"admin_$tablei"}->ECHO_NAME;
	} else {
		$echo_item_name = 'name_1';
	}

	echo mError();
	echo '<table class="table table-condensed">
<tbody>';
	//add new item
	if ($tablei != '' && $levelOfRub >= ${"admin_$tablei"}->LEVEL_OF_RUBS) {
		if (isset(${"admin_$tablei"}->TYPE_PARAM) && !empty($_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)])) {
			$typ = '&' . (${"admin_$tablei"}->TYPE_PARAM) . '=' . $_REQUEST[(${"admin_$tablei"}->TYPE_PARAM)];
		} else {
			$typ = '';
		}

		echo "<tr $tdbg><td align=center width=\"20\"><a><i class=\"glyphicon glyphicon-plus\"></i></a></td>
		<td width=\"220\"><a href=$act?tabler=$tabler&tablei=$tablei$typ&id=&under=$under>" . $word[$ALANG]['add'] . ' ' . (is_array(${"admin_$tablei"}->NAME2)?${"admin_$tablei"}->NAME2[0]:${"admin_$tablei"}->NAME2) . '</a></td><td colspan=3></td></tr>';
	}

	$num = mNumRows($result);
	if ($num) {
		for ($i = 0; $i < $num; $i++) {
			$row = mFetchArray($result);

			//var_dump($row);

			/*Preview image*/
			$image = '';

			$names = ${"admin_$tablei"}->getRowName($row);

			if (!empty($row['preview_name']) && is_file($pref . $FOLDER_FILES . '/preview/' . $row['preview_name'])) {
				$image = '<img src="' . $pref . $FOLDER_FILES . '/preview/' . $row['preview_name'] . '" border="0" width="25">';
			} elseif (!empty($row['format']) && is_file('img/filetypes/' . $row['format'] . '.gif')) {
				$image = '<img src="img/filetypes/' . $row['format'] . '.gif" border="0"  width="24">';
			} else {
				$image = '<i class="glyphicon glyphicon-file"></i>';
			}

			if (isset(${"admin_$tablei"}->IMG_FIELD)) {
				$img_id = $row[${"admin_$tablei"}->IMG_FIELD];
			} elseif (!isset(${"admin_$tablei"}->IMG_FIELD) && isset($row['crtdate'])) {
				$img_id = $row['crtdate'];
			} else {
				$img_id = NULL;
			}

			if ($img_id != NULL) {
				$folder = GetImageFolder($tablei,$img_id);

				//echo 'f='.$folder;

				if (is_file("$pref$FOLDER_IMAGES/$folder$img_id.1.s.jpg")) {
					$image = "<img src=\"$pref_url$FOLDER_IMAGES_FRONTEND/$folder$img_id.1.s.jpg\" width=\"25\" border=\"0\">";
				}
			}

			/************************************************/

			if (!empty(${"admin_$tablei"}->ECHO_ID)) {
				$echoID = $row[${"admin_$tablei"}->ECHO_ID];
			} else {
				$echoID = '#' . $row['id'];
			}

			if (!empty(${"admin_$tablei"}->ECHO_ID2)) {
				$echoID .= '_' . $row[${"admin_$tablei"}->ECHO_ID2];
			}

			if (!empty(${"admin_$tablei"}->ALSO)) {
				$also = "<strong><a href=\"javascript:insertToInput('also_ids',$row[id])\">+С</a></strong> <strong><a href=\"javascript:removeFromInput('also_ids',$row[id])\">-С</a></strong>";
			} else {
				$also = '';
			}

			if (empty($_SESSION['admin']['group']['del_restrict']) && (!isset($row['no_del']) || (isset($row['no_del']) && $row['no_del'] != 1))) {
				$del_item = "<a  onClick=\"confirming('" . $word[$ALANG]['del'] . "?','$act?tabler=$tabler&tablei=$tablei&id=$row[$idname]&del=1&under=$under')\" href=#><i class=\"glyphicon glyphicon-trash\"></i></a>";
			} else {
				$del_item = '';
			}

			echo "<tr $tdbg><td align=center><a href=$act?tabler=$tabler&table=$tablei&tablei=$tablei&id=$row[$idname]$typ&under=$under&ipage=$ipage#header>" . $image . "</a></td>
	<td><a href=$act?tabler=$tabler&tablei=$tablei&id=$row[$idname]$typ&under=$under&ipage=$ipage#header>" . mb_substr($names,0,50, 'utf-8') . "</a></td>
	<td>$echoID</td><td width=45>$also <a href=$act?tabler=$tabler&tablei=$tablei&id=$row[$idname]$typ&under=$under&ipage=$ipage#header><i class=\"glyphicon glyphicon-edit\"></i></a> $del_item</td></tr>";
		}
	}
	//Pages
	$pages = '';

	//under assoc
	if (isset(${"admin_$tablei"}->TABLE_RUB_ASSOC) && (!isset(${"admin_$tablei"}->TABLE_UNDER_DOP) || ${"admin_$tablei"}->TABLE_UNDER_DOP == $tabler)) {
		$query = "SELECT $tablei.* FROM " . ${"admin_$tablei"}->TABLE_RUB_ASSOC . " as admin_assoc LEFT JOIN  $tablei ON  ($tablei.id=admin_assoc.$afid) WHERE admin_assoc.$afunder=$under ORDER by  " . ${"admin_$tablei"}->SORT . '';
		//echo $query;
	} else {
		if (($under) && ($tabler != '')) {
			$query = "SELECT * FROM $tablei WHERE $sp_wh_and `$under_name_i`='$under' ORDER BY  " . ${"admin_$tablei"}->SORT . '';
		} else {
			$query = "SELECT * FROM $tablei WHERE 1 $sp_wh_and ORDER BY  " . ${"admin_$tablei"}->SORT . '';
		}
	}
	//echo $query;
	$result = mQuery($query);
	//echo mError();
	$num = mNumRows($result);

	for ($i = 0; $i < intval($num) / intval($shownum); $i++) {
		if (($ipage) == $i) {
			$pages .= '<li><strong>' . ($i + 1) . '</strong></li>';
		} else {
			$pages .= "<li><a href=\"javascript:ListCat('$tabler','$tablei',$under,$sub_under,$rpage,$i)\">" . ($i + 1) . '</a></li>';
		}
	}
	echo '</tbody>
</table>';
	if (intval($num) / intval($shownum) > 1) {
		echo '<nav class="text-center">
                    <ul class="pagination">' . $pages . '</ul></nav>';
	}
}
