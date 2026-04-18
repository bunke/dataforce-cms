/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.allowedContent = true;
	config.protectedSource.push( /<i [\s\S]*?\i>/g );
	config.enterMode = CKEDITOR.ENTER_BR;

	// Image / file upload (no CKFinder needed — ckupload.php handles it)
	config.filebrowserImageUploadUrl = '/admin/ajax/ckupload.php?type=Images';
	config.filebrowserUploadUrl      = '/admin/ajax/ckupload.php?type=Files';

	// Drag-and-drop + paste image support (requires uploadimage plugin; safe
	// to set even if plugin is absent — the setting is simply ignored).
	config.imageUploadUrl  = '/admin/ajax/ckupload.php?type=Images&responseType=json';
};
