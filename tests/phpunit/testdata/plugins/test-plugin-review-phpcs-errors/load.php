<?php
/**
 * File contains errors related to plugin review PHPCS issues.
 */
?>
<?
/**
 * Check for PHP short tag and DeprecatedFunctions.
 */

ob_start();
    the_author_email();
$the_author_email = ob_get_clean();

$var_post_not_sanitized = $_POST['not_sanitized'];

set_time_limit( 20 );
ini_set( 'max_execution_time', 20 );
ini_alter( 'max_execution_time', 20 );
dl( 'plugin-check.so' );

var_dump( $custom_var );
error_log( 'Error occurred.');

query_posts( 'cat=3' );
wp_reset_query();
