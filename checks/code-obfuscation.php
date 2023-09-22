<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Code_Obfuscation extends Check_Base {

	function check_zend_guard() {
		if (
			$this->scan_matching_files_for_needle( '<?php @Zend;', '\.php$' ) ||
			$this->scan_matching_files_for_needle( 'This file was encoded by', '\.php$' )
		) {
			return new Error(
				'obfuscated_code_detected',
				sprintf(
					__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
					'Zend Guard'
				)
			);
		}
	}

	function check_sourcegardian() {
		$needles = [
			'sourceguardian.com',
			"function_exists('sg_load')",
			'$__x=',
		];

		foreach ( $needles as $needle ) {
			if ( $this->scan_matching_files_for_needle( $needle, '\.php$' ) ) {
				return new Error(
					'obfuscated_code_detected',
					sprintf(
						__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
						'Source Gardian'
					)
				);
			}
		}
	}

	function check_ioncube() {
		if ( $this->scan_matching_files_for_needle( 'ionCube', '\.php$' ) ) {
			return new Error(
				'obfuscated_code_detected',
				sprintf(
					__( 'Code Obfuscation tools are not permitted. Detected: %s', 'plugin-check' ),
					'ionCube'
				)
			);
		}
	}

}
