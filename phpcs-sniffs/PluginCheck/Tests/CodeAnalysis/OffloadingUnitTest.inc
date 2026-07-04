<link href="https://bootstrapcdn.com/awesome-style/foo/bar.css" />

<script src="https://cdn.jsdelivr.net/awesome-script/foo/bar.js" />

<img src="https://s.w.org/some-image.png" />

<?php

// CDN URL in array assignment (issue #1156 pattern).
$assets[] = array(
	'url' => 'https://cdn.jsdelivr.net/npm/intl-tel-input@21.2.4/build/js/intlTelInput.min.js',
);

// CDN URL in array assignment for CSS.
$assets[] = array(
	'type' => 'css',
	'url'  => 'https://cdn.jsdelivr.net/npm/intl-tel-input@21.2.4/build/css/intlTelInput.css',
);

// CDN URL in variable assignment.
$script_url = 'https://unpkg.com/some-library@1.0.0/dist/lib.js';

// Non-CDN URL should NOT be flagged.
$api_url = 'https://api.example.com/data';

// Non-CDN safe URL should NOT be flagged.
$safe_url = 'https://mysite.com/wp-content/themes/my-theme/style.css';
