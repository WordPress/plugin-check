<?php
/**
 * Test file for NonceVerificationSniff
 */

// ============================================================================
// BAD: Superglobals used for actual processing without nonce verification
// ============================================================================

$nonce_check = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

function process_form_data_1() {
	$name = sanitize_text_field( wp_unslash( $_POST['name'] ) ); // Should flag - direct assignment without nonce.
}

function process_form_data_2() {
	$data  = $_POST['data']; // Should flag - direct assignment without nonce.
	$value = $_POST['value']; // Should flag - direct assignment without nonce.
}

function process_form_data_3() {
	$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // Should flag - processing GET without nonce.
}

function process_form_data_4() {
	$total = intval( $_POST['quantity'] ) * floatval( $_POST['price'] ); // Should flag - computation without nonce.
}

// ============================================================================
// BAD: Superglobal in a conditional with nonce check is safe, but direct
// processing later in the same function should still be flagged.
// This function also contains $_POST inside wp_verify_nonce which is safe.
// The isset/empty on $_POST outside wp_verify_nonce should still be safe.
// ============================================================================

function process_with_nonce_check() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'my_action' ) ) {
		return false;
	}

	update_option( $name, sanitize_text_field( wp_unslash( $_POST['name'] ) ) ); // Protected by nonce check above, not flagged.
}

// ============================================================================
// GOOD: empty() and isset() on superglobals should not be flagged
// ============================================================================

function safe_empty_check_1() {
	if ( empty( $_REQUEST['g-recaptcha-response'] ) ) {
		return false;
	}
}

function safe_empty_check_2() {
	if ( empty( $_POST['field_name'] ) ) {
		return;
	}
	process_data( $data );
}

function safe_isset_check_1() {
	if ( isset( $_GET['page'] ) ) {
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
	}
}

function safe_isset_check_2() {
	if ( isset( $_POST['submit'] ) ) {
		// Form was submitted.
	}
}

function safe_empty_check_3() {
	if ( empty( $_FILES['my_file'] ) ) {
		return;
	}
}

function safe_empty_check_4() {
	if ( isset( $_POST['action'] ) ) {
		// Only checking existence.
	}
}
