<?php

/*
 * Admin classes
 * */

class admin_admins_menu extends AdminTable
{
	public $TABLE = 'admins_menu';
	public $NAME = 'Меню админов';
	public $NAME2 = 'пункт меню';

	function __construct()
	{
		$this->fld = [
			new Field('name','Название раздела',1),
			new Field('icon','Путь к иконке',1),
			new Field('url','Адрес страницы: about',1,1),
			new Field('target','Target',1,1),
			new Field('under','Находитcя в разделе',9, ['showInList' => 0, 'editInList' => 0, 'valsFromTable' => 'admins_menu', 'valsFromCategory' => -1, 'valsEchoField' => 'name']),
			new Field('sort','SORT',4),
			new Field('creation_time','Date of creation',4)
		];
	}
}

class admin_admins_groups extends AdminTable
{
	public $TABLE = 'admins_groups';
	public $SORT = 'name asc';
	public $NAME = 'Групи користувачів';
	public $NAME2 = 'групу';

	function __construct()
	{
		$this->fld = [new Field('name','Назва групи',1),
			//new Field("menu","HTML-код меню",2),
			new Field('deny_tables','Заборонені таблиці (через кому)',1),
			new Field('under','Основна група',9, ['showInList' => 0, 'editInList' => 0, 'valsFromTable' => 'admins_groups', 'valsFromCategory' => -1, 'valsEchoField' => 'name']),
			new Field('creation_time','Date of creation',4)];
	}

	function addSpFields($row,$under)
	{
		if (!empty($row['id']) && !isset($del)) {
			echo "<script language=\"JavaScript\" type=\"text/javascript\">
			$(document).ready(function() {
	/* This is basic - uses default settings */
	/* Using custom settings */
	$(\"a#inline\").fancybox({
		'hideOnContentClick': true
	});
	
	$(\"a.frame\").fancybox({
		   zoomSpeedIn: 0,
		   zoomSpeedOut:0,
		   frameWidth: 800,
		   frameHeight: 650
		 });
		 
});

		function ListRubs(under,addrub,delrub,rpage)
		{
		$.ajax({
		  type: \"GET\",
		  url: 'ajax/assoc.php',
		  data: 'under='+under+'&addrub='+addrub+'&delrub='+delrub+'&rpage='+rpage+'&jfname=ListRubs&id=" . $row['id'] . "&tabler=admins_menu&tablei=&tablea=admins_menu_assoc&col_rec=group_id&col_under=menu_id&xr='+Math.random(),
		  success: function(answer) {
		  $('#rubs').html(answer);
		  }
		 });
		}
		
		</script>
		"

			;

			echo '<br /><br />
<strong style="font-size:14px;">
<img border="0" alt="Войти в раздел" src="img/folder_opened.gif"> Настройка меню</strong><br/><div id="rubs">
		<script language="javascript">ListRubs(-1);</script>
	</div>
	'; //onclick="popUp(\'topics\')"
		}
	}

	function getRoot()
	{
		// Админы РВ видят только группы в РВ
		if (!empty($_SESSION['admin']['group_id']) && $_SESSION['admin']['group_id'] == 2) {
			return 3;
		} else {
			return -1;
		}
	}
}

class admin_admins extends AdminTable
{
	public $TABLE = 'admins';
	public $SORT = 'name asc';
	public $NAME = 'Користувачі адмін-панелі';
	public $NAME2 = 'користувача';
	public $FIELD_UNDER = 'group_id';

	function __construct()
	{
		$this->fld[] = new Field('name','Логін',1);
		$this->fld[] = new Field('passwd','Хеш-код пароля',1);
		$this->fld[] = new Field('passwd_rec','Код восстановления пароля',3);
		$this->fld[] = new Field('email','E-mail',1, ['showInList' => 1]);

		if (!empty($_SESSION['admin']['group_id']) && $_SESSION['admin']['group_id'] == 1) {
			$this->fld[] = new Field('deny_scripts','Закрытые скрипты (через запятую)',1);
			$this->fld[] = new Field('deny_tables','Закрытые таблицы (через запятую)',1);
		}

		if (isset($_REQUEST['id']) && $_REQUEST['id'] == $_SESSION['admin_id']) {
			$this->fld[] = new Field('group_id', 'Група', 3);
		} else {
			$this->fld[] = new Field('group_id', 'Група', 9, ['showInList' => 0, 'editInList' => 0, 'valsFromTable' => 'admins_groups', 'valsFromCategory' => -1,
				'valsEchoField' => 'name', 'whereGenerator' => ['class' => 'admin_admins', 'method' => 'groupWhereGen']]);
		}

		$this->fld[] = new Field('creation_time', 'Date of creation', 4);
	}

	function checkPermissions($id)
	{
		if (!isset($id) || empty($id)) {
			return true;
		}

		// var_dump($id);

		if ($id == $_SESSION['admin_id']) {
			return true;
		}

		if ($_SESSION['admin']['group_id'] == 1) {
			return true;
		}

		$rowAdmin = FetchID('admins', $id);

		if ($_SESSION['admin']['group_id'] == $rowAdmin['group_id'] && $_SESSION['admin']['group_id'] != 1) {
			return false;
		}

		if ($_SESSION['admin']['group_id'] != 1 && $rowAdmin['group_id'] == 1) {
			return false;
		}

		if ($_SESSION['admin']['region'] != $rowAdmin['region']) {
			return false;
		}

		return true;
	}
	function addSpFields($row, $under)
	{
		global $TABLE_ADMINS, $HTPASS_ADDRR;
		echo "<script language=\"JavaScript\" type=\"text/javascript\">
		function gethash(inp) {
            if (inp!='') {
                word = document.getElementById(inp).value;
            } else {
            word = 0;
        }

		$.ajax({
		  type: \"POST\",
		  url: 'ajax/get_hash_admin.php',
		  data: 'pass='+word,
		  success: function(answer) {
		  $('#passwd').val(answer);
		  }
		 });
		}</script>";
		echo '<br/><br/><div id="md5" style="border: 1px #CCC;">
		 <strong>Введіть новыи пароль: </strong> <input name="cpass" type="text" id="cpass" value=""> <a href="javascript:gethash(\'cpass\')"><u>отримати код</u></a>
		 </div>';
	}

	public function groupWhereGen()
	{
		if (!empty($_SESSION['admin']['group_id']) && $_SESSION['admin']['group_id'] >= 2) {
			return 'WHERE under = 3';
		} else {
			return 'WHERE under = -1';
		}
	}
}

class admin_admins_menu_assoc extends AdminTable
{
	public $SORT = 'group_id asc';
	public $name = 'меню';
	public $NAME2 = 'меню';
	public $tableRubs = 'admins_menu';
	public $colRecord = 'group_id';
	public $colUnder = 'menu_id';

	function __construct()
	{
		$this->fld = [
			new Field('menu_id','id menu', 1),
			new Field('group_id','id group', 1),
		];
	}
}
