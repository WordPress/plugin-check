<?php
/**
 * Class WordPress\Plugin_Check\Checker\Default_Check_Collection
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use ArrayIterator;
use Exception;
use Traversable;

/**
 * Default Check Collection class.
 *
 * @since n.e.x.t
 */
class Default_Check_Collection implements Check_Collection {

	/**
	 * Map of `$check_slug => $check_obj` pairs.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $checks;

	/**
	 * List of check slugs, in the same order as `$checks` - effectively the keys of that array.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	private $slugs;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks Map of `$check_slug => $check_obj` pairs for the collection.
	 */
	public function __construct( array $checks ) {
		$this->checks = $checks;
		$this->slugs  = array_keys( $this->checks );
	}

	/**
	 * Returns the raw indexed array representation of this collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return array The indexed array of check objects.
	 */
	public function to_array(): array {
		return array_values( $this->checks );
	}

	/**
	 * Returns the raw map of check slugs and their check objects as a representation of this collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return array Map of `$check_slug => $check_obj` pairs.
	 */
	public function to_map(): array {
		return $this->checks;
	}

	/**
	 * Returns a new check collection containing the subset of checks based on the given check filter function.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable $filter_fn Filter function that accepts a single check object and should return a boolean for
	 *                            whether to include the check in the new collection.
	 * @return Check_Collection New check collection, effectively a subset of this one.
	 */
	public function filter( callable $filter_fn ): Check_Collection {
		return new self(
			array_filter(
				$this->checks,
				$filter_fn
			)
		);
	}

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
	public function include( array $check_slugs ): Check_Collection {
		// Return unmodified collection if no check slugs to limit to are given.
		if ( ! $check_slugs ) {
			return $this;
		}

		$check_slugs = array_flip( $check_slugs );

		$checks = array();
		foreach ( $this->checks as $slug => $check ) {
			if ( ! isset( $check_slugs[ $slug ] ) ) {
				continue;
			}

			$checks[ $slug ] = $check;
		}

		return new self( $checks );
	}

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
	public function require( array $check_slugs ): Check_Collection {
		foreach ( $check_slugs as $slug ) {
			if ( ! isset( $this->checks[ $slug ] ) ) {
				throw new Exception(
					sprintf(
						/* translators: %s: The Check slug. */
						__( 'Check with the slug "%s" does not exist.', 'plugin-check' ),
						$slug
					)
				);
			}
		}

		return $this;
	}

	/**
	 * Counts the checks in the collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return int Number of checks in the collection.
	 */
	public function count(): int {
		return count( $this->checks );
	}

	/**
	 * Returns an iterator for the checks in the collection.
	 *
	 * @since n.e.x.t
	 *
	 * @return Traversable Checks iterator.
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( $this->checks );
	}

	/**
	 * Checks whether a check exists with the given slug or index.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|int $offset Either a check slug (string) or index (integer).
	 * @return bool True if a check exists at the given slug or index, false otherwise.
	 */
	public function offsetExists( $offset ) {
		if ( is_string( $offset ) ) {
			return isset( $this->checks[ $offset ] );
		}

		return isset( $this->slugs[ $offset ] );
	}

	/**
	 * Retrieves the check with the given slug or index.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|int $offset Either a check slug (string) or index (integer).
	 * @return Check|null Check with the given slug or index, or null if it does not exist.
	 */
	public function offsetGet( $offset ) {
		if ( is_string( $offset ) ) {
			if ( isset( $this->checks[ $offset ] ) ) {
				return $this->checks[ $offset ];
			}
			return null;
		}

		if ( isset( $this->slugs[ $offset ] ) ) {
			return $this->checks[ $this->slugs[ $offset ] ];
		}

		return null;
	}

	/**
	 * Sets a check in the collection.
	 *
	 * This method does nothing as the collection is read-only.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|int $offset Either a check slug (string) or index (integer).
	 * @param mixed      $value  Value to set.
	 */
	public function offsetSet( $offset, $value ) {
		// Not implemented as this is a read-only collection.
	}

	/**
	 * Removes a check from the collection.
	 *
	 * This method does nothing as the collection is read-only.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|int $offset Either a check slug (string) or index (integer).
	 */
	public function offsetUnset( $offset ) {
		// Not implemented as this is a read-only collection.
	}
}
