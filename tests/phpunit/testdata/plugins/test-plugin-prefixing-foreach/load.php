<?php
/**
 * Plugin Name: Test Plugin Prefixing Foreach
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Prefixing check loop variable false positives.
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-prefixing-foreach
 *
 * @package test-plugin-prefixing-foreach
 */

$items = array( 'a', 'b', 'c' );

// foreach: $key and $value are loop-local, must not be flagged.
foreach ( $items as $key => $value ) {
	echo $value;
}

// foreach without key: $value is loop-local, must not be flagged.
foreach ( $items as $value ) {
	echo $value;
}

// for: $i is loop-local, must not be flagged.
for ( $i = 0; $i < 10; $i++ ) {
	echo $i;
}

// Multi-line foreach: opener on previous line, $key/$value on the
// `as` line. Both loop-local, must not be flagged.
foreach (
	$items
	as $key => $value
) {
	echo $value;
}

// Multi-line for: opener on previous line, $i on the init line.
// Loop-local, must not be flagged.
for (
	$i = 0;
	$i < 10;
	$i++
) {
	echo $i;
}

// Multi-line foreach with split `as` and `$var` on different lines.
// Opener on line 1, `as` on line 3, `$key` on line 4. $key is still
// a loop header variable, must not be flagged.
foreach (
	$items
	as
	$key => $value
) {
	echo $value;
}

// Multi-line for with multi-init on different lines. $i and $j both
// declared in init; both loop-local, must not be flagged.
for (
	$i = 0,
	$j = 10;
	$i < $j;
	$i++, $j--
) {
	echo $i;
}

function test_pp_foreach_helper() {
	return true;
}
