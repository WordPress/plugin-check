<?php
/**
 * File contains errors related to i18n translation issues.
 */

$text_domain = 'test-plugin-check-errors';
esc_html__( 'Hello World!', $text_domain ); // This will trigger WordPress.WP.I18n.NonSingularStringLiteralDomain error.
