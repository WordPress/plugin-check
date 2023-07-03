<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check as Static_Check_Interface;

class Static_Check implements Static_Check_Interface {
	public function run( Check_Result $check_result ) {
		return;
	}

	public function get_category() {
		return Check_Categories::CATEGORY_GENERAL;
	}
}
