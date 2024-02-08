<?php
/**
 * Trait WordPress\Plugin_Check\Traits\Stable_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

/**
 * Trait for stable checks.
 *
 * @since 1.0.0
 */
trait Stable_Check {
	/**
	 * Returns the checks stability.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of the check stability constant values.
	 */
	public function get_stability() {
		return self::STABILITY_STABLE;
	}
}
