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

		$plugin_path = $result->plugin()->path( '/' );
		$plugin_slug = $result->plugin()->slug();
		$filename    = str_replace( $plugin_path, '', $filename );
		/**
		 * Filters the template for the URL for linking to an external editor to open a file for editing.
		 *
		 * Users of IDEs that support opening files in via web protocols can use this filter to override
		 * the edit link to result in their editor opening rather than the plugin editor.
		 *
		 * The initial filtered value is null, requiring extension plugins to supply the URL template
		 * string themselves. If no template string is provided, links to the plugin editors will
		 * be provided if available. For example, for an extension plugin to cause file edit links to
		 * open in an IDE, the following filters can be used:
		 *
		 * # PhpStorm
		 * add_filter( 'wp_plugin_check_validation_error_source_file_editor_url_template', function () {
		 *     return 'phpstorm://open?file={{file}}&line={{line}}';
		 * } );
		 *
		 * # VS Code
		 * add_filter( 'wp_plugin_check_validation_error_source_file_editor_url_template', function () {
		 *     return 'vscode://file/{{file}}:{{line}}';
		 * } );
		 *
		 * For a template to be considered, the string '{{file}}' must be present in the filtered value.
		 *
		 * @since 1.0.0
		 *
		 * @param string|null $editor_url_template Editor URL template. default null.
		 */
		$editor_url_template = apply_filters( 'wp_plugin_check_validation_error_source_file_editor_url_template', null );

		// Supply the file path to the editor template.
		if ( is_string( $editor_url_template ) && str_contains( $editor_url_template, '{{file}}' ) ) {
			$file_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
			if ( $plugin_slug !== $filename ) {
				$file_path .= '/' . $filename;
			}

			if ( file_exists( $file_path ) ) {
				/**
				 * Filters the file path to be opened in an external editor for a given PHPCS error source.
				 *
				 * This is useful to map the file path from inside of a Docker container or VM to the host machine.
				 *
				 * @since 1.0.0
				 *
				 * @param string|null $editor_url_template Editor URL template.
				 * @param array       $source              Source information.
				 */
				$file_path = apply_filters( 'wp_plugin_check_validation_error_source_file_path', $file_path, array( $plugin_slug, $filename, $line ) );
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
