<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Collection
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use WordPress\Plugin_Check\Checker\Exception\Invalid_Check_Slug_Exception;

/**
 * Check Collection interface.
 *
 * @since 1.0.0
 */
interface Check_Collection extends ArrayAccess, Countable, IteratorAggregate {

	/**
	 * Returns the raw indexed array representation of this collection.
	 *
	 * @since 1.0.0
	 *
	 * @return array The indexed array of check objects.
	 */
	public function to_array(): array;

	/**
	 * Returns the raw map of check slugs and their check objects as a representation of this collection.
	 *
	 * @since 1.0.0
	 *
	 * @return array Map of `$check_slug => $check_obj` pairs.
	 */
	public function to_map(): array;

	/**
	 * Returns a new check collection containing the subset of checks based on the given check filter function.
	 *
	 * @since 1.0.0
	 *
	 * @phpstan-param callable(Check,string): bool $filter_fn
	 *
	 * @param callable $filter_fn Filter function that accepts a Check object and a Check slug and
	 *                            should return a boolean for whether to include the check in the new collection.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function filter( callable $filter_fn ): Check_Collection;

	/**
	 * Returns a new check collection containing the subset of checks based on the given check slugs.
	 *
	 * If the given list is empty, the same collection will be returned without any change.
	 *
	 * @since 1.0.0
	 *
	 * @param array $check_slugs List of slugs to limit to only those. If empty, the same collection is returned.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function include( array $check_slugs ): Check_Collection;

	/**
	 * Returns a new check collection excluding the provided checks.
	 *
	 * If the given list is empty, the same collection will be returned without any change.
	 *
	 * @since 1.0.0
	 *
	 * @param array $check_slugs List of slugs to exclude. If empty, the same collection is returned.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function exclude( array $check_slugs ): Check_Collection;

	/**
	 * Throws an exception if any of the given check slugs are not present, or returns the same collection otherwise.
	 *
	 * @since 1.0.0
	 *
	 * @param array $check_slugs List of slugs to limit to only those. If empty, the same collection is returned.
	 * @return Check_Collection The unchanged check collection.
	 *
	 * @throws Invalid_Check_Slug_Exception Thrown when any of the given check slugs is not present in the collection.
	 */
	public function require( array $check_slugs ): Check_Collection;
}
