<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Localhost extends Check_Base {

	function check_localhost() {
		$found = $this->scan_matching_files_for_needle( '#https?://(localhost|127.0.0.1)#', '\.php$' );
		if ( $found ) {
			return new Error(
				'localhost_code_detected',
				sprintf(
					__( 'Do not use Localhost in your code. Detected: %s', 'plugin-check' ),
					$found
				)
			);
		}
	}

}
