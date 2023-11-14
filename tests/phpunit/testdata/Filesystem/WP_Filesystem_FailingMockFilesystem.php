<?php
/**
 * This file needs to be in the global namespace due to how WordPress requires loading it.
 *
 * @package plugin-check
 */

require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php';

/**
 * Simple mock for failing filesystem, limited to working with concrete file paths.
 * No support for hierarchy or parent directories etc.
 *
 * Could be expanded in the future if needed.
 */
class WP_Filesystem_FailingMockFilesystem extends WP_Filesystem_Base {

	private $file_contents = array();

	public function get_contents( $file ) {
		return false;
	}

	public function get_contents_array( $file ) {
		return false;
	}

	public function put_contents( $file, $contents, $mode = false ) {
		return false;
	}

	public function cwd() {
		return false;
	}

	public function chdir( $dir ) {
		return false;
	}

	public function chgrp( $file, $group, $recursive = false ) {
		return false;
	}

	public function chmod( $file, $mode = false, $recursive = false ) {
		return false;
	}

	public function owner( $file ) {
		return false;
	}

	public function group( $file ) {
		return false;
	}

	public function copy( $source, $destination, $overwrite = false, $mode = false ) {
		return false;
	}

	public function move( $source, $destination, $overwrite = false ) {
		return false;
	}

	public function delete( $file, $recursive = false, $type = false ) {
		return false;
	}

	public function exists( $path ) {
		return false;
	}

	public function is_file( $file ) {
		return false;
	}

	public function is_dir( $path ) {
		return false;
	}

	public function is_readable( $file ) {
		return false;
	}

	public function is_writable( $path ) {
		return true;
	}

	public function atime( $file ) {
		return false;
	}

	public function mtime( $file ) {
		return false;
	}

	public function size( $file ) {
		return false;
	}

	public function touch( $file, $time = 0, $atime = 0 ) {
		return false;
	}

	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
		return false;
	}

	public function rmdir( $path, $recursive = false ) {
		return false;
	}

	public function dirlist( $path, $include_hidden = true, $recursive = false ) {
		return false;
	}
}
