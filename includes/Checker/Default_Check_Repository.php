<?php
/**
 * Class WordPress\Plugin_Check\Checker\Default_Check_Repository
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Default Check Repository class.
 *
 * @since n.e.x.t
 */
class Default_Check_Repository implements Check_Repository {

	/**
	 * Array map holding all runtime checks.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $runtime_checks = array();

	/**
	 * Array map holding all static checks.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $static_checks = array();

	/**
	 * Registers a check to the repository.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $slug  The checks slug.
	 * @param Check  $check The Check instance.
	 *
	 * @throws Exception Thrown if Check does not use correct interface, or slug already exists.
	 */
	public function register_check( $slug, Check $check ) {
		if ( ! $check instanceof Runtime_Check && ! $check instanceof Static_Check ) {
			throw new Exception( __( 'Check must be an instance of Runtime_Check or Static_Check.', 'plugin-check' ) );
		}

		if ( isset( $this->runtime_checks[ $slug ] ) || isset( $this->static_checks[ $slug] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: The Check slug. */
					__( 'Check slug "%s" is already in use.', 'plugin-check' ),
					$slug
				)
			);
		}

		$check_array                   = $check instanceof Runtime_Check ? 'runtime_checks' : 'static_checks';
		$this->{$check_array}[ $slug ] = $check;
	}

	/**
	 * Returns an array of checks.
	 *
	 * @since n.e.x.t
	 *
	 * @param int   $flags       The check type flag.
	 * @param array $check_slugs An array of check slugs to return.
	 * @return array An indexed array of check instances.
	 *
	 * @throws Exception Thrown when invalid flag is passed, or Check slug does not exist.
	 */
	public function get_checks( $flags = self::TYPE_ALL, array $check_slugs = array() ) {
		$checks = array();

		if ( $flags & self::TYPE_STATIC ) {
			$checks += $this->static_checks;
		}

		if ( $flags & self::TYPE_RUNTIME ) {
			$checks += $this->runtime_checks;
		}

		// Filter out the specific check slugs requested.
		if ( ! empty( $check_slugs ) ) {
			$checks = array_map(
				function ( $slug ) use ( $checks ) {
					if ( ! isset( $checks[ $slug ] ) ) {
						throw new Exception(
							sprintf(
								/* translators: %s: The Check slug. */
								__( 'Check with the slug "%s" does not exist.', 'plugin-check' ),
								$slug
							)
						);
					}

					return $checks[ $slug ];
				},
				$check_slugs
			);
		}

		return array_values( $checks );
	}
}
