<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;

class Category_Check_Four implements Static_Check {
	public function run( Check_Result $check_result ) {
		return;
	}

	public function get_category() {
		return Check_Categories::CATEGORY_PERFORMANCE;
	}
}
