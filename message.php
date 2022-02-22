<?php
namespace WordPressdotorg\Plugin_Check;
use WP_Error;

class Message extends WP_Error {
	function __construct( $code, $message = '', $data = null ) {
		if ( ! $message && $code ) {
			$message = $code;
			$code = sanitize_title( $code );
		}

		return parent::__construct( $code, $message, $data );
	}
}

class Notice extends Message {}
class Warning extends Notice {}
class Error extends Warning {}
class Guideline_Violation extends Error {}