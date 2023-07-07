<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Runtime_Check as Runtime_Check_Interface;
use WordPress\Plugin_Check\Traits\Stable_Check;

class Runtime_Check implements Runtime_Check_Interface {

	use Stable_Check;

	public function run( Check_Result $check_result ) {
		return;
	}

	public function get_category() {
		return Check_Categories::CATEGORY_GENERAL;
	}
}
