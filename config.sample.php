<?php
// Standalone-mode config. Copy to config.php (which is .gitignored).
// For vendor-mode, use stubs/config.php via bin/dataforce-install instead.

ini_set('display_errors', '0'); // '1' only in dev
date_default_timezone_set('UTC');
error_reporting(E_ALL);

// DB
$DB_HOST     = getenv('DF_DB_HOST')     ?: 'localhost';
$DB_NAME     = getenv('DF_DB_NAME')     ?: 'CHANGE_ME';
$DB_USER     = getenv('DF_DB_USER')     ?: 'CHANGE_ME';
$DB_PASSWORD = getenv('DF_DB_PASSWORD') ?: 'CHANGE_ME';

// Admin
$ALANG        = 'ru';
$PROJECT_NAME = 'DataForce';
$LANGS        = ['ru' => 'RU', 'ua' => 'Укр', 'en' => 'EN'];

// Upload folder names — in standalone mode they're siblings of admin/
$FOLDER_FILES     = 'files';
$FOLDER_IMAGES    = 'images';
$FOLDER_USERFILES = 'userfiles';

// Tables
$TABLE_DOCS_RUBS         = 'tef_drubs';
$TABLE_DOCS              = 'tef_docs';
$TABLE_MAIL              = 'emails';
$TABLE_TAGS              = 'tags';
$TABLE_ADMINS_GROUPS     = 'admins_groups';
$TABLE_ADMINS            = 'admins';
$TABLE_ADMINS_MENU       = 'admins_menu';
$TABLE_ADMINS_MENU_ASSOC = 'admins_menu_assoc';
$TABLE_ADMINS_LOG        = 'admins_log';
