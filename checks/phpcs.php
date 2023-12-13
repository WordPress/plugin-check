<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use const WordPressdotorg\Plugin_Check\{ PLUGIN_DIR, HAS_VENDOR };
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};
use WordPressdotorg\Plugin_Check\PHPCS;
use WordPressdotorg\Plugin_Check\PHPCS_Runner;

class PHPCS_Checks extends Check_Base {

	const NOTICE_TYPES = [
		// This should be an Error, but this is triggered for all variables with SQL which isn't always a problem.
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

	/**
	 * Attempts to load Codesniffer and return a status if it's safe to use the runner.
	 *
	 * @since 0.2.2
	 *
	 * @return bool
	 */
	protected function load_codesniffer_runner(): bool {
		if ( class_exists( '\PHP_CodeSniffer\Runner' ) ) {
			return true;
		}

		// Include the PHPCS autoloader.
		$autoloader = PLUGIN_DIR . '/vendor/squizlabs/php_codesniffer/autoload.php';

		if ( file_exists( $autoloader ) ) {
			include_once $autoloader;
		}

		return class_exists( '\PHP_CodeSniffer\Runner' );
	}

	protected function run_phpcs_standard( string $standard, array $args = [] ) {
		include_once PLUGIN_DIR . '/inc/class-phpcs-runner.php';

		if ( ! $this->load_codesniffer_runner() ) {
			return new Notice(
				'phpcs_runner_not_found',
				esc_html__( 'PHP Code Sniffer rulesets have not been tested, as the Code Sniffer Runner class is missing.', 'plugin-check' )
			);
		}

		$phpcs = new PHPCS_Runner();
		$phpcs->set_path( $this->path );
		$phpcs->set_standard( $standard );

		$results = $phpcs->run();

		if ( is_wp_error( $results ) ) {
			return new Error(
				$results->get_error_code(),
				$results->get_error_message()
			);
		}

		return $this->phpcs_result_to_warnings( $results );
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

				$file_path = dirname( $this->path ) . '/' . $filename;

				$source_code = esc_html( trim( file( $file_path )[ $message['line'] - 1 ] ) );

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
						__( '%1$s<br><br>Line %2$d of file <code>%3$s</code>.<br>%4$s.<br>%5$s%6$s', 'plugin-check' ),
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
	protected function get_file_editor_url( $filename, $line ) {
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
			if ( ! str_starts_with( $filename, $this->slug ) ) {
				$filename = $this->slug . '/' . $filename;
			}

			return esc_url(
				add_query_arg(
					[
						'plugin' => rawurlencode( $this->slug . '/' . array_key_first( $plugin_data ) ),
						'file'   => rawurlencode( $filename ),
						'line'   => rawurlencode( $line ),
					],
					admin_url( 'plugin-editor.php' )
				)
			);
		}

		return $edit_url;
	}
}