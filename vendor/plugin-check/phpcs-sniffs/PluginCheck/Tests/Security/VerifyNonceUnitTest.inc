<?php
/**
 * Test file for VerifyNonceSniff
 */

// BAD: Nonce not checked if it's unset - line 7
function insecure_nonce_1() {
	if ( isset( $post_data['nonce'] ) && ! wp_verify_nonce( $post_data['nonce'], 'my_nonce' ) ) { // unsafe!
		wp_send_json_error( new WP_Error( 'invalid_code' ) );
	}
}

// BAD: Nonce not checked if it's unset - line 13
function insecure_nonce_2() {
	if ( isset( $_REQUEST['my-nonce'] ) && ! wp_verify_nonce( $_REQUEST['my-nonce'], 'post-nonce' ) ) // unsafe!
		die( 'Security check' );
}

// BAD: Nonce not checked if it's unset - line 19
function insecure_nonce_3() {
	if ( isset( $_POST['my_nonce_field'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['my_nonce_field'] ) ), 'my_nonce' ) ) { // unsafe!
		return;
	}
}

// BAD: Unconditional call - line 26
function insecure_nonce_4() {
	wp_verify_nonce( $_POST['my_nonce_field'], 'my-action' ); // unsafe!
}

// BAD: OR condition with else - line 32
function insecure_nonce_5() {
	if ( $something || wp_verify_nonce( $_REQUEST['my-nonce'], 'post-nonce' ) ) {
		do_something();
	} else {
		wp_nonce_ays();
	}
}

// BAD: AND instead of OR - line 41
function insecure_nonce_6() {
	if ( ! isset( $_REQUEST['nonce'] ) && ! wp_verify_nonce( $_REQUEST['nonce'], 'nonce' ) ) return; // unsafe!
}

// BAD: AND instead of OR - line 46
function insecure_nonce_7() {
	if (!isset($_GET['my-nonce']) && !wp_verify_nonce($_GET['my-nonce'], 'delete')) {
		if (!current_user_can('editor') || !current_user_can('administrator')) {
			return false;
		}
	}
}

// BAD: OR condition without else - line 54
function insecure_nonce_8() {
	if ( $something || wp_verify_nonce( $_REQUEST['my-nonce'], 'post-nonce' ) )
		do_something();
	else
		wp_nonce_ays();
}

// ============================================================================
// GOOD EXAMPLES BELOW
// ============================================================================

// GOOD: Example from docs
function safe_example_1() {
	if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpdocs-my-nonce' ) ) { // safe!
		//do you action
	} else {
		die( __( 'Security check', 'textdomain' ) );
	}
}

// GOOD: Another doc example
function safe_example_2() {
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'my-nonce' ) ) {
		die( __( 'Security check', 'textdomain' ) );
	} else {
		// Do stuff here.
	}
}

// GOOD: Nonce check in first clause
function safe_example_3() {
	if ( wp_verify_nonce( $nonce, 'my-nonce' ) ) { // safe since the action is in the first clause
		$result = do_some_stuff();
	} else {
		$result = 'error';
	}

	return $result;
}

// GOOD: Helper function example
function false_positive_1( $nonce ) {
	return wp_verify_nonce( $nonce, $this->get_nonce_action() ); // safe
}

// GOOD: Nonce check with parentheses
function safe_example_4() {
	if ( ( wp_verify_nonce( $nonce, 'my-nonce' ) ) ) {
		do_something();
	} else {
		die();
	}
}

// GOOD: Nonce assigned to variable first
function safe_example_5() {
	$check = wp_verify_nonce(sanitize_text_field($_POST['security']), 'my-nonce');
	if (!$check)
		return;
}

// GOOD: Ternary with isset
function safe_example_6() {
	$is_valid = ( isset( $_POST[ 'my_nonce' ] ) && wp_verify_nonce( $_POST[ 'my_nonce' ], 'something' ) ) ? true : false;
	return $is_valid;
}

// GOOD: Multiple nonce checks with OR
function safe_example_7() {
	if (  wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpdocs-my-nonce-1' ) || wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpdocs-my-nonce-2' ) ) { // safe!
		//do you action
	} else {
		die( __( 'Security check', 'textdomain' ) );
	}
}

// GOOD: Multiple nonce checks with AND in negative condition
function safe_example_8() {
	if (  !wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpdocs-my-nonce-1' ) && !wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpdocs-my-nonce-2' ) ) { // safe!
		die( __( 'Security check', 'textdomain' ) );
	} else {
		// do secure action
	}
}

// GOOD: Nonce check first in AND condition
function safe_example_9() {
	if ( wp_verify_nonce( $nonce, 'my-nonce' ) && $something_else ) {
		// do secure action
	} else {
		die( __( 'Security check', 'textdomain' ) );
	}
}

// GOOD: Simple if/else without braces
function safe_example_10() {
	if ( wp_verify_nonce( $nonce, 'my-nonce' ) )
		do_something();
	else
		die;
}

// GOOD: Short-circuit with early return is safe
function safe_example_11() {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'csf_taxonomy_nonce' ) ) { // safe!
		return;
	}

	// do secure action
}

// GOOD: Use check_admin_referer() for unconditional checks
check_admin_referer( 'my_action' );
update_option( 'my_option', $_POST['value'] );
