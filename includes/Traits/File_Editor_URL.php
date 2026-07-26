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

		$file_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
		if ( $plugin_slug !== $filename ) {
			$file_path .= '/' . $filename;
		}

		if ( file_exists( $file_path ) ) {
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
			 * @since 2.0.0
			 *
			 * @param string|null $url    Editor URL. Default null.
			 * @param array       $source Source information: file, line, plugin, filename.
			 */
			$url = apply_filters(
				'wp_plugin_check_validation_error_source_url',
				null,
				array(
					'file'     => $file_path,
					'line'     => $line,
					'plugin'   => $plugin_slug,
					'filename' => $filename,
				)
			);

			if ( is_string( $url ) && '' !== $url ) {
				$edit_url = str_replace(
					array(
						'{{file}}',
						'{{line}}',
					),
					array(
						$file_path,
						(int) $line,
					),
					$url
				);
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
