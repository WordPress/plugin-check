<?php

/**
 * A distributed file that happens to live under a directory named "docs",
 * but not at the plugin root. It must not be excluded by a root-anchored
 * "docs/" entry in .pcpignore, unlike the top-level docs/example.php file.
 */

$city = 'Surat';

// This will cause a WordPress.WP.I18n.MissingTranslatorsComment error as it has no translators comment.
sprintf(
	__( 'Your city is %s.', 'test-plugin-pcpignore' ),
	$city
);
