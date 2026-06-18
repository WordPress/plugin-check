<?php
/**
 * Plugin Name: Test Plugin Unclosed Ob Start
 */

// 1. Paired in the same function → no issue.
function paired_in_same_function() {
	ob_start();
	echo 'hello';
	ob_end_clean();
}

// 2. ob_start() at the top of the file with no closing call → issue.
ob_start();

// 3. Multiple ob_start() in the same scope, only one closed → issue on the unpaired one (line 18).
function multiple_ob_start() {
	ob_start(); // this one is unpaired
	ob_start();
	ob_end_clean();
}

// 4. ob_start() inside a closure, closed inside the same closure → no issue.
$closure = function() {
	ob_start();
	echo 'hello';
	ob_get_clean();
};

// 5. ob_start() inside a function, closed only conditionally (if) → flagged as warning (line 32).
function conditional_close() {
	ob_start();
	if ( true ) {
		ob_end_clean();
	}
}

// 6. Fully-qualified \ob_start() paired in the same function → no issue (PHP 8 FQN handling).
function fqn_paired() {
	\ob_start();
	echo 'hello';
	\ob_end_clean();
}

// 7. ob_start() closed via an immediately adjacent hook callback → no issue (statically obvious).
function hook_paired() {
	ob_start();
	add_action( 'template_redirect', 'test_plugin_unclosed_ob_start_close' );
}

function test_plugin_unclosed_ob_start_close() {
	ob_end_clean();
}
