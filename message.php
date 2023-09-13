<?php
namespace WordPressdotorg\Plugin_Check;
use WP_Error;

class Message extends WP_Error {
	/**
	 * The error class used for UI displays.
	 */
	public $error_class = 'info';

	function __construct( $code, $message = '', $data = null ) {
		if ( ! $message && $code ) {
			$message = $code;
			$code = sanitize_title( $code );
		}

		return parent::__construct( $code, $message, $data );
	}
}

class Notice extends Message {
}
class Warning extends Notice {
	public $error_class = 'warning';
}
class Error extends Warning {
	public $error_class = 'error';
}
class Guideline_Violation extends Error {
}