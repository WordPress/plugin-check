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

$str = <<<EOD
Example of string
spanning multiple lines
using heredoc syntax.
EOD;

parse_str( 'first=value&arr[]=foo+bar&arr[]=baz' );

$encoded_value = json_encode( array( 'key' => 'value' ) );

file_get_contents( $url );
file_put_contents();

load_plugin_textdomain( 'sample-textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// Test the existing forbidden functions
create_function( '$a', 'return $a;' );
eval( '$test = "hello";' );
move_uploaded_file( $tmp_name, $destination );
passthru( 'ls -la' );
proc_open( 'ls', $descriptorspec, $pipes );
str_rot13( 'hello world' );

// Test the new forbidden functions
_cleanup_header_comment( $comment );
_get_plugin_data_markup_translate( $plugin_data );
_transition_post_status( 'publish', 'draft', $post );
_wp_post_revision_fields( $post );
do_shortcode_tag( $shortcode_tag );
get_post_type_labels( $post_type );
wp_get_sidebars_widgets();
wp_get_widget_defaults( $widget_id );

// NOWDOC example; Should not trigger error.
$str = <<<'NOWDOC'
Example of string
spanning multiple lines
using nowdoc syntax.
NOWDOC;

// Test short URL detection.
$short_url = 'https://bit.ly/abc123';
