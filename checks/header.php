<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Header extends Check_Base {
	function check_textdomain() {
		if (
			isset( $this->slug, $this->headers['TextDomain'] ) &&
			$this->slug !== $this->headers['TextDomain']
		) {
			return new Warning(
				'textdomain_mismatch',
				'TextDomain header in plugin file does not match slug.'
			);
		}
	}
}