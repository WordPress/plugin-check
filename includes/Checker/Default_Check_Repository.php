<?php
/**
 * Class WordPress\Plugin_Check\Checker\Default_Check_Repository
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Default Check Repository class.
 *
 * @since n.e.x.t
 */
class Default_Check_Repository extends Empty_Check_Repository {

	/**
	 * Initializes checks.
	 *
	 * @since n.e.x.t
	 */
	public function __construct() {
		$this->register_default_checks();
	}

	/**
	 * Registers Checks.
	 *
	 * @since n.e.x.t
	 */
	private function register_default_checks() {
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
				'direct_db_queries'          => new Checks\Direct_DB_Queries_Check(),
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
			$this->register_check( $slug, $check );
		}
	}
}
