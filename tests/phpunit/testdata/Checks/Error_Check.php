<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Checker\Static_Check;

class Error_Check implements Static_Check {

	use Stable_Check;

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
