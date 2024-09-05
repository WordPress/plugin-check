<?php

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

class Example_Static_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	public function get_categories() {
		return array( 'new_category' );
	}

	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_preg_match( '#I\sam\sbad#', $php_files );
		if ( $file ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Prohibited text found.', 'pcp-addon' ),
				'prohibited_text_detected',
				$file,
				0,
				0,
				'',
				8
			);
		}
	}

	public function get_description(): string {
		return '';
	}

	public function get_documentation_url(): string {
		return '';
	}
}
