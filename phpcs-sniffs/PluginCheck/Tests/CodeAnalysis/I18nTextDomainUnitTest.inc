<?php
__( 'Hello World' ); // Bad - missing domain.
__( 'Save Changes' ); // Good - core translation.
_x( 'Duplicate', 'action label' ); // Good - core translation.
esc_html__( 'Google Fonts' ); // Bad - not available in core without context.

// Good examples (with domain):
__( 'Hello World', 'my-plugin' ); // Good.
__( 'Save Changes', 'my-plugin' ); // Good.
_x( 'Duplicate', 'action label', 'my-plugin' ); // Good.

// Strings found in core.
__( '66 / 33' ); // Good.
__( 'Audio title&hellip;' ); // Good.
echo sprintf( esc_html__( 'Font collection "%s" not found.' ), 'name' ); // Good.
echo sprintf( esc_html__( '[%s] Your Site is Experiencing a Technical Issue' ), 'Custom Message' ); // Good.
_x( 'Date', 'block title' ); // Good.
_x( 'Duplicate', 'action label' ); // Good.
