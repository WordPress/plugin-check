<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Vulnerable_Packages extends Check_Base {
	function check_composer_packages() {
		// Find composer.lock
		// composer audit -f json --locked
	}

	function check_package_json() {
		// Find package.json
		// npm audit --json
	}
}
