<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use const WordPressdotorg\Plugin_Check\{ PLUGIN_DIR, HAS_VENDOR };
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};
use WordPressdotorg\Plugin_Check\PHPCS;

include PLUGIN_DIR . '/inc/class-php-cli.php';
include PLUGIN_DIR . '/inc/class-phpcs.php';

class PHPCS_Checks extends Check_Base {

	const NOTICE_TYPES = [
		// This should be an Error, but this is triggered for all variablse with SQL which isn't always a problem.
		//'WordPress.DB.PreparedSQL.InterpolatedNotPrepared' => Warning::class,
	];

	public function check_against_phpcs() {
		if ( ! HAS_VENDOR ) {
			return new Notice(
				'phpcs_not_tested',
				__( 'PHP Code Sniffer rulesets have not been tested, as the vendor directory is missing. Perhaps you need to run <code>`composer install`</code>.', 'plugin-check' )
			);
		}

		return $this->run_phpcs_standard(
			__DIR__ . '/phpcs/plugin-check.xml'
		);
	}

	public function check_against_phpcs_review() {
		if ( ! HAS_VENDOR ) {
			return new Notice(
				'phpcs_not_tested',
				__( 'PHP Code Sniffer rulesets have not been tested, as the vendor directory is missing. Perhaps you need to run <code>`composer install`</code>.', 'plugin-check' )
			);
		}

		return $this->run_phpcs_standard(
			__DIR__ . '/phpcs/plugin-check-needs-review.xml'
		);
	}

	protected function run_phpcs_standard( string $standard, array $args = [] ) {
		$phpcs = new PHPCS();
		$phpcs->set_standard( $standard );

		$args = wp_parse_args(
			$args,
			array(
				'extensions' => 'php', // Only check php files.
				's'          => true, // Show the name of the sniff triggering a violation.
				// --ignore-annotations
			)
		);

		$report = $phpcs->run_json_report(
			$this->path,
			$args,
			'array'
		);

		if ( is_wp_error( $report ) ) {
			return new Error(
				$report->get_error_code(),
				$report->get_error_message()
			);
		}

		// If no response, either malformed output or PHP encountered an error.
		if ( ! $report || empty( $report['files'] ) ) {
			return false;
		}

		return $this->phpcs_result_to_warnings( $report );
	}

	protected function phpcs_result_to_warnings( $result ) {
		$return = [];

		array_walk( $result['files'], function( $output, $filename ) use( &$return ) {
			if ( ! $output['messages'] ) {
				return;
			}

			// Ignore the column, and just use the Error + Line number.
			$messages = [];
			foreach ( $output['messages'] as &$message ) {
				$messages[ $message['source'] . ':' . $message['line'] ] = $message;
			}

			foreach ( $messages as $message ) {
				switch( strtoupper( $message['type'] ) ) {
					case 'ERROR':
						$notice_class = Error::class;
						break;
					case 'WARNING':
						$notice_class = Warning::class;
						break;
					case 'INFO':
					case 'NOTICE':
						$notice_class = Notice::class;
						break;
					default:
						$notice_class = Message::class;
				}

				// Allow for individual notices to be overridden.
				if ( isset( self::NOTICE_TYPES[ $message['source'] ] ) ) {
					$notice_class = self::NOTICE_TYPES[ $message['source'] ];
				}

				$source_code = esc_html( trim( file( $this->path . '/' . $filename )[ $message['line'] - 1 ] ) );

				if ( current_user_can( 'edit_plugins' ) ) {
					$edit_link   = sprintf(
						'<a href="%1$s" title="%2$s" aria-label="%2$s" target="_blank">%3$s</a>',
						$this->get_file_editor_url( $filename, $message['line'] ),
						sprintf(
							/* translators: %s is the path to a plugin file. */
							esc_attr__( 'View %s in the plugin file editor.', 'plugin-check' ),
							$this->slug . '/' . $filename
						),
						esc_html__( 'View in code editor', 'plugin-check' )
					);
				}

				$return[] = new $notice_class(
					$message['source'],
					sprintf(
						/* translators: 1: Type of Error 2: Line 3: File 4: Message 5: Code Example 6: Edit Link */
						__( '%1$s Line %2$d of file %3$s.<br>%4$s.<br>%5$s%6$s', 'plugin-check' ),
						"<strong>{$message['source']}</strong>",
						$message['line'],
						$filename,
						rtrim( $message['message'], '.' ),
						"<pre class='wp-plugin-check-code'><code>{$source_code}</code></pre>",
						$edit_link ?? ''
					)
				);
			}
		} );

		return $return;
	}

	/**
	 * Get the URL for opening the plugin file in an external editor.
	 *
	 * @since 0.2.1
	 *
	 * @param array $filename Source of PHPCS error.
	 * @param array $line     Line number of PHPCS error.
	 *
	 * @return string|null File editor URL or null if not available.
	 */
	private function get_file_editor_url( $filename, $line ) {
		if ( ! isset( $filename, $line ) ) {
			return null;
		}

		$edit_url = null;

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
		 * add_filter( 'plugin_check_validation_error_source_file_editor_url_template', function () {
		 *     return 'phpstorm://open?file={{file}}&line={{line}}';
		 * } );
		 *
		 * # VS Code
		 * add_filter( 'plugin_check_validation_error_source_file_editor_url_template', function () {
		 *     return 'vscode://file/{{file}}:{{line}}';
		 * } );
		 *
		 * For a template to be considered, the string '{{file}}' must be present in the filtered value.
		 *
		 * @since 0.2.1
		 *
		 * @param string|null $editor_url_template Editor URL template.
		 */
		$editor_url_template = apply_filters( 'plugin_check_validation_error_source_file_editor_url_template', null );

		// Supply the file path to the editor template.
		if ( null !== $editor_url_template && str_contains( $editor_url_template, '{{file}}' ) ) {
			$file_path = WP_PLUGIN_DIR . '/' . $this->slug;
			if ( $this->slug !== $filename ) {
				$file_path .= '/' . $filename;
			}

			if ( $file_path && file_exists( $file_path ) ) {
				/**
				 * Filters the file path to be opened in an external editor for a given PHPCS error source.
				 *
				 * This is useful to map the file path from inside of a Docker container or VM to the host machine.
				 *
				 * @since 0.2.1
				 *
				 * @param string|null $editor_url_template Editor URL template.
				 * @param array       $source              Source information.
				 */
				$file_path = apply_filters( 'plugin_check_validation_error_source_file_path', $file_path, array( $this->slug, $filename, $line) );
				if ( $file_path ) {
					$edit_url = str_replace(
						[
							'{{file}}',
							'{{line}}',
						],
						[
							rawurlencode( $file_path ),
							rawurlencode( $line ),
						],
						$editor_url_template
					);
				}

			}
		}

		// Fall back to using the plugin editor if no external editor is offered.
		if ( ! $edit_url ) {
			$plugin_data = get_plugins( '/' . $this->slug );

			return esc_url(
				add_query_arg(
					[
						'plugin' => rawurlencode( $this->slug . '/' . array_key_first( $plugin_data ) ),
						'file'   => rawurlencode( $this->slug . '/' . $filename ),
						'line'   => rawurlencode( $line ),
					],
					admin_url( 'plugin-editor.php' )
				)
			);
		}

		return $edit_url;
	}
}