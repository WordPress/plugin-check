<?php
/**
 * Class Plugin_Header_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for plugin header fields.
 *
 * @since 1.2.0
 */
class Plugin_Header_Check implements Static_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.2.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since 1.2.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	public function run( Check_Result $result ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_main_file = $result->plugin()->main_file();
		$plugin_header    = get_plugin_data( $plugin_main_file );

		$labels = array(
			'Name'            => 'Plugin Name',
			'PluginURI'       => 'Plugin URI',
			'Version'         => 'Version',
			'Description'     => 'Description',
			'Author'          => 'Author',
			'AuthorURI'       => 'Author URI',
			'TextDomain'      => 'Text Domain',
			'DomainPath'      => 'Domain Path',
			'Network'         => 'Network',
			'RequiresWP'      => 'Requires at least',
			'RequiresPHP'     => 'Requires PHP',
			'UpdateURI'       => 'Update URI',
			'RequiresPlugins' => 'Requires Plugins',
		);

		if ( ! empty( $plugin_header['Name'] ) ) {
			if ( in_array( $plugin_header['Name'], array( 'Plugin Name', 'My Basics Plugin' ), true ) ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file is not valid.', 'plugin-check' ),
						esc_html( $labels['Name'] )
					),
					'plugin_header_invalid_plugin_name',
					$plugin_main_file,
					0,
					0,
					'',
					6
				);
			}
		}

		if ( ! empty( $plugin_header['PluginURI'] ) ) {
			if ( filter_var( $plugin_header['PluginURI'], FILTER_VALIDATE_URL ) !== $plugin_header['PluginURI'] ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file is not valid.', 'plugin-check' ),
						esc_html( $labels['PluginURI'] )
					),
					'plugin_header_invalid_plugin_uri',
					$plugin_main_file,
					0,
					0,
					'',
					6
				);
			} elseif ( preg_match( '#https?:\/\/(wordpress.org|example.com)#', $plugin_header['PluginURI'] ) ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file uses restricted domain.', 'plugin-check' ),
						esc_html( $labels['PluginURI'] )
					),
					'plugin_header_restricted_plugin_uri',
					$plugin_main_file,
					0,
					0,
					'',
					6
				);
			}
		}

		if ( ! empty( $plugin_header['AuthorURI'] ) ) {
			if ( filter_var( $plugin_header['AuthorURI'], FILTER_VALIDATE_URL ) !== $plugin_header['AuthorURI'] ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file is not valid.', 'plugin-check' ),
						esc_html( $labels['AuthorURI'] )
					),
					'plugin_header_invalid_author_uri',
					$plugin_main_file,
					0,
					0,
					'',
					6
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.2.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks adherence to the Headers requirements.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.2.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/', 'plugin-check' );
	}
}
