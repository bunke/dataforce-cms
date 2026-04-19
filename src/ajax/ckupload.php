<?php

/**
 * CKEditor 4 image / file uploader.
 *
 * Router already validated the admin session and exported $pref,
 * $pref_url, $FOLDER_USERFILES before including this file.
 *
 * URL:   /admin/ajax/ckupload.php?type=Images          (img dialog, old proto)
 *        /admin/ajax/ckupload.php?type=Images&CKEditorFuncNum=N
 *        /admin/ajax/ckupload.php          (uploadimage plugin, JSON)
 */

global $pref, $pref_url, $FOLDER_USERFILES;

$funcNum = isset($_GET['CKEditorFuncNum']) ? (int)$_GET['CKEditorFuncNum'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'Files';   // 'Images' | 'Files'

$file = isset($_FILES['upload']) ? $_FILES['upload'] : null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
	ckRespond($funcNum, null, 'Upload error: ' . ($file ? $file['error'] : 'no file'));
}

$maxBytes = 10 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
	ckRespond($funcNum, null, 'File too large (max 10 MB)');
}

// MIME detection with fallbacks — Ukrainian shared hosts often ship without
// php-fileinfo, so finfo can be missing entirely.
$mime = '';
if (class_exists('finfo')) {
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($file['tmp_name']) ?: '';
}
if ($mime === '' && function_exists('mime_content_type')) {
	$mime = @mime_content_type($file['tmp_name']) ?: '';
}
if ($mime === '' && function_exists('exif_imagetype')) {
	$exifMap = [
		IMAGETYPE_JPEG => 'image/jpeg',
		IMAGETYPE_PNG  => 'image/png',
		IMAGETYPE_GIF  => 'image/gif',
		IMAGETYPE_WEBP => 'image/webp',
	];
	$imgType = @exif_imagetype($file['tmp_name']);
	if (isset($exifMap[$imgType])) {
		$mime = $exifMap[$imgType];
	}
}

$imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$fileMimes = ['application/pdf', 'application/zip', 'application/msword',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'application/vnd.ms-excel',
	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'text/plain', 'text/csv'];

$allowed = ($type === 'Images') ? $imageMimes : array_merge($imageMimes, $fileMimes);
if (!in_array($mime, $allowed, true)) {
	ckRespond($funcNum, null, 'Unsupported type: ' . $mime);
}

$extByMime = [
	'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
	'image/webp' => 'webp',
	'application/pdf' => 'pdf', 'application/zip' => 'zip',
	'application/msword' => 'doc',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
	'application/vnd.ms-excel' => 'xls',
	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
	'text/plain' => 'txt', 'text/csv' => 'csv',
];
$ext = isset($extByMime[$mime]) ? $extByMime[$mime] : 'bin';

$subdir = ($type === 'Images' ? 'ckeditor/images/' : 'ckeditor/files/') . date('Y/m') . '/';
$dir = rtrim($pref, '/') . '/' . $FOLDER_USERFILES . '/' . $subdir;
if (!is_dir($dir)) {
	@mkdir($dir, 0755, true);
}
if (!is_dir($dir)) {
	ckRespond($funcNum, null, 'Cannot create directory: ' . $dir);
}

$rand = function_exists('random_bytes') ? bin2hex(random_bytes(4)) : substr(md5(uniqid('', true)), 0, 8);
$filename = date('Ymd-His') . '-' . $rand . '.' . $ext;
$target = $dir . $filename;

if (!@move_uploaded_file($file['tmp_name'], $target)) {
	ckRespond($funcNum, null, 'Cannot save uploaded file');
}
@chmod($target, 0644);

$url = rtrim($pref_url, '/') . '/' . $FOLDER_USERFILES . '/' . $subdir . $filename;

ckRespond($funcNum, [
	'uploaded' => 1,
	'fileName' => $filename,
	'url' => $url,
], null);

function ckRespond($funcNum, $payload, $error)
{
	// Old protocol (filebrowserImageUploadUrl): HTML with callback
	if ($funcNum > 0) {
		header('Content-Type: text/html; charset=utf-8');
		if ($error !== null) {
			echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, '', "
			    . json_encode($error) . ');</script>';
		} else {
			echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, "
			    . json_encode($payload['url']) . ');</script>';
		}
		exit;
	}

	// New protocol (uploadimage plugin / imageUploadUrl): JSON
	header('Content-Type: application/json; charset=utf-8');
	if ($error !== null) {
		http_response_code(400);
		echo json_encode(['uploaded' => 0, 'error' => ['message' => $error]]);
	} else {
		echo json_encode($payload);
	}
	exit;
}
