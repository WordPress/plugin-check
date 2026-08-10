<?php
/**
 * Plugin Name: Test Plugin Write File Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin that writes files correctly to uploads directory.
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-write-file-without-errors
 *
 * @package test-plugin-write-file-without-errors
 */

// Example 1: Writing to uploads directory (correct).
function test_write_to_uploads() {
	$upload_dir = wp_upload_dir();
	$file_path  = $upload_dir['basedir'] . '/my-plugin/data.txt';
	
	if ( ! file_exists( dirname( $file_path ) ) ) {
		wp_mkdir_p( dirname( $file_path ) );
	}
	
	file_put_contents( $file_path, 'Some data' );
}

// Example 2: Using temporary directory (correct).
function test_write_to_temp() {
	$temp_file = wp_tempnam( 'my-plugin-' );
	fwrite( fopen( $temp_file, 'w' ), 'Temporary data' );
}

// Example 3: Writing to uploads with proper path handling.
function test_write_with_upload_dir() {
	$upload_dir = wp_upload_dir();
	$plugin_dir = $upload_dir['basedir'] . '/test-plugin-data';
	
	if ( ! file_exists( $plugin_dir ) ) {
		wp_mkdir_p( $plugin_dir );
	}
	
	$log_file = $plugin_dir . '/debug.log';
	fputs( fopen( $log_file, 'a' ), 'Log entry' );
}

// Example 4: Copying to uploads directory (correct).
function test_copy_to_uploads() {
	$upload_dir = wp_upload_dir();
	$source     = '/tmp/file.txt';
	$dest       = $upload_dir['basedir'] . '/my-plugin/backup.txt';
	
	copy( $source, $dest );
}

// Example 5: Moving files to uploads directory (correct).
function test_move_to_uploads() {
	$upload_dir = wp_upload_dir();
	$source     = '/tmp/temp.txt';
	$dest       = $upload_dir['basedir'] . '/my-plugin/moved.txt';
	
	rename( $source, $dest );
}

// Example 6: Using get_temp_dir (correct).
function test_write_to_get_temp_dir() {
	$temp_dir = get_temp_dir();
	$file     = $temp_dir . 'my-temp-file.txt';
	touch( $file );
}

// Example 7: Saving to database instead of file (correct approach).
function test_save_to_database() {
	update_option( 'my_plugin_data', array( 'key' => 'value' ) );
}

// Example 8: Using WP_CONTENT_DIR uploads subpath (correct).
function test_write_to_uploads_via_content_dir() {
	file_put_contents( WP_CONTENT_DIR . '/uploads/my-plugin/data.txt', 'Some data' );
}
