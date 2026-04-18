<?php
// In standalone mode this controller can be hit directly — reload
// its dependencies. Vendor mode has already bootstrapped them.
if (!isset($dfConfig)) {
	require_once('config.php');
	require_once('inc/Defines.php');
	require_once('inc/CommonFuncs.php');
}

/*
 * ********************** Password Recovery action *********************
 * */
if (isset($_GET['rec']) && $_GET['rec'] != '') {
	if (file_exists('inc/Connect.php')) {
		include('inc/Connect.php');
	}

	// Only hex/alphanumeric tokens — no SQL injection via the rec param.
	$rec = preg_replace('/[^a-zA-Z0-9]/', '', (string)$_GET['rec']);

	$query = "SELECT * FROM $TABLE_ADMINS WHERE `passwd_rec`='" . $rec . "'";
	$res = pdoFetchAll($query);

	if (count($res) == 1) {
		$row = $res[0];

		$eml = explode('@', $row['email']);
		$newpass = rand(1000, 9999);
		$newpass_hash = crypt($newpass, 'bp');

		$q = "UPDATE $TABLE_ADMINS SET `passwd`='$newpass_hash', `passwd_rec`='' WHERE id=" . (int)$row['id'];
		$res = pdoExec($q);

		if ($res) {
			$host = preg_replace('/[\r\n]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
			echo 'Новий пароль для <strong>' . htmlspecialchars($row['name']) . '</strong>: <strong>' . $newpass . '</strong> — збережіть!';

			$headers  = 'From: ' . $host . ' <pass_rec@' . $host . ">\r\n";
			$headers .= 'Return-Path: <pass_rec@' . $host . ">\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8\r\n";

			$subject = 'Відновлення пароля — ' . $host;
			$body    = 'Новий пароль для https://' . $host . '/admin/ = <strong>' . $newpass . '</strong>';

			if (@mail($row['email'], $subject, $body, $headers)) {
				echo ' <strong>Надіслано на …@' . htmlspecialchars($eml[1] ?? '') . '</strong>';
			} else {
				echo ' <em>(лист не вдалося надіслати — перевірте конфігурацію mail() на сервері)</em>';
			}
		}
	} else {
		echo 'Код відновлення не знайдено!';
	}
}
/*
 * ***************************** Password recovery query ***************
 * */
elseif (!empty($_POST['email'])) {
	if (file_exists('inc/Connect.php')) {
		include_once('inc/Connect.php');
	} else {
		die('NO CONNECTION!');
	}

	$email = addslashes($_POST['email']);

	$query = "SELECT * FROM $TABLE_ADMINS WHERE `email`='" . $email . "'";
	$res = pdoFetchAll($query);

	if (count($res) == 1) {
		$row = $res[0];

		if (empty($row['email'])) {
			die('Для користувача ' . htmlspecialchars($row['name']) . ' не вказаний email.');
		}

		$eml = explode('@', $row['email']);
		// Token: 32 hex chars from random_bytes when available (vs. legacy
		// md5(date('U') - rand()) which was predictable).
		if (function_exists('random_bytes')) {
			$passwd_rec_hash = bin2hex(random_bytes(16));
		} else {
			$passwd_rec_hash = md5(uniqid((string)mt_rand(), true));
		}

		$q = "UPDATE $TABLE_ADMINS SET `passwd_rec`='$passwd_rec_hash' WHERE id=" . (int)$row['id'];
		$res = pdoExec($q);

		$host = preg_replace('/[\r\n]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));

		$headers  = 'From: ' . $host . ' <pass_rec@' . $host . ">\r\n";
		$headers .= 'Return-Path: <pass_rec@' . $host . ">\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";

		$subject = 'Відновлення пароля — ' . $host;
		$body    = 'Щоб отримати новий пароль для ' . $row['name']
			. ', перейдіть за посиланням: https://' . $host
			. '/admin/login.php?rec=' . $passwd_rec_hash;

		if (@mail($row['email'], $subject, $body, $headers)) {
			die('<center style="font-family:arial;font-size:16px;margin-top:40px;">
			Лист надіслано на <strong>' . htmlspecialchars($eml[0]) . '@…</strong><br/><br/>
			<a href="login.php">← Повернутися до входу</a></center>');
		} else {
			die('<center style="color:red;font-family:arial;font-size:16px;margin-top:40px;">
			Не вдалося надіслати лист. Перевірте налаштування mail() на сервері.<br/><br/>
			<a href="login.php">← Повернутися до входу</a></center>');
		}
	} else {
		echo 'Користувача з таким email не знайдено.';
	}
}
/*
 * *********************** CREATE TABLES *******************************
 * */
elseif (!empty($_GET['crt'])) {
	if (file_exists('inc/Connect.php')) {
		include_once('inc/Connect.php');
	} else {
		die('NO CONNECTION!');
	}

	if (!isset($TABLE_ADMINS_GROUPS)) {
		die('TABLE_ADMINS_GROUPS not set!');
	}

	$cfn = 'admin_' . $TABLE_ADMINS_GROUPS;

	${"admin_$TABLE_ADMINS_GROUPS"} = new $cfn();

	$res = crtQuery($TABLE_ADMINS_GROUPS, ${"admin_$TABLE_ADMINS_GROUPS"}->fld);

	if ($res === false) {
		var_dump(pdoError());
	} else {
		echo 'Table ' . $TABLE_ADMINS_GROUPS . ' created ';

		$q = "INSERT INTO `$TABLE_ADMINS_GROUPS` (`id`, `name`, `deny_tables`,`under`, `creation_time`) VALUES
                (1, 'Admins','','-1','" . date('U') . "')";

		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		}

		$qim = "CREATE TABLE IF NOT EXISTS `$TABLE_ADMINS_MENU` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `icon` varchar(255) NOT NULL DEFAULT '',
      `name` varchar(250) NOT NULL,
      `url` varchar(250) NOT NULL,
      `target` varchar(250) NOT NULL,
      `under` int(11) NOT NULL,
      `sort` int(11) NOT NULL,
      `creation_time` bigint(20) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";

		$res = pdoExec($qim);
		if ($res === false) {
			var_dump(pdoError());
		}

		$qim = "INSERT INTO `$TABLE_ADMINS_MENU` (`id`, `icon`, `name`, `url`, `target`, `under`, `sort`, `creation_time`) VALUES
        (1, 'glyphicon glyphicon-th-large', 'Каталог', 'catalog.php?tabler=products_categories&tablei=products&srci=items.php&under=-1', '', -1, 8, 1338378249),
        (2, 'glyphicon glyphicon-text-size', 'Тексты', 'catalog.php?tabler=docs_rubs&tablei=docs&srci=items.php&under=-1', '', -1, 2, 1338378274),
        (3, 'glyphicon glyphicon-tower', 'Администраторы', 'catalog.php?tabler=admins_groups&tablei=admins', '', -1, 1, 1338378294),
        (4, 'glyphicon glyphicon-cog', 'Настройки', 'catalog.php?tabler=slovar_rubs&tablei=slovar', '', -1, 0, 1338378314),
        (5, '', 'SEO', 'catalog.php?tabler=&tablei=seo&srci=items.php', '', 4, 6, 1338630890),
        (6, 'query.php', 'SQL', 'query.php', '', 4, 5, 1338630903),
        (7, '', 'Меню', 'catalog.php?tabler=admins_menu', '', 4, 4, 1338630927),
        (8, 'glyphicon glyphicon-briefcase', 'Заказы', 'catalog.php?tabler=&tablei=orders&under=-1', '', -1, 3, 1338630994),
        (9, '', 'Новые заказы', 'catalog.php?date_1=&date_2=&sort_status=8&d_status=&opl_status=&sub=%D0%A4%D0%B8%D0%BB%D1%8C%D1%82%D1%80&tabler=&tablei=orders&under=-1', '', 8, 16, 1338631029),
        (10, '', 'Параметры заказов', 'catalog.php?tabler=&tablei=orders_params&srci=items.php&under=-1', '', 8, 9, 1338631058),
        (11, '', 'Товары', 'catalog.php?tabler=products_categories&tablei=products&srci=items.php&under=-1', '', 1, 19, 1338631142),
        (12, '', 'Характеристики', 'catalog.php?tabler=extra_params&tablei=extra_params_variants&srci=items.php&under=-1', '', 1, 11, 1338631158),
        (13, '', 'Бренды', 'catalog.php?tabler=products_brands_categories&tablei=products_brands&srci=items.php&under=-1', '', 1, 10, 1338631172),
        (14, '', 'Тектовые блоки', 'catalog.php?tabler=slovar_rubs&tablei=slovar', '', 4, 14, 1338633269),
        (15, '', 'Отзывы', 'catalog.php?tabler=&tablei=products_comments&srci=items.php', '', 1, 9, 1339739682),
        (16, '', 'Покупатели', 'catalog.php?tabler=&tablei=users&srci=items.php', '', 8, 8, 1339739870),
        (17, '', 'Заказы в работе', 'catalog.php?date_1=&date_2=&sort_status=9&d_status=&opl_status=&sub=%D0%A4%D0%B8%D0%BB%D1%8C%D1%82%D1%80&tabler=&tablei=orders&under=-1', '', 8, 10, 1339739942),
        (18, '', 'Рассылка', 'mail.php', '', 8, 7, 1339740036),
        (19, '', 'Акции', 'catalog.php?tabler=&tablei=actions&srci=items.php', '', 1, 12, 1339767953),
        (20, '', 'Статьи для товаров', 'catalog.php?tabler=&tablei=articles&srci=items.php', '', 2, 20, 1339768143),
        (21, '', 'Статические старницы', 'catalog.php?tabler=docs_rubs&tablei=docs&srci=items.php&under=-1', '', 2, 21, 1339768176),
        (22, '', 'Баннеры', 'catalog.php?tabler=&tablei=banners&srci=items.php&under=-1', '', 1, 8, 1339861191),
        (23, '', 'Магазины', 'catalog.php?tabler=&tablei=shops&srci=items.php&under=-1', '', 1, 7, 1339861767),
        (24, '', 'Наш магазин', 'items.php?tabler=docs_rubs&tablei=docs&srci=items.php&id=1#header', '', 2, 24, 1339862193),
        (25, '', 'Обратный звонок', 'catalog.php?tabler=&tablei=users_call', '', 8, 25, 1340018125),
        (26, '', 'Теги', 'catalog.php?tabler=tags&tablei=&under=-1', '', 2, 26, 1340018125),
        (27, '', 'Новости', 'catalog.php?tabler=&tablei=news', '', 2, 27, 1340271797);";

		$res = pdoExec($qim);
		if ($res === false) {
			var_dump(pdoError());
		}
	}

	// Creating admin
	$cfn = 'admin_' . $TABLE_ADMINS;
	${"admin_$TABLE_ADMINS"} = new $cfn();
	$res = crtQuery($TABLE_ADMINS, ${"admin_$TABLE_ADMINS"}->fld);

	if ($res === false) {
		var_dump(pdoError());
	} else {
		$q = "INSERT INTO `$TABLE_ADMINS` (`id`, `name`, `email`, `passwd`, `group_id`, `creation_time`) VALUES
(1, 'admin', 'als.bunke@gmail.com', 'bpMQSfH1Qgdbw', '1', '" . date('U') . "')";
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		}
	}

	if (isset($TABLE_ADMINS_MENU_ASSOC)) {
		$q = 'CREATE TABLE `' . $TABLE_ADMINS_MENU_ASSOC . '` (
  `menu_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`menu_id`,`group_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `admins_menu_assoc_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `' . $TABLE_ADMINS_MENU . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `admins_menu_assoc_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `' . $TABLE_ADMINS_GROUPS . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ';
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			echo '... Menu assoc table created';
		}
	}

	if (isset($TABLE_ADMINS_LOG)) {
		$q = 'CREATE TABLE `' . $TABLE_ADMINS_LOG . '` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `login` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
    `ip` BIGINT NOT NULL ,
    `table_name` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
    `action` INT NOT NULL ,
    `rec_id` INT NOT NULL ,
    `creation_time` BIGINT NOT NULL ,
    PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8;
    ';
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			echo '... Log table created';
		}
	}

	if (isset($TABLE_TAGS)) {
		$q = 'CREATE TABLE `' . $TABLE_TAGS . '` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `hide` smallint(6) NOT NULL DEFAULT 0,
  `parent_id` int(11) NOT NULL DEFAULT -1,
  `sort` int(11) NOT NULL,
  `creation_time` bigint(20) NOT NULL DEFAULT 0,
  `update_time` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ';
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			echo '... Tags table created';
		}

		$q = 'CREATE TABLE `' . $TABLE_TAGS . '_info` (
  `record_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `lang` varchar(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`record_id`,`lang`),
  KEY `tags_info` (`record_id`,`lang`),
  KEY `tags_info_back` (`lang`,`record_id`),
  CONSTRAINT `tags_info_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ';
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			echo '... Tags info table created';
		}

		$q = 'CREATE TABLE `tags_assoc` (
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`table_name`,`record_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `tags_assoc_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ';
		$res = pdoExec($q);
		if ($res === false) {
			var_dump(pdoError());
		} else {
			echo '... Tags assoc table created';
		}
	}

	echo '... creating done!';
}
/*
 ****************************** Login **********************************
 * */
elseif (!empty($_POST['login']) && !empty($_POST['passwd'])) {
	$login = addslashes($_POST['login']);

	$passwdh = crypt($_POST['passwd'], 'bp');

	if (file_exists('inc/Connect.php')) {
		include('inc/Connect.php');
	} else {
		die('No connect!');
	}

	$query = "SELECT * FROM $TABLE_ADMINS WHERE `name`='" . $login . "' AND `passwd`='" . $passwdh . "'";
	//echo $query;
	$res = pdoFetchAll($query);

	if (count($res) == 1) {
		/*if (!isset($SESSIONS_PROVIDER)) {
		    session_start();
		}
		else {
		    require_once($SESSIONS_PROVIDER);
		}*/

		$row = $res[0];
		$_SESSION['hash'] = $row['passwd'];
		$_SESSION['admin_name'] = $row['name'];
		$_SESSION['admin_id'] = $row['id'];
		$_SESSION['admin'] = $row;

		$query = "SELECT * FROM $TABLE_ADMINS_GROUPS WHERE `id` = " . $row['group_id'];
		//echo $query;
		$res = pdoFetchAll($query);
		$_SESSION['admin']['group'] = $res[0];

		//var_dump($_SESSION);

		addLog('',0,0);

		$url = !empty($_POST['backUrl']) ? $_POST['backUrl'] : 'index.php';

		die('<body">
        <center><strong style="font-family:arial;font-size:21px;">' . $word[$ALANG]['login_ok'] . "</strong></center>
		<script>document.location='" . $url . "'</script>
        </body>");
	} else {
		echo '<center><strong style="font-family:arial;font-size:21px;">' . $word[$ALANG]['login_error'] . '</strong></center>';
	}
}

?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Admin login | <?=$PROJECT_NAME?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="stylesheet" href="css/bootstrap.min.css" media="screen" />
        <link rel="stylesheet"  href="css/admin-style.css" />
        <link rel="icon" href="img/favicon.ico" type="image/x-icon">
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">
                    CMS: <?=$PROJECT_NAME?>
                </a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#" data-toggle="tooltip" data-placement="left" title="<?=($word[$ALANG]['view_site'] ?? 'Подивитися сайт')?>">
                            <i class="glyphicon glyphicon-eye-open"></i> <span class="visible-xs-inline visible-sm-inline"><?=($word[$ALANG]['view_site'] ?? 'Подивитися сайт')?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="panel panel-primary login">
                    <div class="panel-heading">
                        <h1 class="panel-title"><i class="glyphicon glyphicon-log-in"></i>&nbsp;<?php echo $word[$ALANG]['auth']; ?></h1>
                    </div>
                    <div class="panel-body">
                        <form action="login.php" method="post" name="f_login" target="_self">
                            <div class="form-group">
                                <label for="your_login"><?=$word[$ALANG]['login']?>:</label>
                                <input tabindex="1" type="text" class="form-control" id="your_login" name="login"/>
                            </div>
                            <div class="form-group">
                                <label for="your_passw"><?=$word[$ALANG]['pass']?>:</label>
                                <input tabindex="2" type="password" name="passwd" class="form-control" id="your_passw" />
                            </div>
                            <!--<div class="form-group">
                                <label><input type="checkbox" checked="checked" /> Запомнить меня</label>
                            </div>-->
                            <div class="form-group">
                                <button tabindex="3"  type="submit" class="btn btn-primary btn-block">
                                    <i class="glyphicon glyphicon-check"></i>&nbsp;<?php echo $word[$ALANG]['enter']; ?>
                                </button>
                            </div>
                            <input type="hidden" name="backUrl" value="<?php if (!empty($_GET['backUrl'])) {
                            	echo base64_decode($_GET['backUrl']);
                            } ?>" />
							
                        </form>
                        <p class="text-center"><?php echo $word[$ALANG]['lost_p']; ?>? <a tabindex="4" href="#" data-toggle="modal" data-target="#remind"><?php echo $word[$ALANG]['remind']; ?></a></p>
                    </div>
                </div><!--/panel-->
                <div class="alert alert-success alert-dismissible hidden" role="alert" id="passw_sent">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?=($word[$ALANG]['passw_sent'] ?? 'Ми надіслали пароль на Ваш e-mail!')?>
                </div>
            </div><!--/col-md-4-->
        </div><!--/row-->
    </div><!--/container-->

    <!--modal window-->
    <!-- Modal -->
    <div class="modal fade" id="remind" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=($word[$ALANG]['remind_title'] ?? 'Нагадування пароля')?></h4>
                </div>
                <div class="modal-body">
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="your_email"><?=($word[$ALANG]['remind_email'] ?? 'Введіть email, вказаний при реєстрації')?></label>
                            <input type="email" name="email" class="form-control" placeholder="admin@sample.com" />
                        </div>
                        <button type="submit" class="btn btn-primary" id="passw_sent_init"><i class="glyphicon glyphicon-send"></i> <?=($word[$ALANG]['send'] ?? 'Надіслати')?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/modal window-->

    <footer id="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <p class="text-right">
                        <small><a href="mailto:als.bunke@gmail.com">Vintage Dataforce CMS</a> © 2004-<?=date('Y')?></small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>
    <script src="js/plugins.js"></script>
    <script src="js/admin-scripts.js"></script>
</body>
</html>
