<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Traits\Experimental_Check;

class Experimental_Runtime_Check implements Runtime_Check {

	use Experimental_Check;

	public function run( Check_Result $check_result ) {
		return;
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
