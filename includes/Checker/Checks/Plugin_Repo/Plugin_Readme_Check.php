<?php
/**
 * Class Plugin_Readme_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Find_Readme;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPressdotorg\Plugin_Directory\Readme\Parser;

/**
 * Check the plugins readme file and contents.
 *
 * @since 1.0.0
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Plugin_Readme_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Find_Readme;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Check the readme file.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {

		// Check if single file plugin, then bail early.
		if ( $result->plugin()->is_single_file_plugin() ) {
			return;
		}

		$plugin_relative_path = $result->plugin()->path();

		// Filter the readme files.
		$readme = $this->filter_files_for_readme( $files, $plugin_relative_path );

		// If the readme file does not exist, add a warning and skip other tests.
		if ( empty( $readme ) ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'The plugin readme.txt does not exist.', 'plugin-check' ),
				'no_plugin_readme',
				'readme.txt',
				0,
				0,
				'',
				9
			);

			return;
		}

		$readme_file = reset( $readme );

		$parser = new Parser( $readme_file );

		// Check the readme file for plugin name.
		$this->check_name( $result, $readme_file, $parser );

		// Check the readme file for missing headers.
		$this->check_headers( $result, $readme_file, $parser );

		// Check the readme file for default text.
		$this->check_default_text( $result, $readme_file, $parser );

		// Check the readme file for a valid license.
		$this->check_license( $result, $readme_file, $parser );

		// Check the readme file for a valid version.
		$this->check_stable_tag( $result, $readme_file, $parser );

		// Check the readme file for upgrade notice.
		$this->check_upgrade_notice( $result, $readme_file, $parser );

		// Check the readme file for warnings.
		$this->check_for_warnings( $result, $readme_file, $parser );
	}

	/**
	 * Checks the readme file for plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_name( Check_Result $result, string $readme_file, Parser $parser ) {
		if ( isset( $parser->warnings['invalid_plugin_name_header'] ) && false === $parser->name ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: Example plugin name header */
					__( 'Plugin name header in your readme is missing or invalid. Please update your readme with a valid plugin name header. Eg: "%s"', 'plugin-check' ),
					'=== Example Name ==='
				),
				'invalid_plugin_name',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incomplete-readme',
				9
			);
		} elseif ( empty( $parser->name ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: Example plugin name header */
					__( 'We cannot find a plugin name in your readme. Please update your readme with a valid plugin name header. Eg: "%s"', 'plugin-check' ),
					'=== Example Name ==='
				),
				'empty_plugin_name',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incomplete-readme',
				9
			);
		}
	}

	/**
	 * Checks the readme file for missing headers.
	 *
	 * @since 1.0.2
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_headers( Check_Result $result, string $readme_file, Parser $parser ) {
		$ignored_warnings = $this->get_ignored_warnings( $parser );

		$fields = array(
			'tested'       => array(
				'label'      => __( 'Tested up to', 'plugin-check' ),
				'ignore_key' => 'tested_header_ignored',
			),
			'contributors' => array(
				'label'      => __( 'Contributors', 'plugin-check' ),
				'ignore_key' => 'contributor_ignored',
			),
		);

		$parser_warnings = is_array( $parser->warnings ) ? $parser->warnings : array();

		foreach ( $fields as $field_key => $field ) {
			if ( ! in_array( $field['ignore_key'], $ignored_warnings, true ) && ! isset( $parser_warnings[ $field['ignore_key'] ] ) ) {

				if ( ! empty( $parser->{$field_key} ) && 'tested' === $field_key ) {
					$latest_wordpress_version = $this->get_wordpress_stable_version();
					if ( version_compare( $parser->{$field_key}, $latest_wordpress_version, '<' ) ) {
						$this->add_result_error_for_file(
							$result,
							sprintf(
								/* translators: 1: currently used version, 2: latest stable WordPress version, 3: 'Tested up to' */
								__( '<strong>Tested up to: %1$s < %2$s.</strong><br>The "%3$s" value in your plugin is not set to the current version of WordPress. This means your plugin will not show up in searches, as we require plugins to be compatible and documented as tested up to the most recent version of WordPress.', 'plugin-check' ),
								$parser->{$field_key},
								$latest_wordpress_version,
								'Tested up to'
							),
							'outdated_tested_upto_header',
							$readme_file,
							0,
							0,
							'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
							7
						);
					}
				} else {
					if ( empty( $parser->{$field_key} ) ) {
						$this->add_result_error_for_file(
							$result,
							sprintf(
								/* translators: %s: plugin header tag */
								__( '<strong>Your readme is either missing or incomplete.</strong><br>The "%s" field is missing. Your readme has to have headers as well as a proper description and documentation as to how it works and how one can use it.', 'plugin-check' ),
								$field['label']
							),
							'missing_readme_header',
							$readme_file,
							0,
							0,
							'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incomplete-readme'
						);
					}
				}
			}
		}
	}

	/**
	 * Checks the readme file for default text.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_default_text( Check_Result $result, string $readme_file, Parser $parser ) {
		$short_description = $parser->short_description;
		$tags              = $parser->tags;
		$donate_link       = $parser->donate_link;

		if (
			in_array( 'tag1', $tags, true )
			|| str_contains( $short_description, 'Here is a short description of the plugin.' )
			|| str_contains( $donate_link, '//example.com/' )
		) {
			$this->add_result_warning_for_file(
				$result,
				__( '<strong>The readme appears to contain default text.</strong><br>This means your readme has to have headers as well as a proper description and documentation as to how it works and how one can use it.', 'plugin-check' ),
				'default_readme_text',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incomplete-readme',
				7
			);
		}
	}

	/**
	 * Checks the readme file for a valid license.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_license( Check_Result $result, string $readme_file, Parser $parser ) {
		$license          = $parser->license;
		$matches_license  = array();
		$plugin_main_file = $result->plugin()->main_file();

		// Filter the readme files.
		if ( empty( $license ) ) {
			$this->add_result_error_for_file(
				$result,
				__( '<strong>Your plugin has no license declared.</strong><br>Please update your readme with a GPLv2 (or later) compatible license. It is necessary to declare the license of this plugin. You can do this by using the fields available both in the plugin readme and in the plugin headers.', 'plugin-check' ),
				'no_license',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);

			return;
		} else {
			$license = $this->normalize_licenses( $license );
		}

		// Test for a valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $license ) ) {
			$this->add_result_warning_for_file(
				$result,
				__( '<strong>Your plugin has an invalid license declared.</strong><br>Please update your readme with a valid SPDX license identifier.', 'plugin-check' ),
				'invalid_license',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);
		}

		$pattern     = preg_quote( 'License', '/' );
		$has_license = self::file_preg_match( "/(*ANYCRLF)^.*$pattern\s*:\s*(.*)$/im", array( $plugin_main_file ), $matches_license );
		if ( ! $has_license ) {
			$this->add_result_error_for_file(
				$result,
				__( '<strong>Your plugin has no license declared in Plugin Header.</strong><br>Please update your plugin header with a GPLv2 (or later) compatible license. It is necessary to declare the license of this plugin. You can do this by using the fields available both in the plugin readme and in the plugin headers.', 'plugin-check' ),
				'no_license',
				$plugin_main_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);
		} else {
			$plugin_license = $this->normalize_licenses( $matches_license[1] );
		}

		// Checks for a valid license in Plugin Header.
		if ( ! empty( $plugin_license ) && ! preg_match( '/GPL|GNU|MIT|FreeBSD|New BSD|BSD-3-Clause|BSD 3 Clause|OpenLDAP|Expat/im', $plugin_license ) ) {
			$this->add_result_error_for_file(
				$result,
				__( '<strong>Your plugin has an invalid license declared in Plugin Header.</strong><br>Please update your readme with a valid GPL license identifier. It is necessary to declare the license of this plugin. You can do this by using the fields available both in the plugin readme and in the plugin headers.', 'plugin-check' ),
				'invalid_license',
				$plugin_main_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);
		}

		// Check different license types.
		if ( ! empty( $plugin_license ) && ! empty( $license ) && $license !== $plugin_license ) {
			$this->add_result_warning_for_file(
				$result,
				__( '<strong>Your plugin has a different license declared in the readme file and plugin header.</strong><br>Please update your readme with a valid GPL license identifier.', 'plugin-check' ),
				'license_mismatch',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#declared-license-mismatched',
				9
			);
		}
	}

	/**
	 * Normalize licenses to compare them.
	 *
	 * @since 1.0.2
	 *
	 * @param string $license The license to normalize.
	 * @return string
	 */
	private function normalize_licenses( $license ) {
		$license = trim( $license );
		$license = str_replace( '  ', ' ', $license );

		// Remove some strings at the end.
		$strings_to_remove = array(
			'.',
			'http://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/gpl-3.0.html',
			' or later',
			'-or-later',
			'+',
		);
		foreach ( $strings_to_remove as $string_to_remove ) {
			$position = strrpos( $license, $string_to_remove );

			if ( false !== $position ) {
				// To remove from the end, the string to remove must be at the end.
				if ( $position + strlen( $string_to_remove ) === strlen( $license ) ) {
					$license = trim( substr( $license, 0, $position ) );
				}
			}
		}

		// Versions.
		$license = str_replace( '-', '', $license );
		$license = str_replace( 'GNU General Public License (GPL)', 'GPL', $license );
		$license = str_replace( 'GNU General Public License', 'GPL', $license );
		$license = preg_replace( '/GPL\s*[-|\.]*\s*[v]?([0-9])(\.[0])?/i', 'GPL$1', $license, 1 );
		$license = str_replace( '.', '', $license );

		return $license;
	}

	/**
	 * Checks the readme file stable tag.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_stable_tag( Check_Result $result, string $readme_file, Parser $parser ) {
		$stable_tag = $parser->stable_tag;

		if ( empty( $stable_tag ) ) {
			$this->add_result_error_for_file(
				$result,
				__( '<strong>Incorrect Stable Tag.</strong><br>Your Stable Tag is meant to be the stable version of your plugin, not of WordPress. For your plugin to be properly downloaded from WordPress.org, those values need to be the same. If they’re out of sync, your users won’t get the right version of your code.', 'plugin-check' ),
				'no_stable_tag',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incorrect-stable-tag',
				9
			);

			return;
		}

		if ( 'trunk' === $stable_tag ) {
			$this->add_result_error_for_file(
				$result,
				__( "<strong>Incorrect Stable Tag.</strong><br>It's recommended not to use 'Stable Tag: trunk'. Your Stable Tag is meant to be the stable version of your plugin, not of WordPress. For your plugin to be properly downloaded from WordPress.org, those values need to be the same. If they’re out of sync, your users won’t get the right version of your code.", 'plugin-check' ),
				'trunk_stable_tag',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incorrect-stable-tag',
				9
			);
		}

		// Check the readme file Stable tag against the plugin's main file version.
		$plugin_data = get_plugin_data( $result->plugin()->main_file() );

		if (
			! empty( $plugin_data['Version'] ) &&
			$stable_tag !== $plugin_data['Version']
		) {
			$this->add_result_error_for_file(
				$result,
				__( '<strong>The Stable Tag in your readme file does not match the version in your main plugin file.</strong><br>Your Stable Tag is meant to be the stable version of your plugin, not of WordPress. For your plugin to be properly downloaded from WordPress.org, those values need to be the same. If they’re out of sync, your users won’t get the right version of your code.', 'plugin-check' ),
				'stable_tag_mismatch',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incorrect-stable-tag',
				9
			);
		}
	}

	/**
	 * Checks the readme file upgrade notice.
	 *
	 * @since 1.0.2
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_upgrade_notice( Check_Result $result, string $readme_file, Parser $parser ) {
		$notices = $parser->upgrade_notice;

		$maximum_characters = 300;

		// Bail if no upgrade notices.
		if ( 0 === count( $notices ) ) {
			return;
		}

		foreach ( $notices as $version => $notice ) {
			if ( strlen( $notice ) > $maximum_characters ) {
				if ( empty( $version ) ) {
					/* translators: %d: maximum limit. */
					$message = sprintf( _n( 'The upgrade notice exceeds the limit of %d character.', 'The upgrade notice exceeds the limit of %d characters.', $maximum_characters, 'plugin-check' ), $maximum_characters );
				} else {
					/* translators: 1: version, 2: maximum limit. */
					$message = sprintf( _n( 'The upgrade notice for "%1$s" exceeds the limit of %2$d character.', 'The upgrade notice for "%1$s" exceeds the limit of %2$d characters.', $maximum_characters, 'plugin-check' ), $version, $maximum_characters );
				}

				$this->add_result_warning_for_file( $result, $message, 'upgrade_notice_limit', $readme_file );
			}
		}
	}

	/**
	 * Checks the readme file warnings.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	private function check_for_warnings( Check_Result $result, string $readme_file, Parser $parser ) {
		$warnings = $parser->warnings ? $parser->warnings : array();

		// This should be ERROR rather than WARNING. So ignoring here to handle separately.
		unset( $warnings['invalid_plugin_name_header'] );

		$warning_keys = array_keys( $warnings );

		$latest_wordpress_version = (float) $this->get_wordpress_stable_version();

		$warning_details = array(
			'contributor_ignored'          => array(
				'message' => sprintf(
					/* translators: %s: plugin header tag */
					__( 'One or more contributors listed were ignored. The "%s" field should only contain WordPress.org usernames. Remember that usernames are case-sensitive.', 'plugin-check' ),
					'Contributors'
				),
			),
			'requires_php_header_ignored'  => array(
				'message' => sprintf(
					/* translators: 1: plugin header tag; 2: Example version 5.2.4. 3: Example version 7.0. */
					__( 'The "%1$s" field was ignored. This field should only contain a PHP version such as "%2$s" or "%3$s".', 'plugin-check' ),
					'Requires PHP',
					'5.2.4',
					'7.0'
				),
			),
			'tested_header_ignored'        => array(
				'message'  => sprintf(
					/* translators: 1: plugin header tag; 2: Example version 5.0. 3: Example version 5.1. */
					__( 'The "%1$s" field was ignored. This field should only contain a valid WordPress version such as "%2$s" or "%3$s".', 'plugin-check' ),
					'Tested up to',
					number_format( $latest_wordpress_version, 1 ),
					number_format( $latest_wordpress_version + 0.1, 1 )
				),
				'severity' => 7,
			),
			'requires_header_ignored'      => array(
				'message' => sprintf(
					/* translators: 1: plugin header tag; 2: Example version 5.0. 3: Example version 4.9. */
					__( 'The "%1$s" field was ignored. This field should only contain a valid WordPress version such as "%2$s" or "%3$s".', 'plugin-check' ),
					'Requires at least',
					number_format( $latest_wordpress_version, 1 ),
					number_format( $latest_wordpress_version - 0.1, 1 )
				),
			),
			'too_many_tags'                => array(
				'message' => sprintf(
					/* translators: %d: maximum tags limit */
					__( 'One or more tags were ignored. Please limit your plugin to %d tags.', 'plugin-check' ),
					5
				),
			),
			'ignored_tags'                 => array(
				'message' => sprintf(
					/* translators: %s: list of tags not supported */
					__( 'One or more tags were ignored. The following tags are not permitted: %s', 'plugin-check' ),
					'"' . implode( '", "', $parser->ignore_tags ) . '"'
				),
			),
			'no_short_description_present' => array(
				'message' => sprintf(
					/* translators: %s: section title */
					__( 'The "%s" section is missing. An excerpt was generated from your main plugin description.', 'plugin-check' ),
					'Short Description'
				),
			),
			'trimmed_short_description'    => array(
				'message'  => sprintf(
					/* translators: 1: section title; 2: maximum limit */
					_n( 'The "%1$s" section is too long and was truncated. A maximum of %2$d character is supported.', 'The "%1$s" section is too long and was truncated. A maximum of %2$d characters is supported.', 150, 'plugin-check' ),
					'Short Description',
					150
				),
				'severity' => 6,
			),
		);

		if ( ! empty( $parser->sections ) ) {
			foreach ( array_keys( $parser->sections ) as $section ) {
				$max_length = $parser->maximum_field_lengths['section'];

				if ( isset( $parser->maximum_field_lengths[ 'section-' . $section ] ) ) {
					$max_length = $parser->maximum_field_lengths[ 'section-' . $section ];
				}

				$section_title = str_replace( '_', ' ', $section );

				$section_title = ( 'faq' === $section ) ? strtoupper( $section_title ) : ucwords( $section_title );

				$warning_details[ 'trimmed_section_' . $section ] = array(
					'message'  => sprintf(
						/* translators: 1: section title; 2: maximum limit */
						_n( 'The "%1$s" section is too long and was truncated. A maximum of %2$d character is supported.', 'The "%1$s" section is too long and was truncated. A maximum of %2$d characters is supported.', $max_length, 'plugin-check' ),
						$section_title,
						$max_length
					),
					'severity' => 6,
				);
			}
		}

		$ignored_warnings = $this->get_ignored_warnings( $parser );

		$warning_keys = array_diff( $warning_keys, $ignored_warnings );

		if ( ! empty( $warning_keys ) ) {
			foreach ( $warning_keys as $warning ) {
				$warning_message = isset( $warning_details[ $warning ]['message'] ) ? $warning_details[ $warning ]['message'] : sprintf(
					/* translators: %s: warning code */
					__( 'Readme parser warning detected: %s', 'plugin-check' ),
					esc_html( $warning )
				);

				$this->add_result_warning_for_file(
					$result,
					$warning_message,
					'readme_parser_warnings_' . $warning,
					$readme_file,
					0,
					0,
					'',
					isset( $warning_details[ $warning ]['severity'] ) ? $warning_details[ $warning ]['severity'] : 5
				);
			}
		}
	}

	/**
	 * Returns current major WordPress version.
	 *
	 * @since 1.0.0
	 *
	 * @return string Stable WordPress version.
	 */
	private function get_wordpress_stable_version() {
		$version = get_transient( 'wp_plugin_check_latest_wp_version' );

		if ( false === $version ) {
			$response = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $body['offers'] ) && ! empty( $body['offers'] ) ) {
					$latest_release = reset( $body['offers'] );

					$version = $latest_release['current'];

					set_transient( 'wp_plugin_check_latest_wp_version', $version, DAY_IN_SECONDS );
				}
			}
		}

		// If $version is still false at this point, use current installed WordPress version.
		if ( false === $version ) {
			$version = get_bloginfo( 'version' );

			// Strip off any -alpha, -RC, -beta suffixes.
			list( $version, ) = explode( '-', $version );
		}

		if ( preg_match( '#^\d.\d#', $version, $matches ) ) {
			$version = $matches[0];
		}

		return $version;
	}

	/**
	 * Returns ignored warnings.
	 *
	 * @since 1.0.2
	 *
	 * @param Parser $parser The Parser object.
	 * @return array Ignored warnings.
	 */
	private function get_ignored_warnings( Parser $parser ) {
		$ignored_warnings = array(
			'contributor_ignored',
		);

		/**
		 * Filter the list of ignored readme parser warnings.
		 *
		 * @since 1.0.2
		 *
		 * @param array  $ignored_warnings Array of ignored warning keys.
		 * @param Parser $parser           The Parser object.
		 */
		$ignored_warnings = (array) apply_filters( 'wp_plugin_check_ignored_readme_warnings', $ignored_warnings, $parser );

		return $ignored_warnings;
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return sprintf(
			/* translators: %s: readme.txt */
			__( 'Checks adherence to the %s requirements.', 'plugin-check' ),
			'<code>readme.txt</code>'
		);
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/', 'plugin-check' );
	}
}
