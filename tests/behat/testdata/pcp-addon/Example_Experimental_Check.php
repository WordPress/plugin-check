<?php

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;

class Example_Experimental_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Experimental_Check;

	public function get_categories() {
		return array( 'new_category' );
	}

	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_preg_match( '#experimental#', $php_files );
		if ( $file ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Experimental text found.', 'pcp-addon' ),
				'experimental_text_detected',
				$file,
				0,
				0,
				'',
				7
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
