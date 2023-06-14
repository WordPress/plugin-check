<?php
/**
 * Abstract Static_Check_UnitTestCase.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Test_Utils\TestCase;

use WP_UnitTestCase;

abstract class Static_Check_UnitTestCase extends WP_UnitTestCase {
	public function set_up() {
		if ( class_exists( '\PHP_CodeSniffer\Config' ) ) {
			/*
			 * Reset \PHP_CodeSniffer\Config::$overriddenDefaults to prevent
			 * incorrect results when running multiple checks.
			 */
			$reflected_phpcs_config = new \ReflectionClass( '\PHP_CodeSniffer\Config' );
			$overridden_defaults    = $reflected_phpcs_config->getProperty( 'overriddenDefaults' );
			$overridden_defaults->setAccessible( true );
			$overridden_defaults->setValue( array() );
			$overridden_defaults->setAccessible( false );
		}
	}
}
