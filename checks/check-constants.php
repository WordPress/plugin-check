<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Code_Constants extends Check_Base {

	/**
	 * Checks if any PHP files contain the `ALLOW_UNFILTERED_UPLOADS` constant. Not permitted.
	 *
	 * @since 0.2.0
	 *
	 * @return void|Error
	 */
	public function check_allow_unfiltered_uploads() {
		if ( ! $this->scan_matching_files_for_needle( 'ALLOW_UNFILTERED_UPLOADS', '\.php$' ) ) {
			return;
		}

		return new Error(
			'allow_unfiltered_uploads_detected',
			__( 'ALLOW_UNFILTERED_UPLOADS is not permitted.', 'plugin-check' )
		);
	}

}
