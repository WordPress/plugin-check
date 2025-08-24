<?php

// Test cases for ReviewsFilterSniff.

$regex_pattern = '/reviews\/\?filter=5/'; // Error.

$preg_match = preg_match( '/reviews\/\?filter=5/', $string ); // Error.

$pattern_var = '/reviews\/\?filter=5/'; // Error.

$escaped_pattern = '/reviews\\/\\?filter=5/'; // Error.

$double_quoted = "/reviews\/\?filter=5/"; // Error.

$heredoc = <<<'PATTERN'
/reviews\/\?filter=5/
PATTERN; // Error.

$multiline_string = 'A string containing /reviews\/\?filter=5/ pattern'; // Error.

// Valid patterns that should not trigger errors.
$valid_pattern = '/reviews\/\?filter=6/';
$another_valid = '/reviews\/\?filter=4/';
$different_path = '/other\/\?filter=5/';
