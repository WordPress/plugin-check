<?php
/**
 * Initializes the environment for running Plugin Check sniffs tests.
 *
 * @package PluginCheck
 */

$dir_separator = DIRECTORY_SEPARATOR;
$vendor_dir    = dirname( __DIR__ ) . $dir_separator . 'vendor' . $dir_separator;

// Load nikic/php-parser token compatibility before PHPCS so T_PUBLIC_SET etc are int.
// Otherwise PHPCS defines them as strings and nikic/php-parser throws on PHP 8.2+.
// This fix can be removed after minimum PHP and PHPCS/nikic packages are upgraded to versions that resolve the token-type conflict.
$nikic_compat = $vendor_dir . 'nikic' . $dir_separator . 'php-parser' . $dir_separator . 'lib' . $dir_separator . 'PhpParser' . $dir_separator . 'compatibility_tokens.php';
if ( file_exists( $nikic_compat ) ) {
	require_once $nikic_compat;
}

use PHP_CodeSniffer\Util\Standards;

if ( ! defined( 'PHP_CODESNIFFER_IN_TESTS' ) ) {
	define( 'PHP_CODESNIFFER_IN_TESTS', true );
}

// Define the path to the PHPCS directory.
$phpcs_path            = $vendor_dir . 'squizlabs' . $dir_separator . 'php_codesniffer';
$autoload_script_path  = $phpcs_path . $dir_separator . 'autoload.php';
$bootstrap_script_path = $phpcs_path . $dir_separator . 'tests' . $dir_separator . 'bootstrap.php';

// Attempt to load the PHPCS autoloader.
if ( ! file_exists( $autoload_script_path ) || ! file_exists( $bootstrap_script_path ) ) {
	echo 'PHP_CodeSniffer not found. Please run "composer install".' . PHP_EOL;
	exit( 1 );
}

require_once $autoload_script_path;
require_once $bootstrap_script_path; // Support for PHPUnit 6.x+.

/**
 * Configure the environment to ignore tests from other coding standards.
 */
$available_standards = Standards::getInstalledStandards();
$ignored_standards   = array( 'Generic' );

foreach ( $available_standards as $available_standard ) {
	if ( 'PluginCheck' === $available_standard ) {
		continue;
	}

	$ignored_standards[] = $available_standard;
}

$ignore_standards_string = implode( ',', $ignored_standards );

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- This is non-production code.
putenv( "PHPCS_IGNORE_TESTS={$ignore_standards_string}" );

// Cleanup.
unset( $dir_separator, $vendor_dir, $nikic_compat, $phpcs_path, $available_standards, $ignored_standards, $available_standard, $ignore_standards_string );
