<?php
/**
 * File contains errors related to i18n translation issues.
 */

$city = 'Kathmandu';
sprintf( __( 'Your city is %s.', 'test-plugin-check-errors' ), $city ); // This will trigger WordPress.WP.I18n.MissingTranslatorsComment error.
