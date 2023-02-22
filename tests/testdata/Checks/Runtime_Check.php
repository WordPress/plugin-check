<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Runtime_Check as Runtime_Check_Interface;
use WordPress\Plugin_Check\Checker\Check_Result;

class Runtime_Check implements Runtime_Check_Interface {
	public function run( Check_Result $check_result ) {
		return;
	}
}
