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
 * @since n.e.x.t
 */
trait File_Editor_URL {

	/**
	 * Gets the URL for opening the plugin file in an external editor.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param string       $filename Error file name.
	 * @param int          $line     Line number of error.
	 * @return string|null File editor URL or null if not available.
	 */
	protected function get_file_editor_url( Check_Result $result, $filename, $line ) {
		if ( ! isset( $filename, $line ) ) {
			return null;
		}

		$edit_url = null;

		$plugin_path = $result->plugin()->path( '/' );
		$plugin_slug = basename( $plugin_path );
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
		 * @since n.e.x.t
		 *
		 * @param string|null $editor_url_template Editor URL template. default null.
		 */
		$editor_url_template = apply_filters( 'wp_plugin_check_validation_error_source_file_editor_url_template', null );

		// Supply the file path to the editor template.
		if ( null !== $editor_url_template && str_contains( $editor_url_template, '{{file}}' ) ) {
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
				 * @since n.e.x.t
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
			return add_query_arg(
				array(
					'plugin' => rawurlencode( $result->plugin()->basename() ),
					'file'   => rawurlencode( $plugin_slug . '/' . $filename ),
					'line'   => $line,
				),
				admin_url( 'plugin-editor.php' )
			);
		}
		return $edit_url;
	}
}
