<?php

namespace WordPress\Plugin_Check\Test_Data;

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Experimental_Check;
use WordPress\Plugin_Check\Checker\Static_Check;

class Experimental_Static_Check implements Static_Check {

	use Experimental_Check;

	public function run( Check_Result $check_result ) {
		return;
	}
}
