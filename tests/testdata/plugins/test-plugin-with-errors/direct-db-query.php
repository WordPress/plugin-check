<?php
/**
 * File contains errors related to direct db query.
 */

global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"
			UPDATE $wpdb->posts
			SET post_status = '%s'
			WHERE post_type = '%s'
			AND post_status = '%s'
			",
		'publish',
		'tribe_events',
		'future'
	)
);
