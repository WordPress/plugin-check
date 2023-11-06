<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Collection
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use ArrayAccess;
use Countable;
use Exception;
use IteratorAggregate;

/**
 * Check Collection interface.
 *
 * @since n.e.x.t
 */
interface Check_Collection extends ArrayAccess, Countable, IteratorAggregate {

	/**
	 * Returns the raw indexed array representation of this collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return array The indexed array of check objects.
	 */
	public function to_array(): array;

	/**
	 * Returns the raw map of check slugs and their check objects as a representation of this collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return array Map of `$check_slug => $check_obj` pairs.
	 */
	public function to_map(): array;

	/**
	 * Returns a new check collection containing the subset of checks based on the given check filter function.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable $filter_fn Filter function that accepts a single check object and should return a boolean for
	 *                            whether to include the check in the new collection.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function filter( callable $filter_fn ): Check_Collection;

	/**
	 * Returns a new check collection containing the subset of checks based on the given check slugs.
	 *
	 * If the given list is empty, the same collection will be returned without any change.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $check_slugs List of slugs to limit to only those. If empty, the same collection is returned.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function include( array $check_slugs ): Check_Collection;

	/**
	 * Throws an exception if any of the given check slugs are not present, or returns the same collection otherwise.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $check_slugs List of slugs to limit to only those. If empty, the same collection is returned.
	 * @return Check_Collection The unchanged check collection.
	 *
	 * @throws Exception Thrown when any of the given check slugs is not present in the collection.
	 */
	public function require( array $check_slugs ): Check_Collection;
}
