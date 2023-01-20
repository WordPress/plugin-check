<?php
/**
 * File contains errors related to i18n translation issues.
 */

$city = 'Surat';

printf(
	__( 'Your city is %s.', 'test-plugin-check' ),
	$city
);

$text_domain = 'test-plugin-check';

esc_html_e( 'Hello World!', $text_domain );
