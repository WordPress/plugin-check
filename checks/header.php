<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\{Error, Guideline_Violation, Message, Notice, Warning};

class Header extends Check_Base {
	function check_textdomain() {
		if (
			! empty( $this->slug ) &&
			! empty( $this->headers['TextDomain'] ) &&
			$this->slug !== $this->headers['TextDomain']
		) {
			return new Warning(
				'textdomain_mismatch',
				sprintf(
					'TextDomain header in plugin file does not match slug. Found %s, expected %s.',
					'<code>' . esc_html( $this->headers['TextDomain'] ) . '</code>',
					'<code>' . esc_html( $this->slug ) . '</code>'
				)
			);
		}
	}
}