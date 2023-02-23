<?php
/**
 * File contains errors related to i18n translation issues.
 */

$city = 'Surat';

sprintf(
	__( 'Your city is %s.', 'test-plugin-check-errors' ),
	$city
);

$text_domain = 'test-plugin-check-errors';

esc_html__( 'Hello World!', $text_domain );
