<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Plugin_Updaters extends Check_Base {

	function check_update_uri() {
		if ( ! empty( $this->headers['UpdateURI'] ) ) {
			return new Error(
				'plugin_updater_detected',
				'Plugin Updater detected. Use of the Update URI header is not helpful in plugins hosted on WordPress.org.'
			);
		}
	}

	function check_updaters() {
		$filenames     = array_map( 'strtolower', array_map( 'basename', $this->files ) );
		$blocked_files = [
			'plugin-update-checker.php'
		];
		$matches       = array_intersect( $filenames, $blocked_files );
		if ( $matches ) {
			return new Error(
				'plugin_updater_detected',
				sprintf(
					__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
					esc_html( implode( ', ', $matches ) )
				)
			);
		}

		$needles = [
			"'plugin-update-checker'",
			'WP_GitHub_Updater',
			'WPGitHubUpdater',
			'#class [A-Z_]+_Plugin_Updater#i',
			'#updater\.\w+\.\w{2,5}#i',
			'site_transient_update_plugins'
		];

		foreach ( $needles as $needle ) {
			if ( $match = $this->scan_matching_files_for_needle( $needle, '\.php$' ) ) {
				return new Error(
					'plugin_updater_detected',
					sprintf(
						__( 'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s', 'plugin-check' ),
						esc_html( $match )
					)
				);
			}
		}
	}

	function check_updater_warnings() {
		$needles = [
			"#pre_set_site_transient_update_\w+#i",
			'auto_update_plugin',
			'#_site_transient_update_\w+#i',
		];

		foreach ( $needles as $needle ) {
			if ( $match = $this->scan_matching_files_for_needle( $needle, '\.php$' ) ) {
				return new Warning(
					'update_modification_detected',
					sprintf(
						__( 'Detected code which may be altering WordPress update routines. Detected: %s', 'plugin-check' ),
						esc_html( $match )
					)
				);
			}
		}
	}

}
