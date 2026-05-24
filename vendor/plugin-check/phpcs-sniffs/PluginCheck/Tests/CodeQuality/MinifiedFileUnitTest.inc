<?php
/**
 * Normal, properly formatted PHP file for testing.
 */

function properly_formatted_function() {
	global $wpdb;

	$user_id = absint( $_GET['user_id'] );

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $wpdb->posts WHERE post_author = %d",
			$user_id
		)
	);

	return $results;
}

add_action( 'init', 'properly_formatted_function' );

// Another function with proper formatting
function another_function( $param ) {
	if ( ! $param ) {
		return false;
	}

	return do_something( $param );
}

