<?php
/**
 * Plugin Name: Test Plugin Personal Data Eraser With Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Personal Data Eraser check — stores user meta but does not register a data eraser.
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-personal-data-eraser-errors
 *
 * @package test-plugin-personal-data-eraser-errors
 */

/**
 * Saves a custom preference for a user.
 *
 * @param int $user_id User ID.
 */
function test_pdel_save_user_preference( $user_id ) {
	update_user_meta( $user_id, 'test_pdel_preference', 'some_value' );
}
add_action( 'user_register', 'test_pdel_save_user_preference' );
