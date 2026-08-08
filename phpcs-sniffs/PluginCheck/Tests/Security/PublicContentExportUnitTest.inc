<?php
/**
 * Test fixture for PublicContentExportSniff.
 *
 * Contains code patterns that should trigger warnings, and patterns
 * that should be clean (suppressed by access-control guards or non-matches).
 *
 * Each test case is wrapped in a named function so scope-level guard
 * detection works correctly.
 */

// -- Triggers: file writes with post content (no guard) --

// Warning: file_put_contents with the_content().
function trigger_the_content_export() {
	$content = the_content();
	file_put_contents( '/tmp/export.html', $content );
}

// Warning: fwrite with get_the_content().
function trigger_get_content_fwrite() {
	$fp   = fopen( '/tmp/export.txt', 'w' );
	$body = get_the_content();
	fwrite( $fp, $body );
}

// Warning: fputs with get_the_excerpt().
function trigger_excerpt_fputs() {
	$fp = fopen( '/tmp/excerpt.txt', 'w' );
	fputs( $fp, get_the_excerpt() );
}

// Warning: file_put_contents with $post->post_content property access.
function trigger_post_content_property() {
	$post = get_post( 42 );
	file_put_contents( '/tmp/post.txt', $post->post_content );
}

// Warning: apply_filters with the_content filter into file write.
function trigger_filter_the_content() {
	$post     = get_post( 42 );
	$rendered = apply_filters( 'the_content', $post->post_content );
	file_put_contents( '/tmp/rendered.html', $rendered );
}

// Warning: fwrite with get_post_field('post_content').
function trigger_get_post_field() {
	$fp = fopen( '/tmp/field.txt', 'w' );
	fwrite( $fp, get_post_field( 'post_content', 42 ) );
	fclose( $fp );
}

// -- Suppressed by guard (no warning expected) --

// Suppressed: post_password_required() guard before file write.
function guarded_password_check() {
	$post    = get_post( 42 );
	$content = the_content();
	if ( post_password_required( $post ) ) {
		return;
	}
	file_put_contents( '/tmp/export.html', $content );
}

// Suppressed: current_user_can() guard before file write.
function guarded_cap_check() {
	$content = the_content();
	if ( current_user_can( 'edit_posts' ) ) {
		file_put_contents( '/tmp/admin-export.html', $content );
	}
}

// Suppressed: is_post_type_viewable() guard before file write.
function guarded_viewable_check() {
	$post = get_post( 42 );
	if ( ! is_post_type_viewable( $post->post_type ) ) {
		return;
	}
	file_put_contents( '/tmp/public-post.html', get_the_content( null, false, $post ) );
}

// Suppressed: is_user_logged_in() guard before file write.
function guarded_login_check() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	file_put_contents( '/tmp/members-export.html', get_the_content() );
}

// -- Non-matches (no warning expected) --

// No warning: file_put_contents without post content reference.
function safe_no_content() {
	file_put_contents( '/tmp/log.txt', 'Log entry' );
}

// No warning: fwrite with non-content data.
function safe_static_data() {
	$fp   = fopen( '/tmp/data.txt', 'w' );
	$data = 'static page content';
	fwrite( $fp, $data );
	fclose( $fp );
}

// No warning: get_post_field with non-post_content field.
function safe_non_content_field() {
	$fp = fopen( '/tmp/title.txt', 'w' );
	fwrite( $fp, get_post_field( 'post_title', 42 ) );
	fclose( $fp );
}

// No warning: apply_filters with a non-content filter name.
function safe_custom_filter() {
	$data  = 'arbitrary data';
	$value = apply_filters( 'my_custom_filter', $data );
	file_put_contents( '/tmp/custom.txt', $value );
}

// No warning: the_content() called standalone, not passed to a file write.
function safe_standalone_content() {
	the_content();
}

// No warning: $post->post_title is not post_content.
function safe_post_title() {
	$post = get_post( 42 );
	file_put_contents( '/tmp/title.txt', $post->post_title );
}
