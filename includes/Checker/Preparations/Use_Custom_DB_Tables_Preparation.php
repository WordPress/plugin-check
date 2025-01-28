<?php
/**
 * Class WordPress\Plugin_Check\Checker\Preparations\Use_Custom_DB_Tables_Preparation
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Preparations;

use Exception;
use WordPress\Plugin_Check\Checker\Preparation;
use WordPress\Plugin_Check\Traits\Amend_DB_Base_Prefix;

/**
 * Class for the preparation step to use the custom database tables.
 *
 * This ensures no side effects on the actual database tables are possible.
 *
 * @since 1.3.0
 */
class Use_Custom_DB_Tables_Preparation implements Preparation {
	use Amend_DB_Base_Prefix;

	/**
	 * Runs this preparation step for the environment and returns a cleanup function.
	 *
	 * @since 1.3.0
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown when preparation fails.
	 */
	public function prepare() {
		return $this->amend_db_base_prefix();
	}
}
