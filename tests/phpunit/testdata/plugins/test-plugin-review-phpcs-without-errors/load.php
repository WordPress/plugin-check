<?php
/**
 * File contains no errors related to i18n translation issues.
 */
?>
<?php

ob_start();
    the_author_meta( 'email');
$the_author_email = ob_get_clean();

$var_sanitized = isset( $data['sanitized'] ) ? sanitize_text_field( wp_unslash( $data['sanitized'] ) ) : '';
