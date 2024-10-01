<?php

/**
 * File contains errors related to i18n translation issues.
 */

 $city = 'Surat';

 // This will cause a WordPress.WP.I18n.MissingTranslatorsComment error as it has no translators comment.
 sprintf(
     __( 'Your city is %s.', 'test-plugin-ignore-directories' ),
     $city
 );

 $text_domain = 'test-plugin-ignore-directories';

 // This will cause a WordPress.WP.I18n.NonSingularStringLiteralDomain error as a variable is used for the text-domain.
 esc_html__( 'Hello World!', $text_domain );
