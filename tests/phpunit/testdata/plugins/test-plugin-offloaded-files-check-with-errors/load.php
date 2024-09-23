<?php
/**
 * The file contains notices for the offloading files check.
 */

wp_enqueue_script(
	'foo-script',
	'https://cdn.jsdelivr.net/awesome-script/foo/bar.js',
	array(),
	'1.0.0',
	true
);

wp_enqueue_style(
	'foo-style',
	'https://bootstrapcdn.com/awesome-style/foo/bar.css',
	array(),
	'1.0.0'
);

?>

<link href="https://bootstrapcdn.com/awesome-style/foo/bar.css" />

<script src="https://cdn.jsdelivr.net/awesome-script/foo/bar.js" />

<img src="https://s.w.org/some-image.png" />

<?php
