<?php
if (floatval(phpversion()) < 5.6) {
    die('PHP must be 5.6+ version!');
}

if (!defined('DATAFORCE_SRC')) {
    define('DATAFORCE_SRC', __DIR__);
}
if (!defined('DATAFORCE_ROOT')) {
    define('DATAFORCE_ROOT', dirname(__DIR__));
}

// Standalone mode loads config.php sibling to router.php.
// Vendor mode skips it — bootstrap.php already exported the
// required globals from $dfConfig before including router.
if (!isset($dfConfig)) {
    require_once("config.php");
}

require_once("inc/Connect.php");
require_once("inc/Defines.php");
require_once("inc/CommonFuncs.php");


if (!isset($SESSIONS_PROVIDER)) {
    session_start();
}
else {
    require_once($SESSIONS_PROVIDER);
}


/*
 * Model autoloader.
 *
 *   1. CMS core models (admin_admins* etc.) live in src/models/.
 *   2. Project-specific models live in the host site and are pointed
 *      at via $dfConfig['paths']['extra_models'] = [absolute/path, ...].
 *
 * Both dirs are scanned and every *.php file is required_once.
 */
$__dfModelDirs = [DATAFORCE_SRC . '/models'];
if (isset($dfConfig['paths']['extra_models'])) {
	foreach ((array)$dfConfig['paths']['extra_models'] as $__dir) {
		$__dfModelDirs[] = $__dir;
	}
}

foreach ($__dfModelDirs as $__dir) {
	if (!is_dir($__dir)) continue;
	foreach (scandir($__dir) as $__file) {
		if ($__file === '.' || $__file === '..') continue;
		if (substr(strtolower($__file), -4) !== '.php') continue;
		require_once rtrim($__dir, '/\\') . '/' . $__file;
	}
}
unset($__dfModelDirs, $__dir, $__file);


if (isset($_GET['inc']) && $_GET['inc'] != '' && $_GET['inc'] != 'login') {
	$sttime = getTime();

	$admin_name = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'';
	$admin_id = isset($_SESSION['admin_id'])?$_SESSION['admin_id']:NULL;

	//Anti self loop
    if ($_GET['inc'] == 'router') {
        $_GET['inc'] = "index";
    }

    if (!isset($_SESSION['admin_name']) || (isset($_SESSION['admin_name']) && $_SESSION['admin_name']== '')) {

        if ($_SERVER['REQUEST_URI'] != '/admin/')
            $backUrl = '?backUrl=' . base64_encode($_SERVER['REQUEST_URI']);
        else
            $backUrl = '';

        die('<script>document.location=\'/admin/login.php' . $backUrl . '\'</script>');
    }

    // Safe class instantiation for tabler/tablei/table — only admin_* classes
    // that actually exist. Blocks the "new $cfn() from request" RCE vector.
    foreach (['tabler', 'tablei', 'table'] as $__key) {
        if (empty($_REQUEST[$__key])) continue;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $_REQUEST[$__key])) continue;

        $__cfn = 'admin_' . $_REQUEST[$__key];
        if (!class_exists($__cfn)) continue;

        ${$__cfn} = new $__cfn();
    }
    unset($__key, $__cfn);

    // Safe controller include — sanitize $_GET['inc'], then verify the
    // resolved path is inside the CMS root. Blocks ../ traversal and LFI.
    $__inc = $_GET['inc'];
    $__resolved = resolveControllerPath($__inc);

    if ($__resolved === null) {
        http_response_code(404);
        die('File not found!');
    }

    include $__resolved;
    unset($__inc, $__resolved);
}
else {
    include('controllers/login.php');
}


/**
 * Map $_GET['inc'] to an absolute controller path inside DATAFORCE_SRC.
 * Returns null if the value is unsafe or no matching file exists.
 */
function resolveControllerPath($inc)
{
    // Format: one or more /-separated segments of [a-zA-Z0-9_-]
    if (!is_string($inc) || !preg_match('~^[a-zA-Z0-9_-]+(/[a-zA-Z0-9_-]+)*$~', $inc)) {
        return null;
    }

    $src = realpath(DATAFORCE_SRC);
    if ($src === false) return null;

    $candidates = [
        $src . DIRECTORY_SEPARATOR . $inc . '.php',                       // ajax/*, modules/*
        $src . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR  // controllers/*
            . $inc . '.php',
    ];

    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real === false) continue;
        if (!is_file($real)) continue;
        // Containment: resolved path must start within DATAFORCE_SRC
        if (strncmp($real, $src . DIRECTORY_SEPARATOR, strlen($src) + 1) !== 0) continue;

        return $real;
    }

    return null;
}
