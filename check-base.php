<?php
namespace WordPressdotorg\Plugin_Check\Checks;
use WordPressdotorg\Plugin_Check\Notice;

class Check_Base {
	public function __invoke( $args ) {
		$messages = [];

		foreach ( get_class_methods( $this ) as $method ) {
			if ( ! str_starts_with( $method, 'check_' ) ) {
				continue;
			}

			$result = $this->$method( $args );

			if ( ! $result ) {
				$short_name = substr( $method, 6 );
				$result     = new Notice( $this->name() . ' ' . $short_name . ' returned false.' );
			}

			if ( is_wp_error( $result ) ) {
				$messages[] = $result;
			}
		}

		return $messages;
	}

	public function name() {
		if ( defined( get_class( $this ) . '::NAME' ) ) {
			return $this::NAME;
		}

		if ( isset( $this->name ) ) {
			return $this->name;
		}

		return str_replace( __NAMESPACE__, '', get_class( $this ) );
 	}
}
