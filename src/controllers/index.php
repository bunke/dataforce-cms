<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $PROJECT_NAME . ': ' . $word[$ALANG]['adminpanel']; ?></title>
<?php
$App->insertHead();
?>
</head>

<body>
<?php
require_once('controllers/menu.php');
?>
<br />
<br />
<div class="container">
    <h2><span class="glyphicon glyphicon-map-marker" style="color:#1E90FF" aria-hidden="true"></span>
        <?=$word[$ALANG]['hello']?>, <strong><?=htmlspecialchars($row_user['name'], ENT_QUOTES, 'UTF-8'); ?></strong>!</h2>
    <br />

    <div class="row df-dashboard">
<?php
$topq = "SELECT * FROM $TABLE_ADMINS_MENU WHERE under=-1 $whg ORDER BY sort DESC";
$topItems = pdoFetchAll($topq);

foreach ($topItems as $item) {
    $subq = "SELECT * FROM $TABLE_ADMINS_MENU WHERE under={$item['id']} $whg ORDER BY sort DESC";
    $subItems = pdoFetchAll($subq);

    $hasOwnUrl = ($item['url'] !== '' && $item['url'] !== '#');
    $icon = $item['icon'] ?: 'glyphicon glyphicon-folder-open';
    $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
    $target = ($item['target'] === '_blank') ? ' target="_blank"' : '';
    ?>
        <div class="col-sm-6 col-md-4">
            <div class="df-card">
                <div class="df-card-head">
                    <?php if ($hasOwnUrl): ?>
                        <a href="<?=htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8')?>"<?=$target?>>
                            <i class="<?=htmlspecialchars($icon, ENT_QUOTES, 'UTF-8')?>"></i>
                            <span><?=$name?></span>
                        </a>
                    <?php else: ?>
                        <i class="<?=htmlspecialchars($icon, ENT_QUOTES, 'UTF-8')?>"></i>
                        <span><?=$name?></span>
                    <?php endif; ?>
                </div>
                <?php if (count($subItems) > 0): ?>
                    <ul class="df-card-sub">
                        <?php foreach ($subItems as $sub):
                            $subIcon   = $sub['icon'] ?: 'glyphicon glyphicon-chevron-right';
                            $subTarget = ($sub['target'] === '_blank') ? ' target="_blank"' : '';
                            ?>
                            <li>
                                <a href="<?=htmlspecialchars($sub['url'], ENT_QUOTES, 'UTF-8')?>"<?=$subTarget?>>
                                    <i class="<?=htmlspecialchars($subIcon, ENT_QUOTES, 'UTF-8')?>"></i>
                                    <?=htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8')?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php
}
?>
    </div>
</div>

<style>
.df-dashboard { margin-top: 10px; }
.df-dashboard .df-card {
    background: #fff;
    border: 1px solid #e5e8ec;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    margin-bottom: 20px;
    transition: box-shadow .15s ease, transform .15s ease;
}
.df-dashboard .df-card:hover {
    box-shadow: 0 6px 18px rgba(30,144,255,.12);
    transform: translateY(-1px);
}
.df-dashboard .df-card-head {
    padding: 18px 20px;
    border-bottom: 1px solid #eef1f4;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}
.df-dashboard .df-card-head a {
    color: #2c3e50;
    text-decoration: none;
    display: block;
}
.df-dashboard .df-card-head a:hover { color: #1E90FF; }
.df-dashboard .df-card-head i {
    color: #1E90FF;
    margin-right: 10px;
    font-size: 20px;
    width: 24px;
    text-align: center;
}
.df-dashboard .df-card-sub {
    list-style: none;
    margin: 0;
    padding: 8px 0;
}
.df-dashboard .df-card-sub li a {
    display: block;
    padding: 8px 20px;
    color: #555;
    text-decoration: none;
    border-left: 3px solid transparent;
}
.df-dashboard .df-card-sub li a:hover {
    background: #f6f9fc;
    color: #1E90FF;
    border-left-color: #1E90FF;
}
.df-dashboard .df-card-sub li a i {
    color: #9aa5b1;
    margin-right: 8px;
    width: 16px;
    text-align: center;
    font-size: 12px;
}
.df-dashboard .df-card-sub li a:hover i { color: #1E90FF; }
</style>

<?php
include('inc/Bottom.php');
?>
</body>
</html>
