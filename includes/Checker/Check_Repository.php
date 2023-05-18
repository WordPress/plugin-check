<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Repository
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Check Repository interface.
 *
 * @since n.e.x.t
 */
interface Check_Repository {

	/**
	 * Bitwise flag for static type checks.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	const TYPE_STATIC = 1;

	/**
	 * Bitwise flag for runtime type checks.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	const TYPE_RUNTIME = 2;

	/**
	 * Bitwise flag for all check types.
	 *
	 * This is the same as `TYPE_STATIC | TYPE_RUNTIME`.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	const TYPE_ALL = 3;

	/**
	 * Registers a check to the repository.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $slug  The checks slug.
	 * @param Check  $check The Check instance.
	 */
	public function register_check( $slug, Check $check );

	/**
	 * Returns an array of checks.
	 *
	 * @since n.e.x.t
	 *
	 * @param int   $flags       The check type flag.
	 * @param array $check_slugs An array of check slugs to return.
	 * @return array An indexed array of check instances.
	 */
	public function get_checks( $flags = self::TYPE_ALL, array $check_slugs = array() );
}
