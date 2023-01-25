<?php
/**
 * File contains no errors related to i18n translation issues.
 */

$city = 'Surat';

printf(
	/* translators: %s: Name of a city */
	__( 'Your city is %s.', 'test-plugin-check' ),
	$city
);

esc_html_e( 'Hello World!', 'test-plugin-check' );
