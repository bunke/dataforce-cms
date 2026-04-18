<?php

/**
 * Database connection + legacy request-variable binding.
 *
 * Historically this file did `foreach($_POST as $n=>$v) $$n=$v;` — essentially
 * a manual register_globals, which let any request overwrite $DB_PASSWORD,
 * $TABLE_*, bootstrap internals, etc. We keep the "$id / $tabler / $del in
 * local scope" convention that controllers depend on, but filter the
 * input so sensitive names cannot be clobbered.
 */

// --- Connections ---
$mySqlLink = mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD);
if (!$mySqlLink) {
	throw new RuntimeException('DataForce: mysqli connect failed');
}
mysqli_select_db($mySqlLink, $DB_NAME);
mysqli_query($mySqlLink, 'SET NAMES utf8');

try {
	$pdo = new PDO(
		"mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8",
		$DB_USER,
		$DB_PASSWORD,
		[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
	);
	$pdo->exec('set names utf8');
} catch (PDOException $e) {
	throw new RuntimeException('DataForce: PDO connect failed: ' . $e->getMessage(), 0, $e);
}

// --- Legacy request-variable binding (filtered) ---

/**
 * Names that MUST NOT be overwritten from the request. Any of these coming
 * in via $_GET/$_POST would allow the caller to hijack the app.
 */
$__dfDeny = [
	// DB & config from bootstrap
	'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
	'ALANG', 'PROJECT_NAME', 'LANGS', 'FOLDER_FILES',
	'dfConfig',
	// Connections
	'mySqlLink', 'pdo', 'db',
	// PHP superglobals
	'GLOBALS', '_GET', '_POST', '_REQUEST',
	'_COOKIE', '_FILES', '_SERVER', '_ENV', '_SESSION',
	// Our own internals (prefix __df is reserved)
];

foreach (array_merge($_POST, $_GET) as $__name => $__val) {
	// Skip blocked names
	if (in_array($__name, $__dfDeny, true)) {
		continue;
	}
	// Block the TABLE_* and __df* namespaces
	if (strncmp($__name, 'TABLE_', 6) === 0) {
		continue;
	}
	if (strncmp($__name, '__df',   4) === 0) {
		continue;
	}
	// Only valid PHP identifiers
	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $__name)) {
		continue;
	}

	$$__name = $__val;
}

unset($__dfDeny, $__name, $__val);
