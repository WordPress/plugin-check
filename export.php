<?php
/**
 * This file is for plugin-check functions that are exposed globally.
 */
function plugin_check( $args ) {
	return WordPressdotorg\Plugin_Check\run_all_checks( $args );
}