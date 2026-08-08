<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class Warning_Check implements Static_Check {

	use Stable_Check;

	public function run( Check_Result $check_result ) {
		$check_result->add_message(
			false,
			'Warning message',
			array(
				'code' => 'check_warning',
				'file' => 'vendor/phpseclib/file.php',
			)
		);
		$check_result->add_message(
			false,
			'Outside warning message',
			array(
				'code' => 'check_warning_outside',
				'file' => 'includes/file.php',
			)
		);
		$check_result->add_message(
			true,
			'Error message',
			array(
				'code' => 'check_error',
				'file' => 'vendor/phpseclib/file.php',
			)
		);
	}

	public function get_categories() {
		return array( Check_Categories::CATEGORY_GENERAL );
	}

	public function get_description(): string {
		return '';
	}

	public function get_documentation_url(): string {
		return '';
	}
}
