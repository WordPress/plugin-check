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
 * @since 1.0.0
 */
interface Check_Repository {

	/**
	 * Bitwise flag for static type checks.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TYPE_STATIC = 1;

	/**
	 * Bitwise flag for runtime type checks.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TYPE_RUNTIME = 2;

	/**
	 * Bitwise flag for all check types.
	 *
	 * This is the same as `TYPE_STATIC | TYPE_RUNTIME`.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TYPE_ALL = 3;

	/**
	 * Bitwise flag for experimental checks.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const INCLUDE_EXPERIMENTAL = 4;

	/**
	 * Registers a check to the repository.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug  The checks slug.
	 * @param Check  $check The Check instance.
	 */
	public function register_check( $slug, Check $check );

	/**
	 * Returns an array of checks.
	 *
	 * @since 1.0.0
	 *
	 * @param int $flags The check type flag.
	 * @return Check_Collection Check collection providing an indexed array of check instances.
	 */
	public function get_checks( $flags = self::TYPE_ALL );
}
