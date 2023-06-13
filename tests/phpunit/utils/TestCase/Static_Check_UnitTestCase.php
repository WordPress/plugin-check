<?php
/**
 * Abstract Runtime_Check_UnitTestCase.
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
			 *
			 * PHPStan ignore reason: PHPStan raised an issue because we can't
			 * use class in ReflectionClass.
			 *
			 * @phpstan-ignore-next-line
			 */
			$reflected_phpcs_config = new \ReflectionClass( '\PHP_CodeSniffer\Config' );
			if ( $reflected_phpcs_config->hasProperty( 'overriddenDefaults' ) ) {
				$reflected_phpcs_config->setStaticPropertyValue( 'overriddenDefaults', array() );
			}
		}
	}
}
