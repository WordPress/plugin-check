<?php
namespace WordPressdotorg\Plugin_Check\Checks;

class Header extends Check_Base {
	const NAME = 'Header Checks';

	function check_textdomain( $args ) {
		$return = array();

		if (
			! empty( $args['slug'] ) &&
			! empty( $args['headers']['TextDomain'] ) &&
			$args['slug'] !== $args['headers']['TextDomain']
		) {
			$return[] = new Warning( "TextDomain header in plugin file does not match slug." );
		}

		return $return ?: true;
	}
}