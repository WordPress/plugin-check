<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Security\Private_Upload_Protection_Check;

class Test_Private_Upload_Protection_Check extends Private_Upload_Protection_Check {

	public function inspect_files( Check_Result $result, array $files ) {
		$this->check_files( $result, $files );
	}
}
