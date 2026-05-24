<?php

wp_enqueue_script(
	'foo-script',
	'https://cdn.jsdelivr.net/awesome-script/foo/bar.js',
	array(),
	'1.0.0',
	true
);

wp_enqueue_style(
	'foo-style',
	'https://BOOTSTRAPCDN.COM/awesome-style/foo/bar.css',
	array(),
	'1.0.0'
);

// Safeguard support for PHP 8.0+ named parameters.
wp_register_script(
	ver: 0,
	handle: 'someScript-js',
	src: 'http://googleusercontent.com/awesome-script/foo/bar.js',
);

wp_enqueue_script('foo-script');
