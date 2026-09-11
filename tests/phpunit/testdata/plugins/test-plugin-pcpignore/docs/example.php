<?php

/**
 * Development-only documentation example.
 *
 * This file lives at the plugin root's "docs" directory, which is excluded
 * by the root-anchored "docs/" entry in .pcpignore.
 */

$city = 'Surat';

// This will cause a WordPress.WP.I18n.MissingTranslatorsComment error as it has no translators comment.
sprintf(
	__( 'Your city is %s.', 'test-plugin-pcpignore' ),
	$city
);
