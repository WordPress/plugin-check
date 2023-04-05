<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Header extends Check_Base {
	function check_textdomain( $args ) {
		$return = array();

		if (
			! empty( $args['slug'] ) &&
			! empty( $args['headers']['TextDomain'] ) &&
			$args['slug'] !== $args['headers']['TextDomain']
		) {
			return new Warning(
				'textdomain_mismatch',
				'TextDomain header in plugin file does not match slug.'
			);
		}
	}
}