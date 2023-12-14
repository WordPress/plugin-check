<?php
/**
 * Class WordPress\Plugin_Check\Checker\Empty_Check_Repository
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Empty Check Repository class.
 *
 * @since n.e.x.t
 */
class Empty_Check_Repository implements Check_Repository {

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
	 * Constructor.
	 *
	 * @since n.e.x.t
	 */
	public function __construct() {
	}

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
			throw new Exception(
				sprintf(
					/* translators: %s: The Check slug. */
					__( 'Check with slug "%s" must be an instance of Runtime_Check or Static_Check.', 'plugin-check' ),
					$slug
				)
			);
		}

		if ( isset( $this->runtime_checks[ $slug ] ) || isset( $this->static_checks[ $slug ] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: The Check slug. */
					__( 'Check slug "%s" is already in use.', 'plugin-check' ),
					$slug
				)
			);
		}

		if ( ! $check->get_categories() ) {
			throw new Exception(
				sprintf(
					/* translators: %s: The Check slug. */
					__( 'Check with slug "%s" has no categories associated with it.', 'plugin-check' ),
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
	 * @param int $flags The check type flag.
	 * @return Check_Collection Check collection providing an indexed array of check instances.
	 */
	public function get_checks( $flags = self::TYPE_ALL ) {
		$checks = array();

		if ( $flags & self::TYPE_STATIC ) {
			$checks += $this->static_checks;
		}

		if ( $flags & self::TYPE_RUNTIME ) {
			$checks += $this->runtime_checks;
		}

		// Return all checks, including experimental if requested.
		if ( $flags & self::INCLUDE_EXPERIMENTAL ) {
			return new Default_Check_Collection( $checks );
		}

		// Remove experimental checks before returning.
		return ( new Default_Check_Collection( $checks ) )->filter(
			static function ( Check $check ) {
				return $check->get_stability() !== Check::STABILITY_EXPERIMENTAL;
			}
		);
	}
}
