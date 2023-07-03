<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;

class Invalid_Check implements Check {
	public function run( Check_Result $check_result ) {
		return;
	}

	public function get_category() {
		return Check_Categories::CATEGORY_GENERAL;
	}
}
