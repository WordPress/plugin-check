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

	function check_against_phpcs() {
		if ( ! HAS_VENDOR ) {
			return new Notice(
				'phpcs_not_tested',
				'PHP CS rulesets have not been tested, as the vendor directory is missing. Perhaps you need to run <code>`composer install`</code>.'
			);
		}

		return $this->run_phpcs_standard(
			__DIR__ . '/phpcs/plugin-check.xml'
		);
	}

	function check_against_phpcs_review() {
		if ( ! HAS_VENDOR ) {
			return new Notice(
				'phpcs_not_tested',
				'PHP CS rulesets have not been tested, as the vendor directory is missing. Perhaps you need to run <code>`composer install`</code>.'
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

				$return[] = new $notice_class(
					$message['source'],
					sprintf(
						'%s Line %d of file %s.<br>%s.<br>%s',
						"<strong>{$message['source']}</strong>",
						$message['line'],
						$filename,
						rtrim( $message['message'], '.' ),
						"<pre><code>{$source_code}</code></pre>"
					)
				);
			}
		} );

		return $return;
	}
}