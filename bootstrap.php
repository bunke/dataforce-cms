<?php
/**
 * DataForce CMS — vendor entry point.
 *
 * The host site calls this after `require vendor/autoload.php` and after
 * defining $dfConfig (see stubs/config.php for the shape).
 */

if (!isset($dfConfig) || !is_array($dfConfig)) {
    throw new RuntimeException('DataForce: $dfConfig must be defined before including bootstrap.php');
}

define('DATAFORCE_ROOT', __DIR__);
define('DATAFORCE_SRC',  __DIR__ . '/src');

// --- Expose config as legacy globals expected by controllers ---
$DB_HOST     = $dfConfig['db']['host'] ?? 'localhost';
$DB_NAME     = $dfConfig['db']['name'] ?? '';
$DB_USER     = $dfConfig['db']['user'] ?? '';
$DB_PASSWORD = $dfConfig['db']['pass'] ?? '';

$ALANG        = $dfConfig['lang']         ?? 'ru';
$PROJECT_NAME = $dfConfig['project_name'] ?? 'DataForce';
$LANGS        = $dfConfig['langs']        ?? ['ru' => 'RU', 'ua' => 'Укр', 'en' => 'EN'];

// --- Upload paths (filesystem vs URL) ---
// Two separate prefixes let the CMS run in different layouts:
//   * Legacy standalone: admin/ at project root, uploads are siblings of admin/
//     fs_root = '../', url_root = '../'
//   * Symfony-style: admin served from public/admin/, uploads live in public/
//     fs_root = dirname(DATAFORCE_ROOT).'/public/', url_root = '/'
//   * Self-hosted vendor: admin in public/admin/, uploads at project root
//     fs_root = dirname(DATAFORCE_ROOT).'/', url_root = '/'
$__dfPaths = $dfConfig['paths'] ?? [];

$FOLDER_FILES           = $__dfPaths['files']     ?? ($dfConfig['folder_files'] ?? 'files');
$FOLDER_IMAGES          = $__dfPaths['images']    ?? 'images';
$FOLDER_USERFILES       = $__dfPaths['userfiles'] ?? 'userfiles';
$FOLDER_IMAGES_FRONTEND = $FOLDER_IMAGES;

$pref     = rtrim($__dfPaths['fs_root']  ?? '../', '/') . '/';
$pref_url = rtrim($__dfPaths['url_root'] ?? '../', '/') . '/';

// Legacy flag: "we are not running from admin/ CWD, don't prepend '../' yourself"
$NO_ADMIN = 1;

unset($__dfPaths);

$tables = $dfConfig['tables'] ?? [];
$TABLE_DOCS_RUBS         = $tables['docs_rubs']         ?? 'tef_drubs';
$TABLE_DOCS              = $tables['docs']              ?? 'tef_docs';
$TABLE_MAIL              = $tables['mail']              ?? 'emails';
$TABLE_TAGS              = $tables['tags']              ?? 'tags';
$TABLE_ADMINS_GROUPS     = $tables['admins_groups']     ?? 'admins_groups';
$TABLE_ADMINS            = $tables['admins']            ?? 'admins';
$TABLE_ADMINS_MENU       = $tables['admins_menu']       ?? 'admins_menu';
$TABLE_ADMINS_MENU_ASSOC = $tables['admins_menu_assoc'] ?? 'admins_menu_assoc';
$TABLE_ADMINS_LOG        = $tables['admins_log']        ?? 'admins_log';

// Extra host-provided tables
foreach (($dfConfig['extra_tables'] ?? []) as $key => $name) {
    $varName = 'TABLE_' . strtoupper($key);
    $$varName = $name;
}

if (!empty($dfConfig['error_display'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set($dfConfig['timezone'] ?? 'UTC');

// Legacy code uses relative requires (require_once "inc/Connect.php"),
// scandir("models/") etc. — enter the src/ dir so those resolve.
$__dfPrevCwd = getcwd();
chdir(DATAFORCE_SRC);

try {
    require DATAFORCE_SRC . '/router.php';
} finally {
    if ($__dfPrevCwd !== false) {
        chdir($__dfPrevCwd);
    }
}
