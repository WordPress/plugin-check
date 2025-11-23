<?php
/**
 * Class Plugin_Header_Fields_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\License_Utils;
use WordPress\Plugin_Check\Traits\Mode_Aware;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Traits\URL_Utils;
use WordPress\Plugin_Check\Traits\Version_Utils;

/**
 * Check for plugin header fields.
 *
 * @since 1.2.0
 */
class Plugin_Header_Fields_Check implements Static_Check {

	use Amend_Check_Result;
	use License_Utils;
	use Stable_Check;
	use URL_Utils;
	use Version_Utils;
	use Mode_Aware;

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
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of the check).
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function run( Check_Result $result ) {
		$plugin_main_file = $result->plugin()->main_file();

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
			'License'         => 'License',
			'LicenseURI'      => 'License URI',
		);

		$restricted_labels = array(
			'RestrictedLabel' => 'Restricted Label',
		); // Reserved for future use.

		$plugin_header = $this->get_plugin_data( $plugin_main_file, array_merge( $labels, $restricted_labels ) );

		if ( ! empty( $plugin_header['Name'] ) ) {
			if ( in_array( $plugin_header['Name'], array( 'Plugin Name', 'My Basics Plugin' ), true ) ) {
				$this->add_result_error_for_file(
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
					7
				);
			} else {
				// Check for unsupported plugin name only in 'new' mode.
				if ( $this->is_new_mode( $result ) ) {
					$valid_chars_count = preg_match_all( '/[a-z0-9]/i', $plugin_header['Name'] );

					if ( intval( $valid_chars_count ) < 5 ) {
						$this->add_result_error_for_file(
							$result,
							sprintf(
								/* translators: %s: plugin header field */
								__( 'The "%s" header in the plugin file is not valid. It needs to contain at least 5 latin letters (a-Z) and/or numbers. This is necessary because the initial plugin slug is generated from the name.', 'plugin-check' ),
								esc_html( $labels['Name'] )
							),
							'plugin_header_unsupported_plugin_name',
							$plugin_main_file,
							0,
							0,
							'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
							7
						);
					}
				}
			}
		}

		if ( ! empty( $plugin_header['PluginURI'] ) ) {
			if ( true !== $this->is_valid_url( $plugin_header['PluginURI'] ) ) {
				$this->add_result_error_for_file(
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
					'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
					7
				);
			} else {
				$matched_domain = $this->find_discouraged_domain( $plugin_header['PluginURI'] );
				if ( $matched_domain ) {
					$this->add_result_error_for_file(
						$result,
						sprintf(
							/* translators: 1: plugin header field, 2: domain */
							__( 'The "%1$s" header in the plugin file is not valid. Discouraged domain "%2$s" found. This is the homepage of the plugin, which should be a unique URL, preferably on your own website.', 'plugin-check' ),
							esc_html( $labels['PluginURI'] ),
							esc_html( $matched_domain )
						),
						'plugin_header_invalid_plugin_uri_domain',
						$plugin_main_file,
						0,
						0,
						'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
						7
					);
				}
			}
		}

		if ( empty( $plugin_header['Description'] ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: plugin header field */
					__( 'The "%s" header is missing in the plugin file.', 'plugin-check' ),
					esc_html( $labels['Description'] )
				),
				'plugin_header_missing_plugin_description',
				$plugin_main_file,
				0,
				0,
				__( 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/', 'plugin-check' ),
				7
			);
		} else {
			if (
				str_contains( $plugin_header['Description'], 'This is a short description of what the plugin does' )
				|| str_contains( $plugin_header['Description'], 'Here is a short description of the plugin' )
				|| str_contains( $plugin_header['Description'], 'Handle the basics with this plugin' )
				) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file should not contain default text.', 'plugin-check' ),
						esc_html( $labels['Description'] )
					),
					'plugin_header_invalid_plugin_description',
					$plugin_main_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
					7
				);
			}
		}

		if ( empty( $plugin_header['Version'] ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: plugin header field */
					__( 'The "%s" header is missing in the plugin file.', 'plugin-check' ),
					esc_html( $labels['Version'] )
				),
				'plugin_header_missing_plugin_version',
				$plugin_main_file,
				0,
				0,
				__( 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/', 'plugin-check' ),
				7
			);
		} else {
			if ( ! preg_match( '/^[a-z0-9.-]+$/i', $plugin_header['Version'] ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file should only contain numbers, letters, periods, and hyphens.', 'plugin-check' ),
						esc_html( $labels['Version'] )
					),
					'plugin_header_invalid_plugin_version',
					$plugin_main_file,
					0,
					0,
					__( 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/', 'plugin-check' ),
					7
				);
			}
		}

		if ( ! empty( $plugin_header['AuthorURI'] ) ) {
			if ( true !== $this->is_valid_url( $plugin_header['AuthorURI'] ) ) {
				$this->add_result_error_for_file(
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
					'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
					7
				);
			} else {
				$matched_domain = $this->find_discouraged_domain( $plugin_header['AuthorURI'] );
				if ( $matched_domain ) {
					$this->add_result_error_for_file(
						$result,
						sprintf(
							/* translators: 1: plugin header field, 2: domain */
							__( 'The "%1$s" header in the plugin file is not valid. Discouraged domain "%2$s" found. This is the author\'s website or profile on another website.', 'plugin-check' ),
							esc_html( $labels['AuthorURI'] ),
							esc_html( $matched_domain )
						),
						'plugin_header_invalid_author_uri_domain',
						$plugin_main_file,
						0,
						0,
						'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
						7
					);
				}
			}
		}

		if ( ! empty( $plugin_header['Network'] ) ) {
			if ( 'true' !== strtolower( $plugin_header['Network'] ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file is not valid. Can only be set to true, and should be left out when not needed.', 'plugin-check' ),
						esc_html( $labels['Network'] )
					),
					'plugin_header_invalid_network',
					$plugin_main_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
					7
				);
			}
		}

		if ( ! empty( $plugin_header['RequiresWP'] ) ) {
			$latest_wp_version = $this->get_wordpress_stable_version();

			// Only proceed with WordPress version validation if we got a valid version.
			if ( ! empty( $latest_wp_version ) ) {
				if ( ! preg_match( '!^\d+\.\d(\.\d+)?$!', $plugin_header['RequiresWP'] ) ) {
					$previous_wp_version = $this->get_wordpress_relative_major_version( $latest_wp_version, -1 );

					// Only add error if we got a valid previous version.
					if ( ! empty( $previous_wp_version ) ) {
						$this->add_result_error_for_file(
							$result,
							sprintf(
								/* translators: 1: plugin header field, 2: Example version 6.7, 3: Example version 6.6 */
								__( 'The "%1$s" header in the plugin file should only contain a WordPress version such as "%2$s" or "%3$s".', 'plugin-check' ),
								esc_html( $labels['RequiresWP'] ),
								esc_html( $latest_wp_version ),
								esc_html( $previous_wp_version )
							),
							'plugin_header_invalid_requires_wp',
							$plugin_main_file,
							0,
							0,
							'',
							7
						);
					}
				} else {
					$acceptable_min_wp_version = $this->get_wordpress_relative_major_version( $latest_wp_version, 1 );

					// Only add error if we got a valid acceptable minimum version.
					if ( ! empty( $acceptable_min_wp_version ) ) {
						if ( version_compare( $plugin_header['RequiresWP'], $acceptable_min_wp_version, '>' ) ) {
							$this->add_result_error_for_file(
								$result,
								sprintf(
									/* translators: 1: plugin header field, 2: currently used version */
									__( '<strong>%1$s: %2$s.</strong><br>The "%1$s" value in your plugin header is not valid. This version of WordPress does not exist (yet).', 'plugin-check' ),
									esc_html( $labels['RequiresWP'] ),
									esc_html( $plugin_header['RequiresWP'] )
								),
								'plugin_header_nonexistent_requires_wp',
								$plugin_main_file,
								0,
								0,
								'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
								7
							);
						}
					}
				}
			}
		}
		if ( ! empty( $plugin_header['RequiresPHP'] ) ) {
			if ( ! preg_match( '!^\d+(\.\d+){1,2}$!', $plugin_header['RequiresPHP'] ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: 1: plugin header field; 2: Example version 5.2.4. 3: Example version 7.0. */
						__( 'The "%1$s" header in the plugin file should only contain a PHP version such as "%2$s" or "%3$s".', 'plugin-check' ),
						esc_html( $labels['RequiresPHP'] ),
						'5.2.4',
						'7.0'
					),
					'plugin_header_invalid_requires_php',
					$plugin_main_file,
					0,
					0,
					'',
					7
				);
			}
		}

		if ( ! empty( $plugin_header['RequiresPlugins'] ) ) {
			if ( ! preg_match( '/^[a-z0-9-]+(?:,\s*[a-z0-9-]+)*$/', $plugin_header['RequiresPlugins'] ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: plugin header field */
						__( 'The "%s" header in the plugin file must contain a comma-separated list of WordPress.org-formatted slugs.', 'plugin-check' ),
						esc_html( $labels['RequiresPlugins'] )
					),
					'plugin_header_invalid_requires_plugins',
					$plugin_main_file,
					0,
					0,
					'',
					7
				);
			}
		}

		if ( empty( $plugin_header['License'] ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: plugin header field */
					__( '<strong>Missing "%s" in Plugin Header.</strong><br>Please update your Plugin Header with a valid GPLv2 (or later) compatible license.', 'plugin-check' ),
					esc_html( $labels['License'] )
				),
				'plugin_header_no_license',
				$plugin_main_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);
		} else {
			$plugin_license = $this->get_normalized_license( $plugin_header['License'] );
			if ( ! $this->is_license_gpl_compatible( $plugin_license ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: 1: plugin header field, 2: license */
						__( '<strong>Invalid %1$s: %2$s.</strong><br>Please update your Plugin Header with a valid GPLv2 (or later) compatible license.', 'plugin-check' ),
						esc_html( $labels['License'] ),
						esc_html( $plugin_header['License'] )
					),
					'plugin_header_invalid_license',
					$plugin_main_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
					9
				);
			}
		}

		$found_headers = array();

		foreach ( $restricted_labels as $restricted_key => $restricted_label ) {
			if ( array_key_exists( $restricted_key, $plugin_header ) && ! empty( $plugin_header[ $restricted_key ] ) ) {
				$found_headers[ $restricted_key ] = $restricted_label;
			}
		}

		if ( ! empty( $found_headers ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: header fields */
					__( 'Restricted plugin header field(s) found: %s', 'plugin-check' ),
					"'" . implode( "', '", array_values( $found_headers ) ) . "'"
				),
				'plugin_header_restricted_fields',
				$plugin_main_file,
				0,
				0,
				'',
				7
			);
		}

		if ( ! $result->plugin()->is_single_file_plugin() ) {
			if ( ! empty( $plugin_header['TextDomain'] ) ) {
				if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $plugin_header['TextDomain'] ) ) {
					$this->add_result_error_for_file(
						$result,
						sprintf(
							/* translators: 1: plugin header field, 2: text domain */
							__( 'The "%1$s" header in the plugin file should only contain lowercase letters, numbers, and hyphens. Found "%2$s".', 'plugin-check' ),
							esc_html( $labels['TextDomain'] ),
							esc_html( $plugin_header['TextDomain'] )
						),
						'textdomain_invalid_format',
						$plugin_main_file,
						0,
						0,
						'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains',
						7
					);
				} else {
					$plugin_slug = $result->plugin()->slug();

					if ( $plugin_slug !== $plugin_header['TextDomain'] ) {
						$this->add_result_warning_for_file(
							$result,
							sprintf(
								/* translators: 1: plugin header field, 2: plugin header text domain, 3: plugin slug */
								__( 'The "%1$s" header in the plugin file does not match the slug. Found "%2$s", expected "%3$s".', 'plugin-check' ),
								esc_html( $labels['TextDomain'] ),
								esc_html( $plugin_header['TextDomain'] ),
								esc_html( $plugin_slug )
							),
							'textdomain_mismatch',
							$plugin_main_file,
							0,
							0,
							'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/',
							6
						);
					}
				}
			}

			if ( ! empty( $plugin_header['DomainPath'] ) ) {
				if ( ! str_starts_with( $plugin_header['DomainPath'], '/' ) ) {
					$this->add_result_warning_for_file(
						$result,
						sprintf(
							/* translators: %s: plugin header field */
							__( 'The "%s" header in the plugin file must start with forward slash.', 'plugin-check' ),
							esc_html( $labels['DomainPath'] )
						),
						'plugin_header_invalid_domain_path',
						$plugin_main_file,
						0,
						0,
						'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#domain-path',
						6
					);
				}

				$domain_path = trim( $plugin_header['DomainPath'], '/' );

				$target_path = wp_normalize_path( $result->plugin()->path() . $domain_path );

				if ( ! is_dir( $target_path ) ) {
					$this->add_result_warning_for_file(
						$result,
						sprintf(
							/* translators: 1: plugin header field, 2: domain path */
							__( 'The "%1$s" header in the plugin file must point to an existing folder. Found: "%2$s"', 'plugin-check' ),
							esc_html( $labels['DomainPath'] ),
							$domain_path
						),
						'plugin_header_nonexistent_domain_path',
						$plugin_main_file,
						0,
						0,
						'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#domain-path',
						6
					);
				}
			}
		}
	}

	/**
	 * Parses the plugin contents to retrieve plugin's metadata.
	 *
	 * @since 1.2.0
	 *
	 * @param string $plugin_file     Absolute path to the main plugin file.
	 * @param array  $default_headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
	 * @return string[] Array of file header values keyed by header name.
	 */
	private function get_plugin_data( $plugin_file, $default_headers ) {
		return get_file_data( $plugin_file, $default_headers, 'plugin' );
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
