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
	 * Returns a new check collection containing the subset of checks based on the given check slugs.
	 *
	 * If the given list is empty, the same collection will be returned without any change.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function include( array $check_slugs ): Check_Collection;
}
