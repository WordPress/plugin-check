<?php
/**
 * Class WordPress\Plugin_Check\Traits\Mode_Aware
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Mode Aware Trait.
 *
 * @since 1.7.0
 */
trait Mode_Aware {

	/**
	 * Gets the current mode from the check result.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result.
	 * @return string The current mode, defaults to 'new'.
	 */
	protected function get_mode_from_result( Check_Result $result ): string {
		// Get mode from the check context.
		$context = $result->plugin();

		if ( method_exists( $context, 'get_mode' ) ) {
			return $context->get_mode();
		}

		return 'new';
	}

	/**
	 * Checks if the current mode is 'update'.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result.
	 * @return bool True if the mode is 'update', false otherwise.
	 */
	protected function is_update_mode( Check_Result $result ): bool {
		return 'update' === $this->get_mode_from_result( $result );
	}

	/**
	 * Checks if the current mode is 'new'.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result.
	 * @return bool True if the mode is 'new', false otherwise.
	 */
	protected function is_new_mode( Check_Result $result ): bool {
		return 'new' === $this->get_mode_from_result( $result );
	}
}
