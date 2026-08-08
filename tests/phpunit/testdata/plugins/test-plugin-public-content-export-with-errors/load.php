<?php
/**
 * Plugin Name: Test Plugin Public Content Export With Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin that exports post content to files without access-control guards.
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-public-content-export-with-errors
 *
 * @package test-plugin-public-content-export-with-errors
 */

// Trigger: file_put_contents with the_content().
// Expected warning: PostContentExport.
function test_export_with_the_content() {
	$content = the_content();
	file_put_contents( '/tmp/export.html', $content );
}

// Trigger: fwrite with get_the_content().
// Expected warning: PostContentExport.
function test_fwrite_with_get_content() {
	$fp   = fopen( '/tmp/export.txt', 'w' );
	$body = get_the_content();
	fwrite( $fp, $body );
	fclose( $fp );
}

// Trigger: fputs with get_the_excerpt().
// Expected warning: PostContentExport.
function test_fputs_with_excerpt() {
	$fp = fopen( '/tmp/excerpt.txt', 'w' );
	fputs( $fp, get_the_excerpt() );
	fclose( $fp );
}

// Trigger: file_put_contents with $post->post_content.
// Expected warning: PostContentExport.
function test_export_post_content_property() {
	$post = get_post( 42 );
	file_put_contents( '/tmp/post.txt', $post->post_content );
}

// Trigger: file_put_contents with apply_filters('the_content').
// Expected warning: PostContentExport.
function test_export_with_filter_the_content() {
	$post     = get_post( 42 );
	$rendered = apply_filters( 'the_content', $post->post_content );
	file_put_contents( '/tmp/rendered.html', $rendered );
}

// Trigger: fwrite with get_post_field('post_content').
// Expected warning: PostContentExport.
function test_export_with_get_post_field() {
	$fp = fopen( '/tmp/field.txt', 'w' );
	fwrite( $fp, get_post_field( 'post_content', 42 ) );
	fclose( $fp );
}
