<?php
/**
 * Fixture for UnclosedObStartSniff.
 *
 * @package PluginCheck
 */

function paired_in_same_function() {
	ob_start();
	echo 'hello';
	ob_end_clean();
}

ob_start();

function multiple_ob_start() {
	ob_start(); // Unpaired.
	ob_start();
	ob_end_clean();
}

$closure = static function() {
	ob_start();
	echo 'hello';
	ob_get_clean();
};

function conditional_close() {
	ob_start();
	if ( true ) {
		ob_end_clean();
	}
}

function hook_paired() {
	ob_start();
	add_action( 'template_redirect', 'hook_paired_callback' );
}

function hook_paired_callback() {
	ob_end_clean();
}
