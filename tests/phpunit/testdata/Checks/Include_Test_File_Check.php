<?php
/**
 * Test check class for include/exclude file filtering tests.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Concrete file check that captures the file list for testing.
 *
 * @since 1.9.0
 */
class Include_Test_File_Check extends Abstract_File_Check {

	use Stable_Check;

	/**
	 * Files passed to check_files().
	 *
	 * @var array
	 */
	public $files_checked = array();

	public function get_categories() {
		return array( 'test' );
	}

	protected function check_files( Check_Result $result, array $files ) {
		$this->files_checked = $files;
	}

	public function get_description(): string {
		return 'Test check for include/exclude file filtering.';
	}

	public function get_documentation_url(): string {
		return '';
	}
}
