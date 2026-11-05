<?php
/**
 * Plugin Name:       Fclose PHP Output Test
 * Description:       A test plugin to verify the Plugin Check plugin's fclose() false positive fix — fclose(php://output) should not trigger an AlternativeFunctions sniff.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            Plugin Check QA
 * Text Domain:       fclose-php-output-test
 */

// This fclose() call on php://output is a common WordPress pattern
// used to end output buffering. It should NOT trigger the
// WordPress.WP.AlternativeFunctions.file_system_operations_fclose sniff
// because php://output is not a file system resource.
function send_csv_download() {
	$handle = fopen( 'php://output', 'w' );
	fwrite( $handle, 'name,email' . PHP_EOL );
	fwrite( $handle, 'John,john@example.com' . PHP_EOL );
	fclose( $handle );
}

// This fclose() on a real file SHOULD still trigger the sniff.
function read_log_file() {
	$handle = fopen( WP_CONTENT_DIR . '/debug.log', 'r' );
	if ( $handle ) {
		$contents = fread( $handle, 1024 );
		fclose( $handle );
		return $contents;
	}
	return '';
}
