<?php
/**
 * Class WordPress\Plugin_Check\Checker\Abstract_Check_runner
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;
use WordPress\Plugin_Check\Checker\Preparations\Universal_Runtime_Preparation;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * Abstract Check Runner class.
 *
 * @since n.e.x.t
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Abstract_Check_Runner implements Check_Runner {

	/**
	 * True if the class was initialized early in the WordPress load process.
	 *
	 * @since n.e.x.t
	 * @var bool
	 */
	protected $initialized_early;

	/**
	 * The check slugs to run.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $check_slugs;

	/**
	 * The check slugs to ignore.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $check_ignore_slugs;

	/**
	 * The plugin parameter.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $plugin;

	/**
	 * An instance of the Checks class.
	 *
	 * @since n.e.x.t
	 * @var Checks
	 */
	protected $checks;

	/**
	 * The plugin basename to check.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * An instance of the Check_Repository.
	 *
	 * @since n.e.x.t
	 * @var Check_Repository
	 */
	private $check_repository;

	/**
	 * Runtime environment.
	 *
	 * @since n.e.x.t
	 * @var Runtime_Environment_Setup
	 */
	protected $runtime_environment;

	/**
	 * Whether to include experimental checks.
	 *
	 * @since n.e.x.t
	 * @var bool
	 */
	protected $include_experimental;

	/**
	 * Checks category for the filter.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	protected $check_categories;

	/**
	 * Returns the plugin parameter based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin parameter from the request.
	 */
	abstract protected function get_plugin_param();

	/**
	 * Returns an array of Check slugs to run based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs.
	 */
	abstract protected function get_check_slugs_param();
	/**
	 * Returns an array of Check slugs to ignore based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of Check slugs.
	 */
	abstract protected function get_check_ignore_slugs_param();

	/**
	 * Returns the include experimental parameter based on the request.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool Returns true to include experimental checks else false.
	 */
	abstract protected function get_include_experimental_param();

	/**
	 * Returns an array of categories for filtering the checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of categories.
	 */
	abstract protected function get_categories_param();

	/**
	 * Sets whether the runner class was initialized early.
	 *
	 * @since n.e.x.t
	 */
	final public function __construct() {
		$this->initialized_early = ! did_action( 'muplugins_loaded' );
		$this->register_checks();
		$this->runtime_environment = new Runtime_Environment_Setup();
	}

	/**
	 * Sets the check slugs to be run.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $check_slugs An array of check slugs to be run.
	 *
	 * @throws Exception Thrown if the checks do not match those in the original request.
	 */
	final public function set_check_slugs( array $check_slugs ) {
		if ( $this->initialized_early ) {
			// Compare the check slugs to see if there was an error.
			if ( $check_slugs !== $this->get_check_slugs_param() ) {
				throw new Exception(
					__( 'Invalid checks: The checks to run do not match the original request.', 'plugin-check' )
				);
			}
		}

		$this->check_slugs = $check_slugs;
	}

	/**
	 * Sets the check slugs to be ignored.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $check_slugs An array of check slugs to be ignored.
	 *
	 * @throws Exception Thrown if the checks do not match those in the original request.
	 */
	final public function set_check_ignore_slugs( array $check_slugs ) {
		if ( $this->initialized_early ) {
			// Compare the check slugs to see if there was an error.
			if ( $check_slugs !== $this->get_check_ignore_slugs_param() ) {
				throw new Exception(
					__( 'Invalid checks: The checks to ignore do not match the original request.', 'plugin-check' )
				);
			}
		}

		$this->check_ignore_slugs = $check_slugs;
	}

	/**
	 * Sets the plugin slug or basename to be checked.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $plugin The plugin slug or basename to be checked.
	 *
	 * @throws Exception Thrown if the plugin set does not match the original request parameter.
	 */
	final public function set_plugin( $plugin ) {
		if ( $this->initialized_early ) {
			// Compare the plugin parameter to see if there was an error.
			if ( $plugin !== $this->get_plugin_param() ) {
				throw new Exception(
					__( 'Invalid plugin: The plugin set does not match the original request parameter.', 'plugin-check' )
				);
			}
		}

		$this->plugin = $plugin;
	}

	/**
	 * Sets whether to include experimental checks in the process.
	 *
	 * @since n.e.x.t
	 *
	 * @param bool $include_experimental True to include experimental checks. False to exclude.
	 *
	 * @throws Exception Thrown if the flag set does not match the original request parameter.
	 */
	final public function set_experimental_flag( $include_experimental ) {
		if ( $this->initialized_early ) {
			if ( $include_experimental !== $this->get_include_experimental_param() ) {
				throw new Exception(
					sprintf(
						/* translators: %s: include-experimental */
						__( 'Invalid flag: The %s value does not match the original request parameter.', 'plugin-check' ),
						'include-experimental'
					)
				);
			}
		}

		$this->include_experimental = $include_experimental;
	}

	/**
	 * Sets categories for filtering the checks.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $categories An array of categories for filtering.
	 *
	 * @throws Exception Thrown if the getegories does not match the original request parameter.
	 */
	final public function set_categories( $categories ) {
		if ( $this->initialized_early ) {
			if ( $categories !== $this->get_categories_param() ) {
				throw new Exception(
					sprintf(
						/* translators: %s: categories */
						__( 'Invalid categories: The %s value does not match the original request parameter.', 'plugin-check' ),
						'categories'
					)
				);
			}
		}
		$this->check_categories = $categories;
	}

	/**
	 * Prepares the environment for running the requested checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return callable Cleanup function to revert any changes made here.
	 *
	 * @throws Exception Thrown exception when preparation fails.
	 */
	final public function prepare() {
		if ( $this->has_runtime_check( $this->get_checks_to_run() ) ) {
			$preparation = new Universal_Runtime_Preparation( $this->get_check_context() );
			return $preparation->prepare();
		}

		return function () {};
	}

	/**
	 * Runs the checks against the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Result An object containing all check results.
	 */
	final public function run() {
		global $wpdb, $table_prefix;
		$checks       = $this->get_checks_to_run();
		$preparations = $this->get_shared_preparations( $checks );
		$cleanups     = array();
		$old_prefix   = null;

		// Set the correct test database prefix if required.
		if ( $this->has_runtime_check( $checks ) ) {
			$old_prefix = $wpdb->set_prefix( $table_prefix . 'pc_' );
		}

		// Prepare all shared preparations.
		foreach ( $preparations as $preparation ) {
			$instance   = new $preparation['class']( ...$preparation['args'] );
			$cleanups[] = $instance->prepare();
		}

		$results = $this->get_checks_instance()->run_checks( $this->get_check_context(), $checks );

		if ( ! empty( $cleanups ) ) {
			foreach ( $cleanups as $cleanup ) {
				$cleanup();
			}
		}

		// Restore the old prefix.
		if ( $old_prefix ) {
			$wpdb->set_prefix( $old_prefix );
		}

		return $results;
	}

	/**
	 * Determines if any of the checks are a runtime check.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of check instances to run.
	 * @return bool Returns true if one or more checks is a runtime check.
	 */
	private function has_runtime_check( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns all shared preparations used by the checks to run.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks An array of Check instances to run.
	 * @return array An array of Preparations to run where each item is an array with keys `class` and `args`.
	 */
	private function get_shared_preparations( array $checks ) {
		$shared_preparations = array();

		foreach ( $checks as $check ) {
			if ( ! $check instanceof With_Shared_Preparations ) {
				continue;
			}

			$preparations = $check->get_shared_preparations();

			foreach ( $preparations as $class => $args ) {
				$key = $class . '::' . md5( json_encode( $args ) );

				if ( ! isset( $shared_preparations[ $key ] ) ) {
					$shared_preparations[ $key ] = array(
						'class' => $class,
						'args'  => $args,
					);
				}
			}
		}

		return array_values( $shared_preparations );
	}

	/**
	 * Returns the Check instances to run.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array map of check slugs to Check instances.
	 *
	 * @throws Exception Thrown when invalid flag is passed, or Check slug does not exist.
	 */
	final public function get_checks_to_run() {
		// Include file to use is_plugin_active() in CLI context.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$check_slugs = $this->get_check_slugs();
		$check_flags = Check_Repository::TYPE_STATIC;

		// Check if conditions are met in order to perform Runtime Checks.
		if ( ( $this->initialized_early || $this->runtime_environment->can_set_up() ) && is_plugin_active( $this->get_plugin_basename() ) ) {
			$check_flags = Check_Repository::TYPE_ALL;
		}

		// Check whether to include experimental checks.
		if ( $this->get_include_experimental() ) {
			$check_flags = $check_flags | Check_Repository::INCLUDE_EXPERIMENTAL;
		}

		$excluded_checks = $this->get_check_ignore_slugs();

		$collection = $this->check_repository->get_checks( $check_flags )
			->include( $check_slugs ) // Ensures only the checks with the given slugs are included.
			->exclude( $excluded_checks ); // Exclude provided checks from list.

		// Filters the checks by specific categories.
		$categories = $this->get_categories();
		if ( $categories ) {
			$collection = Check_Categories::filter_checks_by_categories( $collection, $categories );
		}

		return $collection
			->require( $check_slugs ) // Ensures all of the given slugs are valid.
			->to_map();
	}

	/**
	 * Creates and returns the Check instance.
	 *
	 * @since n.e.x.t
	 *
	 * @return Checks An instance of the Checks class.
	 *
	 * @throws Exception Thrown if the plugin slug is invalid.
	 */
	protected function get_checks_instance() {
		if ( null !== $this->checks ) {
			return $this->checks;
		}

		$this->checks = new Checks();

		return $this->checks;
	}

	/**
	 * Returns the check slugs to run.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of check slugs to run.
	 */
	private function get_check_slugs() {
		if ( null !== $this->check_slugs ) {
			return $this->check_slugs;
		}

		return $this->get_check_slugs_param();
	}

	/**
	 * Returns the check slugs to ignore.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of check slugs to ignore.
	 */
	private function get_check_ignore_slugs() {
		if ( null !== $this->check_ignore_slugs ) {
			return $this->check_ignore_slugs;
		}

		return $this->get_check_ignore_slugs_param();
	}

	/**
	 * Returns the plugin basename.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The plugin basename to check.
	 */
	final public function get_plugin_basename() {
		if ( null === $this->plugin_basename ) {
			$plugin                = null !== $this->plugin ? $this->plugin : $this->get_plugin_param();
			$this->plugin_basename = Plugin_Request_Utility::get_plugin_basename_from_input( $plugin );
		}

		return $this->plugin_basename;
	}

	/**
	 * Returns the value for the include experimental flag.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if experimental checks are included. False if not.
	 */
	final protected function get_include_experimental() {
		if ( null !== $this->include_experimental ) {
			return $this->include_experimental;
		}

		return $this->get_include_experimental_param();
	}

	/**
	 * Returns an array of categories for filtering the checks.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An array of categories.
	 */
	final protected function get_categories() {
		if ( null !== $this->check_categories ) {
			return $this->check_categories;
		}

		return $this->get_categories_param();
	}

	/** Gets the Check_Context for the plugin.
	 *
	 * @since n.e.x.t
	 *
	 * @return Check_Context The check context for the plugin file.
	 */
	private function get_check_context() {
		return new Check_Context( WP_PLUGIN_DIR . '/' . $this->get_plugin_basename() );
	}

	/**
	 * Registers Checks to the Check_Repository.
	 *
	 * @since n.e.x.t
	 */
	private function register_checks() {
		$this->check_repository = new Default_Check_Repository();

		/**
		 * Filters the available plugin check classes.
		 *
		 * @since n.e.x.t
		 *
		 * @param array $checks An array map of check slugs to Check instances.
		 */
		$checks = apply_filters(
			'wp_plugin_check_checks',
			array(
				'i18n_usage'                 => new Checks\I18n_Usage_Check(),
				'enqueued_scripts_size'      => new Checks\Enqueued_Scripts_Size_Check(),
				'code_obfuscation'           => new Checks\Code_Obfuscation_Check(),
				'file_type'                  => new Checks\File_Type_Check(),
				'plugin_header_text_domain'  => new Checks\Plugin_Header_Text_Domain_Check(),
				'late_escaping'              => new Checks\Late_Escaping_Check(),
				'plugin_updater'             => new Checks\Plugin_Updater_Check(),
				'plugin_review_phpcs'        => new Checks\Plugin_Review_PHPCS_Check(),
				'performant_wp_query_params' => new Checks\Performant_WP_Query_Params_Check(),
				'enqueued_scripts_in_footer' => new Checks\Enqueued_Scripts_In_Footer_Check(),
				'plugin_readme'              => new Checks\Plugin_Readme_Check(),
				'enqueued_styles_scope'      => new Checks\Enqueued_Styles_Scope_Check(),
				'localhost'                  => new Checks\Localhost_Check(),
				'no_unfiltered_uploads'      => new Checks\No_Unfiltered_Uploads_Check(),
				'trademarks'                 => new Checks\Trademarks_Check(),
			)
		);

		foreach ( $checks as $slug => $check ) {
			$this->check_repository->register_check( $slug, $check );
		}
	}

	/**
	 * Sets the runtime environment setup.
	 *
	 * @since n.e.x.t
	 *
	 * @param Runtime_Environment_Setup $runtime_environment_setup Runtime environment instance.
	 */
	final public function set_runtime_environment_setup( $runtime_environment_setup ) {
		$this->runtime_environment = $runtime_environment_setup;
	}
}
