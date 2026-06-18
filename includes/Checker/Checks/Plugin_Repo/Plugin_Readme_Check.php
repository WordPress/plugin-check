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
use WordPress\Plugin_Check\Lib\Readme\Parser as PCPParser;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Language_Utils;
use WordPress\Plugin_Check\Traits\License_Utils;
use WordPress\Plugin_Check\Traits\Mode_Aware;
use WordPress\Plugin_Check\Traits\Readme_Utils;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Traits\URL_Utils;
use WordPress\Plugin_Check\Traits\Version_Utils;
use WordPressdotorg\Plugin_Directory\Readme\Parser as DotorgParser;

/**
 * Check the plugins readme file and contents.
 *
 * @since 1.0.0
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class Plugin_Readme_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Mode_Aware;
	use Readme_Utils;
	use Stable_Check;
	use License_Utils;
	use URL_Utils;
	use Version_Utils;
	use Language_Utils;

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

		// If the readme file does not exist, add an error and skip other tests.
		if ( empty( $readme ) ) {
			$this->add_result_error_for_file(
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

		$parser = class_exists( DotorgParser::class ) ? new DotorgParser( $readme_file ) : new PCPParser( $readme_file );

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

		// Check the readme file for donate link.
		$this->check_for_donate_link( $result, $readme_file, $parser );

		// Check the readme file for contributors.
		$this->check_for_contributors( $result, $readme_file );

		// Check the readme file for requires headers.
		$this->check_requires_headers( $result, $readme_file, $parser );

		// Check the readme for language.
		$this->check_language( $result, $readme_file, $parser );

		// Check for mismatched "Tested up to" header between plugin header and readme.
		$this->check_tested_up_to_mismatch( $result, $parser, $result->plugin()->main_file() );
	}

	/**
	 * Checks the readme file for plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_name( Check_Result $result, string $readme_file, $parser ) {
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
		} else {
			$plugin_data = get_plugin_data( $result->plugin()->main_file(), false, false );

			$plugin_readme_name = html_entity_decode( $parser->name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
			$plugin_header_name = html_entity_decode( $plugin_data['Name'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

			if ( $plugin_readme_name !== $plugin_header_name ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: 1: Plugin name, 2: Name in plugin header */
						__( 'Plugin name "%1$s" is different from the name declared in plugin header "%2$s".', 'plugin-check' ),
						$plugin_readme_name,
						$plugin_header_name
					),
					'mismatched_plugin_name',
					$readme_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incomplete-readme',
					7
				);

			}
		}
	}

	/**
	 * Checks the readme file for missing headers.
	 *
	 * @since 1.0.2
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	private function check_headers( Check_Result $result, string $readme_file, $parser ) {
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
					list( $tested_upto, ) = explode( '-', $parser->{$field_key} );

					$latest_wordpress_version = $this->get_wordpress_stable_version();

					// Only proceed with WordPress version validation if we got a valid version.
					if ( ! empty( $latest_wordpress_version ) ) {
						$tested_upto_major = $tested_upto;
						if ( preg_match( '#^\d.\d#', $tested_upto, $matches ) ) {
							$tested_upto_major = $matches[0];
						}

						if ( $tested_upto_major === $latest_wordpress_version && preg_match( '/^\d+\.\d+\.\d+/', $tested_upto ) ) {
							$this->add_result_error_for_file(
								$result,
								sprintf(
									/* translators: %s: currently used version */
									__( '<strong>Tested up to: %1$s</strong><br>The version number should only include major versions %2$s.', 'plugin-check' ),
									$tested_upto,
									$tested_upto_major
								),
								'invalid_tested_upto_minor',
								$readme_file,
								0,
								0,
								'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
								7
							);
						}

						if ( version_compare( $tested_upto_major, $latest_wordpress_version, '<' ) ) {
							$outdated_tested_message = sprintf(
								/* translators: 1: currently used version, 2: latest stable WordPress version, 3: 'Tested up to' */
								__( '<strong>Tested up to: %1$s &lt; %2$s.</strong><br>The "%3$s" value in your plugin is not set to the current version of WordPress. This means your plugin will not show up in searches, as we require plugins to be compatible and documented as tested up to the most recent version of WordPress.', 'plugin-check' ),
								$tested_upto_major,
								$latest_wordpress_version,
								'Tested up to'
							);
							$outdated_tested_docs = 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information';

							if ( $this->is_update_mode( $result ) ) {
								$this->add_result_warning_for_file(
									$result,
									$outdated_tested_message,
									'outdated_tested_upto_header',
									$readme_file,
									0,
									0,
									$outdated_tested_docs,
									5
								);
							} else {
								$this->add_result_error_for_file(
									$result,
									$outdated_tested_message,
									'outdated_tested_upto_header',
									$readme_file,
									0,
									0,
									$outdated_tested_docs,
									7
								);
							}
						} elseif ( version_compare( $tested_upto_major, number_format( (float) $latest_wordpress_version + 0.1, 1 ), '>' ) ) {
							$this->add_result_error_for_file(
								$result,
								sprintf(
									/* translators: 1: currently used version, 2: 'Tested up to' */
									__( '<strong>Tested up to: %1$s.</strong><br>The "%2$s" value in your plugin is not valid. This version of WordPress does not exist (yet).', 'plugin-check' ),
									$tested_upto_major,
									'Tested up to'
								),
								'nonexistent_tested_upto_header',
								$readme_file,
								0,
								0,
								'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
								7
							);
						}
					}
				} else {
					if ( empty( $parser->{$field_key} ) ) {
						$this->add_result_error_for_file(
							$result,
							sprintf(
								/* translators: %s: readme header field */
								__( 'The "%s" header is missing in the readme file.', 'plugin-check' ),
								$field['label']
							),
							'missing_readme_header_' . $field_key,
							$readme_file,
							0,
							0,
							'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
							7
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
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_default_text( Check_Result $result, string $readme_file, $parser ) {
		$short_description = $parser->short_description;
		$tags              = $parser->tags;

		if (
			in_array( 'tag1', $tags, true )
			|| str_contains( $short_description, 'Here is a short description of the plugin.' )
		) {
			$this->add_result_error_for_file(
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
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_license( Check_Result $result, string $readme_file, $parser ) {
		$license          = $parser->license;
		$matches_license  = array();
		$plugin_main_file = $result->plugin()->main_file();

		// Filter the readme files.
		if ( empty( $license ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: readme header field */
					__( '<strong>Missing "%s".</strong><br>Please update your readme with a valid GPLv2 (or later) compatible license.', 'plugin-check' ),
					'License'
				),
				'no_license',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared',
				9
			);

			return;
		} else {
			$license = $this->get_normalized_license( $license );
		}

		// Test for a valid SPDX license identifier.
		if ( ! $this->is_license_valid_identifier( $license ) ) {
			$this->add_result_error_for_file(
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

		$plugin_license = '';

		$pattern     = preg_quote( 'License', '/' );
		$has_license = self::file_preg_match( "/(*ANYCRLF)^.*$pattern\s*:\s*(.*)$/im", array( $plugin_main_file ), $matches_license );

		if ( $has_license ) {
			$plugin_license = $this->get_normalized_license( $matches_license[1] );
		}

		// Check different license types.
		if ( ! empty( $plugin_license ) && ! empty( $license ) && $license !== $plugin_license ) {
			$this->add_result_error_for_file(
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
	 * Checks the readme file stable tag.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_stable_tag( Check_Result $result, string $readme_file, $parser ) {
		$stable_tag = $parser->stable_tag;

		if ( empty( $stable_tag ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: 1: readme header tag, 2: plugin header tag */
					__( '<strong>Invalid or missing %1$s.</strong><br>Your %1$s is meant to be the stable version of your plugin and it needs to be exactly the same with the %2$s in your main plugin file\'s header. Any mismatch can prevent users from downloading the correct plugin files from WordPress.org.', 'plugin-check' ),
					'Stable Tag',
					'Version'
				),
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
				sprintf(
					/* translators: 1: readme header tag, 2: example tag, 3: plugin header tag */
					__( '<strong>Incorrect %1$s.</strong><br>It\'s recommended not to use "%2$s". Your %1$s is meant to be the stable version of your plugin and it needs to be exactly the same with the %3$s in your main plugin file\'s header. Any mismatch can prevent users from downloading the correct plugin files from WordPress.org.', 'plugin-check' ),
					'Stable Tag',
					'Stable Tag: trunk',
					'Version'
				),
				'trunk_stable_tag',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incorrect-stable-tag',
				9
			);

			return;
		}

		// Check the readme file Stable tag against the plugin's main file version.
		$plugin_data = get_plugin_data( $result->plugin()->main_file() );

		if (
			! empty( $plugin_data['Version'] ) &&
			$stable_tag !== $plugin_data['Version']
		) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: 1: readme header tag, 2: versions comparison, 3: plugin header tag */
					__( '<strong>Mismatched %1$s: %2$s.</strong><br>Your %1$s is meant to be the stable version of your plugin and it needs to be exactly the same with the %3$s in your main plugin file\'s header. Any mismatch can prevent users from downloading the correct plugin files from WordPress.org.', 'plugin-check' ),
					'Stable Tag',
					esc_html( $stable_tag ) . ' != ' . esc_html( $plugin_data['Version'] ),
					'Version'
				),
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
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_upgrade_notice( Check_Result $result, string $readme_file, $parser ) {
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
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private function check_for_warnings( Check_Result $result, string $readme_file, $parser ) {
		$warnings = $parser->warnings ? $parser->warnings : array();

		// This should be ERROR rather than WARNING. So ignoring here to handle separately.
		unset( $warnings['invalid_plugin_name_header'] );

		// We handle license check in our own way.
		unset( $warnings['license_missing'] );
		unset( $warnings['invalid_license'] );
		unset( $warnings['unknown_license'] );

		$warning_keys = array_keys( $warnings );

		$latest_wordpress_version = $this->get_wordpress_stable_version();

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
					! empty( $latest_wordpress_version ) ? number_format( (float) $latest_wordpress_version, 1 ) : '5.0',
					! empty( $latest_wordpress_version ) ? number_format( (float) $latest_wordpress_version + 0.1, 1 ) : '5.1'
				),
				'severity' => 7,
			),
			'requires_header_ignored'      => array(
				'message' => sprintf(
					/* translators: 1: plugin header tag; 2: Example version 5.0. 3: Example version 4.9. */
					__( 'The "%1$s" field was ignored. This field should only contain a valid WordPress version such as "%2$s" or "%3$s".', 'plugin-check' ),
					'Requires at least',
					! empty( $latest_wordpress_version ) ? number_format( (float) $latest_wordpress_version, 1 ) : '5.0',
					! empty( $latest_wordpress_version ) ? number_format( (float) $latest_wordpress_version - 0.1, 1 ) : '4.9'
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
	 * Checks the readme file for donate link.
	 *
	 * @since 1.3.0
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_for_donate_link( Check_Result $result, string $readme_file, $parser ) {
		$donate_link = $parser->donate_link;

		// Bail if empty donate link.
		if ( empty( $donate_link ) ) {
			return;
		}

		if ( ! $this->is_valid_url( $donate_link ) ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: readme header field */
					__( 'The "%s" header in the readme file must be a valid URL.', 'plugin-check' ),
					'Donate link'
				),
				'readme_invalid_donate_link',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
				7
			);

			return;
		}

		// Check for discouraged domain.
		$matched_domain = $this->find_discouraged_domain( $donate_link );

		if ( $matched_domain ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: 1: readme header field, 2: domain */
					__( 'The "%1$s" header in the readme file is not valid. Discouraged domain "%2$s" found. This is the URL for users to support plugin author financially.', 'plugin-check' ),
					'Donate link',
					esc_html( $matched_domain )
				),
				'readme_invalid_donate_link_domain',
				$readme_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
				7
			);
		}
	}

	/**
	 * Checks the readme file for contributors.
	 *
	 * @since 1.2.0
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	private function check_for_contributors( Check_Result $result, string $readme_file ) {
		$regex = '/Contributors\s?:(?:\*\*|\s)?(.*?)\R/';

		$matches = array();

		self::file_preg_match( $regex, array( $readme_file ), $matches );

		// Bail if no "Contributors" found.
		if ( empty( $matches ) ) {
			return;
		}

		$usernames = explode( ',', trim( $matches[1], ' ,' ) );
		$usernames = array_filter( array_unique( array_map( 'trim', $usernames ) ) );

		$valid = true;

		foreach ( $usernames as $username ) {
			if ( 1 !== preg_match( '/^[a-z0-9_.\-@ ]+$/i', $username ) ) {
				$valid = false;
				break;
			}
		}

		if ( ! $valid ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: plugin header field */
					__( 'The "%s" header in the readme file must be a comma-separated list of WordPress.org-formatted usernames.', 'plugin-check' ),
					'Contributors'
				),
				'readme_invalid_contributors',
				$readme_file,
				0,
				0,
				'',
				6
			);

			return;
		}

		$restricted_contributors = $this->get_restricted_contributors();

		$disallowed_contributors = array_keys(
			array_filter(
				$restricted_contributors,
				function ( $value ) {
					return 'error' === strtolower( $value );
				}
			)
		);

		if ( ! empty( $disallowed_contributors ) ) {
			$disallowed_usernames = array_intersect( $usernames, $disallowed_contributors );

			if ( ! empty( $disallowed_usernames ) ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: 1: plugin header field, 2: usernames */
						__( 'The "%1$s" header in the readme file contains restricted username(s). Found: %2$s', 'plugin-check' ),
						'Contributors',
						'"' . implode( '", "', $disallowed_usernames ) . '"'
					),
					'readme_restricted_contributors',
					$readme_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
					7
				);
			}
		}

		$reserved_contributors = array_keys(
			array_filter(
				$restricted_contributors,
				function ( $value ) {
					return 'warning' === strtolower( $value );
				}
			)
		);

		if ( ! empty( $reserved_contributors ) ) {
			$reserved_usernames = array_intersect( $usernames, $reserved_contributors );

			if ( ! empty( $reserved_usernames ) ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: 1: plugin header field, 2: usernames */
						__( 'The "%1$s" header in the readme file contains reserved username(s). Found: %2$s', 'plugin-check' ),
						'Contributors',
						'"' . implode( '", "', $reserved_usernames ) . '"'
					),
					'readme_reserved_contributors',
					$readme_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
					6
				);
			}
		}
	}

	/**
	 * Checks the readme file for requires headers.
	 *
	 * @since 1.5.0
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_requires_headers( Check_Result $result, string $readme_file, $parser ) {
		$ignored_warnings = $this->get_ignored_warnings( $parser );

		$found_warnings = $parser->warnings ? $parser->warnings : array();

		$current_warnings = array_diff( array_keys( $found_warnings ), $ignored_warnings );

		$requires = array(
			'requires_header_ignored'     => array(
				'label'        => 'Requires at least',
				'key'          => 'requires',
				'header_field' => 'RequiresWP',
			),
			'requires_php_header_ignored' => array(
				'label'        => 'Requires PHP',
				'key'          => 'requires_php',
				'header_field' => 'RequiresPHP',
			),
		);

		// Find potential requires keys to check.
		$potential_requires = array_diff( array_keys( $requires ), $current_warnings );

		// Bail if not found.
		if ( empty( $potential_requires ) ) {
			return;
		}

		$plugin_data = get_plugin_data( $result->plugin()->main_file(), false, false );

		foreach ( $potential_requires as $require ) {
			$readme_value = $parser->{$requires[ $require ]['key']};
			$plugin_value = $plugin_data[ $requires[ $require ]['header_field'] ];

			if ( ! empty( $readme_value ) && ! empty( $plugin_value ) && $readme_value !== $plugin_value ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: 1: readme header tag, 2: versions comparison */
						__( '<strong>Mismatched %1$s: %2$s.</strong><br>"%1$s" needs to be exactly the same with that in your main plugin file\'s header.', 'plugin-check' ),
						esc_html( $requires[ $require ]['label'] ),
						esc_html( $readme_value ) . ' != ' . esc_html( $plugin_value )
					),
					'readme_mismatched_header_' . $requires[ $require ]['key'],
					$readme_file,
					0,
					0,
					'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information'
				);
			}
		}
	}

	/**
	 * Checks the readme file for official language.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result           $result      The Check Result to amend.
	 * @param string                 $readme_file Readme file.
	 * @param DotorgParser|PCPParser $parser      The Parser object.
	 */
	private function check_language( Check_Result $result, string $readme_file, $parser ) {
		$check_language = $this->is_on_official_language( $parser->short_description );

		if ( ! $check_language ) {
			$this->add_result_error_for_file(
				$result,
				__( 'The readme short description contains unofficial language. It must be written in standard English.', 'plugin-check' ),
				'readme_short_description_non_official_language',
				$readme_file,
				0,
				0,
				'https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/',
				6
			);
		}

		if ( ! empty( $parser->sections['description'] ) ) {
			$check_language = $this->is_on_official_language( $parser->sections['description'] );

			if ( ! $check_language ) {
				$this->add_result_error_for_file(
					$result,
					__( 'The readme description contains unofficial language. It must be written in standard English.', 'plugin-check' ),
					'readme_description_non_official_language',
					$readme_file,
					0,
					0,
					'https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/',
					6
				);
			}
		}
	}


	/**
	 * Checks for mismatched "Tested up to" header between plugin header and readme.
	 *
	 * @since 1.8.0
	 *
	 * @param Check_Result           $result           The Check Result to amend.
	 * @param DotorgParser|PCPParser $parser           The Parser object.
	 * @param string                 $plugin_main_file The main plugin file path.
	 */
	private function check_tested_up_to_mismatch( Check_Result $result, $parser, string $plugin_main_file ) {

		// Check if single file plugin, then bail early.
		if ( $result->plugin()->is_single_file_plugin() ) {
			return;
		}

		// Get the "Tested up to" value from the readme.
		$readme_tested = isset( $parser->tested ) ? $parser->tested : '';

		if ( empty( $readme_tested ) ) {
			return;
		}

		// Get the "Tested up to" value from the plugin header.
		$tested_header = get_file_data(
			$plugin_main_file,
			array( 'TestedWP' => 'Tested up to' ),
			'plugin'
		);

		$plugin_tested = isset( $tested_header['TestedWP'] ) ? $tested_header['TestedWP'] : '';
		if ( empty( $plugin_tested ) ) {
			return;
		}

		// Normalize versions by removing any suffixes (like -RC1, -beta1).
		$readme_tested_normalized = strtok( $readme_tested, '-' );
		$plugin_tested_normalized = strtok( $plugin_tested, '-' );

		// Compare the two values.
		if ( $readme_tested_normalized !== $plugin_tested_normalized ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: 1: Tested up to value from readme, 2: Tested up to value from plugin header */
					__( '<strong>Mismatched "Tested up to": %1$s != %2$s.</strong><br>The "Tested up to" value in the readme file must match the "Tested up to" value in the plugin header. If the plugin header has a "Tested up to" value, it will override the readme value, which can cause confusion.', 'plugin-check' ),
					esc_html( $readme_tested_normalized ),
					esc_html( $plugin_tested_normalized )
				),
				'mismatched_tested_up_to_header',
				$plugin_main_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information',
				7
			);
		}
	}

	/**
	 * Returns ignored warnings.
	 *
	 * @since 1.0.2
	 *
	 * @param DotorgParser|PCPParser $parser The Parser object.
	 * @return array Ignored warnings.
	 */
	private function get_ignored_warnings( $parser ) {
		$ignored_warnings = array(
			'contributor_ignored',
		);

		/**
		 * Filter the list of ignored readme parser warnings.
		 *
		 * @since 1.0.2
		 *
		 * @param array                  $ignored_warnings Array of ignored warning keys.
		 * @param DotorgParser|PCPParser $parser           The Parser object.
		 */
		$ignored_warnings = (array) apply_filters( 'wp_plugin_check_ignored_readme_warnings', $ignored_warnings, $parser );

		return $ignored_warnings;
	}

	/**
	 * Returns restricted contributors.
	 *
	 * @since 1.4.0
	 *
	 * @return array Restricted contributors.
	 */
	private function get_restricted_contributors() {
		$restricted_contributors = array(
			'username'                => 'error',
			'your-name'               => 'error',
			'your-username'           => 'error',
			'your-wordpress-username' => 'error',
			'your_wordpress_username' => 'error',
			'yourusername'            => 'error',
			'yourwordpressusername'   => 'error',
			'wordpressdotorg'         => 'warning',
		);

		/**
		 * Filter the list of restricted contributors.
		 *
		 * @since 1.4.0
		 *
		 * @param array $restricted_contributors Array of restricted contributors with error type.
		 */
		$restricted_contributors = (array) apply_filters( 'wp_plugin_check_restricted_contributors', $restricted_contributors );

		return $restricted_contributors;
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
