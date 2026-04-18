<?php
/*
 * Generating menu block
 * */

$q = "SELECT $TABLE_ADMINS.*,$TABLE_ADMINS_GROUPS.id as group_id,$TABLE_ADMINS_GROUPS.deny_tables AS gdt 
FROM $TABLE_ADMINS JOIN $TABLE_ADMINS_GROUPS ON $TABLE_ADMINS_GROUPS.id=$TABLE_ADMINS.group_id 
WHERE $TABLE_ADMINS.name='" . $admin_name . "' AND passwd='" . $_SESSION['hash'] . "'";
$res = pdoFetchAll($q);
//echo $q.mError();
if (count($res) == 1) {
	$row_user = $res[0];
	//var_dump($row_user);

	$row_user['gdt'] = str_replace(' ', '', $row_user['gdt']);

	$gdenytables = explode(',', $row_user['gdt']);
} else {
	//var_dump(pdoError());
	echo 'NO VALID USER!';
}
?>
<nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/admin/" data-toggle="tooltip" data-placement="right" title="CMS: <?php echo $PROJECT_NAME; ?>">
                    <?php echo $PROJECT_NAME; ?>
                </a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
				

				<!--MENU STARTS-->
				<ul class="nav navbar-nav">
<?php
// Not SuperAdmin
if ($row_user['group_id'] > 1) {
	$whg = "AND id IN (SELECT menu_id FROM $TABLE_ADMINS_MENU_ASSOC WHERE group_id=$row_user[group_id])";
} else {
	$whg = '';
}

$q = "SELECT * FROM $TABLE_ADMINS_MENU WHERE under=-1 $whg ORDER BY sort DESC";
$res = pdoFetchAll($q);

foreach ($res as $rowm) {
	$q2 = "SELECT * FROM $TABLE_ADMINS_MENU WHERE under=$rowm[id] $whg ORDER BY sort DESC";
	$res2 = pdoFetchAll($q2);
	$num2 = count($res2);
	// /admin/
	echo '<li ' . ($num2 > 0?'class="dropdown"':'') . '><a href="' . (($rowm['target'] != '_blank')?'':'') . $rowm['url'] . '" ' . ($num2 > 0?'class="dropdown-toggle" data-toggle="dropdown"':'') . '  role="button"><i class="' . $rowm['icon'] . '"></i> ' . $rowm['name'];
	if ($num2 > 0) {
		echo '<span class="caret"></span>';
	}
	echo '</a>';

	//Level 2
	if ($num2 > 0) {
		echo '<ul class="dropdown-menu" role="menu">';
		$i2 = 0;
		foreach ($res2 as $rowm2) {
			//markers
			if ($i2 == 0) {
				$cl = 'class="first"';
			} else {
				$cl = '';
			}

			if ($rowm2['target'] != '') {
				$target = 'target="' . $rowm2['target'] . '"';
			} else {
				$target = '';
			}
			// /admin/
			echo '<li ' . $cl . '><a href="' . (($rowm2['target'] != '_blank')?'':'') . $rowm2['url'] . '" ' . $target . '>' . $rowm2['name'] . '</a></li>
			';
			$i2++;
		}
		echo '</ul>';
	}
	echo '</li>';
}
//End menu ccl
?>
					
                    
				</ul>
                
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="/" target="_blank" data-toggle="tooltip" data-placement="left" title="<?=$word[$ALANG]['view_site']?>">
                            <i class="glyphicon glyphicon-eye-open"></i> <span class="visible-xs-inline visible-sm-inline"><?=$word[$ALANG]['view_site']?></span>
                        </a>
                    </li>
                    <li>
                        <a href="exit.php" data-toggle="tooltip" data-placement="left" title="<?=$word[$ALANG]['exit']?>">
                            <i class="glyphicon glyphicon-off"></i> <span class="visible-xs-inline visible-sm-inline"><?=$word[$ALANG]['exit']?></span>
                        </a>
                    </li>
                </ul>
                <p class="navbar-text pull-right" data-toggle="tooltip" data-placement="left" title="<?php echo $word[$ALANG]['login_as'] . ' ' . $row_user['name']; ?>">
                    <a style="color:#FFF" href="items.php?tabler=admins_groups&tablei=admins&srci=items.php&id=<?=$_SESSION['admin_id']?>#header"><i class="glyphicon glyphicon-user"></i> <?php echo $row_user['name']; ?></a>
                </p>
            </div>
        </div>
    </nav>
