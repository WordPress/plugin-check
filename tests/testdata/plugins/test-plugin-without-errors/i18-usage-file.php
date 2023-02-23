<?php
/**
 * File contains no errors related to i18n translation issues.
 */

$city = 'Surat';

sprintf(
	/* translators: %s: Name of a city */
	__( 'Your city is %s.', 'test-plugin-check' ),
	$city
);

esc_html__( 'Hello World!', 'test-plugin-check' );
