<?php
/**
 * Trait WordPress\Plugin_Check\Checker\Check_Name
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use ReflectionClass;

/**
 * Trait for checks name.
 *
 * @since n.e.x.t
 */
trait Check_Name {
	/**
	 * Returns the checks name.
	 *
	 * @since n.e.x.t
	 *
	 * @return string Check name.
	 */
	public function get_name() {
		$reflection = new ReflectionClass( $this );

		$class_name = strtolower( $reflection->getShortName() );
		$class_name = str_replace( '_check', '', $class_name );

		return $class_name;
	}
}
