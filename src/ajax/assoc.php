<?
/*
 * 25.02.2015 - Items ONLY assoc added
 * */
error_reporting(E_ALL);

header("Pragma: no-cache");
header('Cache-Control: no-cache');

if (!empty($_REQUEST['tablea'])) {
    $tablea = $_REQUEST['tablea'];
 
    $className = "admin_$tablea";
    
    if (class_exists($className)) {
		
        $rubsAssoc = new $className();
        
        $tabler = $rubsAssoc->tableRubs;
        $col_rec = $rubsAssoc->colRecord;
        $col_under = $rubsAssoc->colUnder;

    }
    else { 
        //echo '! ' . $className . ' not defined!!! ';
        $col_rec = $_REQUEST['col_rec'];
        $col_under = $_REQUEST['col_under'];
    }
}

if (!empty($_REQUEST['tableItemsAssoc'])) {
	$tableItemsAssoc = $_REQUEST['tableItemsAssoc'];
    $className = "admin_$tableItemsAssoc";
    
    if (class_exists($className)) {
        $itemsAssoc = new $className();
    }
    else 
        echo '! ' . $className . ' not defined!!! ';
    
}


$jfname=$_REQUEST['jfname'];
$col_sort=isset($_REQUEST['col_sort'])?$_REQUEST['col_sort']:NULL;
$under = $_REQUEST['under'];

if (isset($itemsAssoc)) {
    $tablei = $itemsAssoc->tableItems;
    $className = "admin_$tablei";
        
    if (class_exists($className)) {
        ${"admin_$tablei"} = new $className();
    }
}

if (!empty($tabler)) {
	$className = "admin_$tabler";
	if (class_exists($className)) {
        ${$className} = new $className();
    } else {
		echo 'No class ' . $className;
	}
    
    $rubsLangJoin = genLangJoin($tabler, ${'admin_'.$tabler});
    
    ${"admin_$tabler"}->SORT=str_replace("%20"," ",${"admin_$tabler"}->SORT);

	if (isset(${"admin_$tabler"}->ECHO_NAME)) {
		$echo_rub_name = ${"admin_$tabler"}->ECHO_NAME;
	} /*elseif (${"admin_$tabler"}->MULTI_LANG == 1) {
		$echo_rub_name = ${"admin_$tabler"}->ECHO_NAME . '_1';
	}*/
	else $echo_rub_name="name_1";

	if (isset(${"admin_$tabler"}->FIELD_UNDER)) $field_under_r = ${"admin_$tabler"}->FIELD_UNDER;
	else $field_under_r="under";
}

if (!empty($tablei)) {
	if (isset(${"admin_$tablei"}->FIELD_UNDER)) $field_under_i = ${"admin_$tablei"}->FIELD_UNDER;
	else $field_under_i="under";

	if (isset(${'admin_'.$tablei}->ALT_ID)) $idname=${'admin_'.$tablei}->ALT_ID;
	else $idname='id';

	if (isset(${'admin_'.$tablei}->ECHO_NAME)) $echo_item_name=${'admin_'.$tablei}->ECHO_NAME;
	else $echo_item_name='name_1';
}

if (isset($tableItemsAssoc)) {
    // Adding item
    if ($_GET['addrub'][0] == 'i') {
        
        $addId = intval(str_replace('i', '', $_GET['addrub']));
        
        if ($addId > 0) {
            $qa="INSERT INTO ".$tableItemsAssoc." (`".$itemsAssoc->colRecord."`,`".$itemsAssoc->colUnder."`) 
            VALUES ('".$id."','".$addId."')";
            mQuery($qa);
            if (mError()) echo $qa.'->'.mError();
        }
        
        // Rub auto adding
        if (isset($tablea)) {
            $rowsItem = pdoFetchAll("SELECT * FROM $tablei WHERE id = $addId"); 
            $rowItem = $rowsItem[0];
            unset($rowsItem);
        
            $_GET['addrub'] = $rowItem[$field_under_i];
        }
        
        
    }
    
    if ($_GET['delrub'][0] == 'i') {
        
        $delId = intval(str_replace('i', '', $_GET['delrub']));
        
        if ($delId > 0) {
            $qa="DELETE FROM ".$tableItemsAssoc." 
                WHERE " . $itemsAssoc->colRecord . " = " . $id . " 
                AND " . $itemsAssoc->colUnder . " = " . $delId;
            mQuery($qa);
            if (mError()) echo $qa.'->'.mError();
        }
        
    }
} // ItemsAssoc end

if (isset($tablea) && $id > 0)
{
	//echo 'ok'.$tablei.':'.$tabler.$id.$_GET['addrub'];
	if (($_GET['addrub'][0]!='i' && $_GET['addrub']!='0' && $_GET['addrub']!='undefined') && $id>0) //&& !($tablei==$tabler && $id==$_GET['addrub'])
	{
		//echo '+'.$_GET['addrub'];
		$qa="INSERT IGNORE INTO ".$tablea." (`$col_rec`,`$col_under`) VALUES ('".$id."','".$_GET['addrub']."')";
		mQuery($qa);
		if (mError()) echo $qa.'->'.mError();
        
        if (!empty($itemsAssoc->childsAutoAssoc) && $itemsAssoc->childsAutoAssoc == true) {
           // echo 'Childs!!! ';
            $q = "SELECT id FROM $tablei 
                                    WHERE `$field_under_i`='".$_GET['addrub']."'";
            $items = pdoFetchAll($q);
            foreach($items as $item) {
                $qa = "INSERT INTO ".$tableItemsAssoc." (`".$itemsAssoc->colRecord."`,`".$itemsAssoc->colUnder."`) 
                VALUES ('".$id."','".$item['id']."')";
                mQuery($qa);
                }
        }
	}
	
    if ($_GET['delrub'][0]!='i' && $_GET['delrub']!='0' && $_GET['addrub']!='undefined' && $id>0)
	{
		//echo '-'.$_GET['delrub'];
		mQuery("DELETE FROM ".$tablea." WHERE $col_under='".$_GET['delrub']."' AND $col_rec='".$id."'");
        
        // Items auto delete
        if (isset($tableItemsAssoc)) {
            
                $qa="DELETE FROM ".$tableItemsAssoc." 
                    WHERE " . $itemsAssoc->colRecord . " = " . $id . " 
                    AND " . $itemsAssoc->colUnder . " IN 
                        (SELECT id FROM $tablei WHERE $field_under_i = ".$_GET['delrub'].")";
                pdoExec($qa);

        }
        
	}
}

/*
 * Printing already assoced rubrics and items
 * */
 
$tdbg="";//"bgcolor=\"#c6d1de\"";
echo '<table class="table">';
if ($id > 0) {
    
	if (isset($rubsAssoc->extraFields)) {
        $ssort=", admin_assoc.$col_rec as arec, admin_assoc.$col_under as aunder, admin_assoc.".$rubsAssoc->extraFields[0]['name']." as a_".$rubsAssoc->extraFields[0]['name']." ";
    }
    elseif ($col_sort!='') 
        $ssort=", admin_assoc.$col_rec as arec, admin_assoc.$col_under as aunder, admin_assoc.$col_sort as asort ";
	else 
        $ssort="";
    
	
	if (!empty($tabler)) {
    $table = $tabler;
	
	if (isset($tablea)) {
        $query="SELECT * $ssort FROM ".$tablea." as admin_assoc 
            LEFT JOIN $table " . $rubsLangJoin . " ON  ($table.id=admin_assoc.$col_under) 
            WHERE admin_assoc.$col_rec=$id 
            ORDER by ".($col_sort!=''?'admin_assoc.'.$col_sort.' desc':'id asc');
        }
        elseif (!empty($tabler) && isset($tableItemsAssoc)) {
            $query="SELECT $tabler.* $ssort FROM $tabler 
            WHERE id IN (SELECT $tablei.".$field_under_i." FROM " . $tableItemsAssoc . " as admin_assoc 
                    LEFT JOIN $tablei ON  ($tablei.id = admin_assoc.".$itemsAssoc->colUnder.") 
                    WHERE admin_assoc.".$itemsAssoc->colRecord." = $id)
                            
            ORDER by ".($col_sort!=''?'admin_assoc.'.$col_sort.' desc':'id asc');
        }

        
	//echo $query;
	$result = mQuery($query);
    echo mError();
	$num = mNumRows($result);
	
	// Rubs
    for ($i=0; $i<$num;$i++) {

		$row = mFetchArray($result);
		$row[$echo_rub_name]=stripslashes($row[$echo_rub_name]);
		
		if (isset($row[$field_under_r]) && $row[$field_under_r] > 0) 
            
            $row[$echo_rub_name] = RubName($tabler,$row[$field_under_r]) . ' / ' . $row[$echo_rub_name];
		
		if (empty($row[$field_under_r])) {
            $row[$col_under]='0';
            $row[$field_under_r] = 0;
		}
        echo "<tr $tdbg><td  align=center><i class=\"glyphicon glyphicon-folder-close\" style=\"color:#FFB800\"></i></td><td style=\"font-size:14px;\">";
		
        echo "<a href=\"javascript:$jfname(".$row['id'].",0,0)\">";
		echo $row[$echo_rub_name];
		echo "</a>";
		
        echo "</td>";
        
        
        if (isset($rubsAssoc)) {
            $extraCols = "";
            if (isset($rubsAssoc->extraFields))
                foreach ($rubsAssoc->extraFields as $eField) {
                    $fid = $eField['name'].'_'.$row['arec'].'_'.$row['aunder'];
                    $extraCols .= '<input id="'.$fid.'" value="'.$row['a_'.$eField['name']].'" title="'.$eField['name'].'" placeholder="'.$eField['placeholder'].'" onkeyup="updateItemVal2(\''.$tablea.'\',\''.$eField['name'].'\',\''.$fid.'\',\''.$col_rec.'='.$row['arec'].' AND '.$col_under.'='.$row['aunder'].'\');" style="width: 40px;" type="text">';
                }
                
               echo '<td width=45>'.$extraCols.'</td>';
		 
        
        }
        else 
            echo "<td>#$row[id]</td>";
        
        echo "<td width=45>";
		if (isset($tablea)) echo "	<a href=\"javascript:$jfname('".($under!=0?$row[$field_under_r]:0)."',0,'$row[id]')\">убрать</a>";
		echo "</td>";
        
        
        

		if ($col_sort!='') 
            echo '<td width=45><input name="sort'.$row['arec'].$row['aunder'].'" id="sort'.$row['arec'].$row['aunder'].'" value="'.$row['asort'].'" onkeyup="updateItemVal2(\''.$tablea.'\',\''.$col_sort.'\',\'sort'.$row['arec'].$row['aunder'].'\',\''.$col_rec.'='.$row['arec'].' AND '.$col_under.'='.$row['aunder'].'\');" style="width: 40px;" type="text"></td><td align="center"></td>';
		echo "</tr>";
		
        // Items
        if (!empty($tablei) && !empty($tableItemsAssoc)) {
            $query="SELECT $tablei.* FROM " . $tableItemsAssoc . " as admin_assoc 
                    LEFT JOIN $tablei ON  ($tablei.id = admin_assoc.".$itemsAssoc->colUnder.") 
                    WHERE admin_assoc.".$itemsAssoc->colRecord." = $id 
                            AND $tablei.$field_under_i = " . $row['id'] . "
                    ORDER by ".($col_sort!=''?'admin_assoc.'.$col_sort.' desc':'id asc');
            
            $resulti = mQuery($query);
            echo mError();
            $numi = mNumRows($resulti);
            
            for ($i2=0; $i2<$numi;$i2++) {
                $rowi = mFetchArray($resulti);
                $rowi[$echo_item_name] = stripslashes($rowi[$echo_item_name]);
                
                //print_r($rowi);
                
                //if (!(abs($row[$field_under_r])>0))$row[$col_under]='0';
                echo "<tr $tdbg><td align=center>".imfile."</td><td>";
                echo $rowi[$echo_item_name];            
                echo "</td><td>#$rowi[id]</td><td width=45>
                    <a href=\"javascript:$jfname('".($under!=0?$rowi[$field_under_i]:0)."',0,'i$rowi[id]')\">убрать</a>
                </td>";
                if ($col_sort!='') 
                    echo '<td width=45><input name="sort'.$row['arec'].$row['aunder'].'" id="sort'.$row['arec'].$row['aunder'].'" value="'.$row['asort'].'" onkeyup="updateItemVal2(\''.$tablea.'\',\''.$col_sort.'\',\'sort'.$row['arec'].$row['aunder'].'\',\''.$col_rec.'='.$row['arec'].' AND '.$col_under.'='.$row['aunder'].'\');" style="width: 40px;" type="text"></td><td align="center"></td>';
                echo "</tr>";
            }
        }
        
        }
    } 
    // Items only
    elseif (!empty($tablei)) {
                // Items
        if (!empty($tableItemsAssoc)) {
            $query="SELECT $tablei.* FROM " . $tableItemsAssoc . " as admin_assoc 
                    LEFT JOIN $tablei ON  ($tablei.id = admin_assoc.".$itemsAssoc->colUnder.") 
                    WHERE admin_assoc.".$itemsAssoc->colRecord." = $id 
                    ORDER by ".($col_sort!=''?'admin_assoc.'.$col_sort.' desc':'id asc');
            
            $resulti = mQuery($query);
            echo mError();
            $numi = mNumRows($resulti);
            
            for ($i2=0; $i2<$numi;$i2++) {
                $rowi = mFetchArray($resulti);
                $rowi[$echo_item_name] = stripslashes($rowi[$echo_item_name]);
                
                //print_r($rowi);
                if (!empty($rowi['preview_name']))
                {
                    $image='<img src="'.$pref_url.$FOLDER_FILES.'/preview/'.$rowi['preview_name'].'" border="0" width="25">';
                }
                elseif (is_file($pref.$FOLDER_IMAGES.'/'.$tablei.'/'.$rowi['id'].'.1.s.jpg'))
                {
                    $image='<img src="'.$pref_url.$FOLDER_IMAGES.'/'.$tablei.'/'.$rowi['id'].'.1.s.jpg" border="0"  width="24">';
                }
                else
                if (!empty($rowi['format']))
                {
                    if (is_file('img/filetypes/'.$rowi['format'].'.gif')) $image='<img src="img/filetypes/'.$rowi['format'].'.gif" border="0"  width="24">';
                }
                else $image=imfile;
        
                //if (!(abs($row[$field_under_r])>0))$row[$col_under]='0';
                echo "<tr $tdbg><td align=center>" . $image . "</td><td>";
                echo $rowi[$echo_item_name];            
                echo "</td><td>#$rowi[id]</td><td width=45>
                    <a href=\"javascript:$jfname('".($under!=0?$rowi[$field_under_i]:0)."',0,'i$rowi[id]')\">убрать</a>
                </td>";
                //if ($col_sort!='') 
                  //  echo '<td width=45><input name="sort'.$row['arec'].$row['aunder'].'" id="sort'.$row['arec'].$row['aunder'].'" value="'.$row['asort'].'" onkeyup="updateItemVal2(\''.$tablea.'\',\''.$col_sort.'\',\'sort'.$row['arec'].$row['aunder'].'\',\''.$col_rec.'='.$row['arec'].' AND '.$col_under.'='.$row['aunder'].'\');" style="width: 40px;" type="text"></td><td align="center"></td>';
                echo "</tr>";
            }
        }
    }
		echo "<tr $tdbg><td align=center></td><td><hr></td></tr>";
}

if (!empty($tabler)) {	
	
	$shownumr = 300;
	if (!($rpage>0)) $rpage=0;
	$startsr=$rpage*$shownumr;
	
	if ($under>0) 
	{
		$query="SELECT * FROM $tabler " . $rubsLangJoin . " WHERE `id`='$under' LIMIT 1";
		//echo $query;
		$result = mQuery($query);
		$row=mFetchArray($result);
		
        if (!($row[$field_under_r] > 0 || $row[$field_under_r] == -1)) 
            $row[$field_under_r] = 0;
            
		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-folder-open\" style=\"color:#FFB800\"></i></td><td><b><font color=#ff0000>".stripslashes($row[$echo_rub_name])."</font></b></td></tr>";
		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-level-up\" style=\"color:#FFB800\"></i></td><td><a href=\"javascript:$jfname(".$row[$field_under_r].",0,0)\"><b>/..</b></a></td><td></td></tr>";
		}
	else echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-folder-open\" style=\"color:#FFB800\"></i></td><td><b><font color=#ff0000>".$word[$ALANG]['root']."</font></b></td></tr>";

	//Если итемов нету, то скрываем рубрики
	if (empty($tablei)) $whua="id NOT IN (SELECT $col_under FROM $tablea WHERE $col_rec='$id')";
	//Если есть Итемсы - значит рубрики не скрывать!
	else $whua='id>0';
	
	if ($under != 0) $whu="WHERE `$field_under_r`='$under' AND $whua";
	// Rubrics assos exists
	elseif (isset($tablea)) {
		$whu="WHERE id NOT IN (SELECT $col_under FROM $tablea WHERE $col_rec='$id')";
	}
	else {
		$whu="";
	}
	
	
	if(empty(${"admin_$tabler"}->FIELD_UNDER) && empty($itemsAssoc->FIELD_UNDER)) {
		$query="SELECT tr.* FROM $tabler tr $whu ORDER BY ".${"admin_$tabler"}->SORT." LIMIT $startsr,$shownumr";
	} else {

		$query="SELECT *,
  (SELECT count(id)
   FROM $tabler tr2
   WHERE tr2.$field_under_r = " . $tabler . ".id) AS under_count
FROM $tabler ".$rubsLangJoin." $whu
ORDER BY ".${"admin_$tabler"}->SORT." LIMIT $startsr,
                                            $shownumr";
	}
	//$query="SELECT tr.*, (SELECT count(id) FROM $tabler tr2 WHERE tr2.$field_under_r = tr.id) as under_count FROM $tabler tr $whu ORDER BY ".${"admin_$tabler"}->SORT." LIMIT $startsr,$shownumr";
	//echo $query;
	$result = mQuery($query);
	if (mError() != '') {
		echo "<br/>" . $query . "->" . mError() . "!<br/>";
	}
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{

		$row=mFetchArray($result);

		if (!empty($row[$echo_rub_name])) $row[$echo_rub_name]=stripslashes($row[$echo_rub_name]);
		if (!isset($row[$field_under_r]) || !($row[$field_under_r]>0 || $row[$field_under_r] == -1)) 
            $row[$field_under_r]='0';
		
        if (isset($tablea)) {
            $queryp="SELECT * FROM ".$tablea." WHERE `$col_rec`='$id' AND $col_under=$row[id]";
            //echo $queryp;
            $resultp = mQuery($queryp);
            $nump = mNumRows($resultp);
        }
        // No rub assoc
        else {
            $nump = 1;
        }
        
        // If rec exists - no add! 
        if ($nump > 0) 
            $plus = "";
		// No rec - can add!
        else 
            $plus= "<strong><a href=\"javascript:$jfname('".($under!=0?$row[$field_under_r]:0)."','$row[id]',0)\">добавить</a></strong>";
		
		echo "<tr $tdbg><td align=center><i class=\"glyphicon glyphicon-folder-close\" style=\"color:#FFB800\"></i></td><td style=\"font-size:14px;\">";
        echo "<a href=\"javascript:$jfname('".$row['id']."',0,0)\">";
        //else echo 'r=i ';
        echo $row[$echo_rub_name];
        echo "</a>";
        echo "</td><td>#$row[id]</td><td width=45>$plus</td></tr>";
		}


//echo $_GET['addrub'].' '.$tablea.' '.$id;
//Pages
$pages="";
	
	$query="SELECT * FROM $tabler $whu ORDER BY ".${"admin_$tabler"}->SORT;

	//echo $query;
$result = mQuery($query);
//echo mError();
$num=mNumRows($result);

for ($i=0; $i<ceil($num/$shownumr);$i++)
{
	if (($rpage)==$i) $pages.=" <strong>".($i+1)."</strong> ";
	else $pages.=" <a href=\"javascript:$jfname('".$row['under']."','0','0',$i)\">".($i+1)."</a> ";
}
if (ceil($num/$shownumr)>1) echo "<tr><td></td><td><br>$pages</td><td colspan=3></td></tr>";
}

if (!empty($tablei) && $tablei != $tabler)
{	
	if (!empty($field_under_i) && $under != 0) 
        $query="SELECT * FROM $tablei WHERE `$field_under_i`='$under' ORDER BY id LIMIT 150";
	else 
        $query="SELECT * FROM $tablei ORDER BY id LIMIT 150";
    
    echo $query;
        
	$result = mQuery($query);
	$num=mNumRows($result);
	if ($num) for ($i=0; $i<$num;$i++)
		{
		$row=mFetchArray($result);
		
		$row[$echo_item_name] = stripslashes($row[$echo_item_name]);
		
		if (!empty($row['preview_name']))
		{
			$image='<img src="'.$pref_url.$FOLDER_FILES.'/preview/'.$row['preview_name'].'" border="0" width="25">';
		}
		elseif (is_file($pref.$FOLDER_IMAGES.'/'.$tablei.'/'.$row['id'].'.1.s.jpg'))
		{
			$image='<img src="'.$pref_url.$FOLDER_IMAGES.'/'.$tablei.'/'.$row['id'].'.1.s.jpg" border="0"  width="24">';
		}
		else
		if (!empty($row['format']))
		{
			if (is_file('img/filetypes/'.$row['format'].'.gif')) $image='<img src="img/filetypes/'.$row['format'].'.gif" border="0"  width="24">';
		}
		else $image=imfile;
		
		
		$queryp="SELECT * FROM ".$tableItemsAssoc." WHERE `".$itemsAssoc->colRecord."` = $id AND ".$itemsAssoc->colUnder." = $row[id]";
		//echo $queryp;
		$resultp = mQuery($queryp);
		$nump=mNumRows($resultp);
		
        // If rec exists - no add! 
        if ($nump > 0) 
            $plus="";
		// No rec - can add!
        else 
            $plus="<a href=\"javascript:$jfname(".($under!=0?$row[$field_under_i]:0).",'i$row[id]',0)\">добавить</a>";
		
		echo "<tr $tdbg><td align=center>".$image."</td><td>$row[$echo_item_name]</td><td>#$row[id]</td><td width=45>$plus</td></tr>";
		}
}


echo "</table>";



?>

