<?php

use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Traits\URL_Aware;

class Example_Runtime_Check extends Abstract_Runtime_Check {

	use Amend_Check_Result;
	use Stable_Check;
	use URL_Aware;

	public function get_categories() {
		return array( 'new_category' );
	}

	public function prepare() {
		$orig_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;

		// Backup the original values for the global state.
		$this->backup_globals();

		return function () use ( $orig_scripts ) {
			if ( is_null( $orig_scripts ) ) {
				unset( $GLOBALS['wp_scripts'] );
			} else {
				$GLOBALS['wp_scripts'] = $orig_scripts;
			}

			$this->restore_globals();
		};
	}

	public function run( Check_Result $result ) {
		$this->run_for_urls(
			array( home_url() ),
			function ( $url ) use ( $result ) {
				$this->check_url( $result, $url );
			}
		);
	}

	protected function check_url( Check_Result $result, $url ) {
		// Reset the WP_Scripts instance.
		unset( $GLOBALS['wp_scripts'] );

		// Run the 'wp_enqueue_script' action, wrapped in an output buffer in case of any callbacks printing scripts
		// directly. This is discouraged, but some plugins or themes are still doing it.
		ob_start();
		wp_enqueue_scripts();
		wp_scripts()->do_head_items();
		wp_scripts()->do_footer_items();
		ob_end_clean();

		foreach ( wp_scripts()->done as $handle ) {
			$script = wp_scripts()->registered[ $handle ];

			if ( strpos( $script->src, $result->plugin()->url() ) !== 0 ) {
				continue;
			}

			$script_path = str_replace( $result->plugin()->url(), $result->plugin()->path(), $script->src );

			$this->add_result_warning_for_file(
				$result,
				sprintf(
					'Not allowed to enqueue scripts. Found script handle "%s"',
					$handle
				),
				'ExampleRuntimeCheck.ForbiddenScript',
				$script_path
			);
		}
	}

	public function get_description(): string {
		return '';
	}

	public function get_documentation_url(): string {
		return '';
	}
}
