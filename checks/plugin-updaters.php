<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Plugin_Updaters extends Check_Base {

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
					'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s',
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
						'Plugin Updater detected. These are not permitted in WordPress.org hosted plugins. Detected: %s',
						esc_html( $match )
					)
				);
			}
		}
	}

}
