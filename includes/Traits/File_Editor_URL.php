<?php
/**
 * Trait WordPress\Plugin_Check\Traits\File_Editor_URL
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Traits;

use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Trait for file editor URL.
 *
 * @since 1.0.0
 */
trait File_Editor_URL {

	/**
	 * Gets the URL for opening the plugin file in an external editor.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param string       $filename Error file name.
	 * @param int          $line     Optional. Line number of error. Default 0 (no specific line).
	 * @return string|null File editor URL or null if not available.
	 */
	protected function get_file_editor_url( Check_Result $result, $filename, $line = 0 ) {

		$edit_url = null;
		$line     = (int) $line;

		$plugin_path = $result->plugin()->path( '/' );
		$plugin_slug = $result->plugin()->slug();
		$filename    = str_replace( $plugin_path, '', $filename );

		$file_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
		if ( $plugin_slug !== $filename ) {
			$file_path .= '/' . $filename;
		}

		$source = array(
			'file'     => $file_path,
			'line'     => $line,
			'plugin'   => $plugin_slug,
			'filename' => $filename,
		);

		/**
		 * Filters the URL for linking to an external editor to open a file for editing.
		 *
		 * Users of IDEs that support opening files via web protocols can use this filter to
		 * override the edit link so it opens in their editor rather than the plugin editor.
		 *
		 * The initial filtered value is null, requiring extension plugins to supply the URL
		 * themselves. If no URL is provided, links to the plugin editor are used if available.
		 * Returning a string that contains `{{file}}` or `{{line}}` placeholders causes them
		 * to be substituted with the raw filesystem path and the integer line number
		 * respectively. Returning a string without placeholders uses it verbatim.
		 *
		 * For example, to cause file edit links to open in an IDE:
		 *
		 * # PhpStorm
		 * add_filter( 'wp_plugin_check_validation_error_source_url', function ( $url, $source ) {
		 *     return 'phpstorm://open?file=' . rawurlencode( $source['file'] ) . '&line=' . (int) $source['line'];
		 * }, 10, 2 );
		 *
		 * # VS Code (using placeholders)
		 * add_filter( 'wp_plugin_check_validation_error_source_url', function ( $url ) {
		 *     return 'vscode://file/{{file}}:{{line}}';
		 * } );
		 *
		 * @since 2.1.0
		 *
		 * @param string|null $url    Editor URL. Default null.
		 * @param array       $source Source information: file, line, plugin, filename.
		 */
		$url = apply_filters( 'wp_plugin_check_validation_error_source_url', null, $source );

		if ( is_string( $url ) && '' !== $url && file_exists( $file_path ) ) {
			$edit_url = str_replace(
				array(
					'{{file}}',
					'{{line}}',
				),
				array(
					$file_path,
					$line,
				),
				$url
			);
		}

		// Backward compatibility for the two-filter chain that this single filter replaces.
		if ( ! $edit_url && has_filter( 'wp_plugin_check_validation_error_source_file_editor_url_template' ) ) {
			/**
			 * Filters the template for the URL for linking to an external editor to open a file for editing.
			 *
			 * Users of IDEs that support opening files via web protocols can use this filter to override
			 * the edit link to result in their editor opening rather than the plugin editor.
			 *
			 * @deprecated 2.1.0 Use the `wp_plugin_check_validation_error_source_url` filter instead.
			 *
			 * @since 1.0.0
			 *
			 * @param string|null $editor_url_template Editor URL template. Default null.
			 */
			$editor_url_template = apply_filters_deprecated(
				'wp_plugin_check_validation_error_source_file_editor_url_template',
				array( null ),
				'2.1.0',
				'wp_plugin_check_validation_error_source_url'
			);

			// Supply the file path to the editor template.
			if ( is_string( $editor_url_template ) && str_contains( $editor_url_template, '{{file}}' ) && file_exists( $file_path ) ) {
				/**
				 * Filters the file path to be opened in an external editor for a given PHPCS error source.
				 *
				 * This is useful to map the file path from inside of a Docker container or VM to the host machine.
				 *
				 * @deprecated 2.1.0 Use the `wp_plugin_check_validation_error_source_url` filter instead.
				 *
				 * @since 1.0.0
				 *
				 * @param string|null $file_path File path to be opened in the external editor.
				 * @param array       $source    Source information.
				 */
				$file_path = apply_filters_deprecated(
					'wp_plugin_check_validation_error_source_file_path',
					array( $file_path, array( $plugin_slug, $filename, $line ) ),
					'2.1.0',
					'wp_plugin_check_validation_error_source_url'
				);
				if ( $file_path ) {
					$edit_url = str_replace(
						array(
							'{{file}}',
							'{{line}}',
						),
						array(
							rawurlencode( $file_path ),
							$line,
						),
						$editor_url_template
					);
				}
			}
		}

		// Fall back to using the plugin editor if no external editor is offered.
		if ( ! $edit_url && current_user_can( 'edit_plugins' ) ) {
			$file = '';

			if ( $result->plugin()->is_single_file_plugin() ) {
				$file = $filename;
			} elseif ( $result->plugin()->is_file_editable( $filename ) ) {
				$file = $plugin_slug . '/' . $filename;
			}

			if ( ! empty( $file ) ) {
				$query_args = array(
					'plugin' => rawurlencode( $result->plugin()->basename() ),
					'file'   => rawurlencode( $file ),
				);
				if ( $line ) {
					$query_args['line'] = $line;
				}
				return add_query_arg(
					$query_args,
					admin_url( 'plugin-editor.php' )
				);
			}
		}
		return $edit_url;
	}
}
