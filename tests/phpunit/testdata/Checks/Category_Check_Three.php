<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class Category_Check_Three implements Static_Check {

	use Stable_Check;

	public function run( Check_Result $check_result ) {
		return;
	}

	public function get_categories() {
		return array( Check_Categories::CATEGORY_SECURITY );
	}
}
