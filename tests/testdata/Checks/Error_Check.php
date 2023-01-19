<?php

namespace WordPress\Plugin_Check\Tests;

use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Result;

class Error_Check implements Check {
	public function run( Check_Result $check_result ) {
		$check_result->add_message(
			true,
			'Error message',
			array(
				'code'   => 'check_error',
				'file'   => 'error-file.php',
				'line'   => 10,
				'column' => 5,
			)
		);
	}
}
