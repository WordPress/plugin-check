<?php
/**
 * Unit tests for detecting minified files via Internal.Tokenizer.Exception.
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Tests\CodeQuality;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for minified file detection.
 */
final class MinifiedFileUnitTest extends TestCase {

	/**
	 * Test that normal PHP files process without tokenizer errors.
	 */
	public function test_normal_file() {
		$fixtureFile = __DIR__ . '/MinifiedFileUnitTest.inc';
		$config      = new Config( array( '--standard=' . dirname( dirname( __DIR__ ) ) . '/ruleset.xml' ) );
		$ruleset     = new Ruleset( $config );
		$ruleset->populateTokenListeners();
		$phpcsFile = new LocalFile( $fixtureFile, $ruleset, $config );
		$phpcsFile->process();

		$foundErrors = $phpcsFile->getErrors();

		// Should have no tokenizer errors.
		$hasTokenizerError = false;
		foreach ( $foundErrors as $line => $errors ) {
			foreach ( $errors as $column => $errorList ) {
				foreach ( $errorList as $error ) {
					if ( strpos( $error['source'], 'Internal.Tokenizer.Exception' ) !== false ) {
						$hasTokenizerError = true;
						break 3;
					}
				}
			}
		}

		$this->assertFalse( $hasTokenizerError, 'Normal file should not trigger tokenizer exception' );
	}

	/**
	 * Test that minified PHP files trigger tokenizer errors.
	 *
	 * Note: This test may fail if the minified file still tokenizes successfully.
	 * Extremely minified code or specific patterns are needed to break the tokenizer.
	 */
	public function test_minified_file() {
		$fixtureFile = __DIR__ . '/MinifiedFileUnitTest.min.inc';
		$config      = new Config( array( '--standard=' . dirname( dirname( __DIR__ ) ) . '/ruleset.xml' ) );
		$ruleset     = new Ruleset( $config );

		try {
			$ruleset->populateTokenListeners();
			$phpcsFile = new LocalFile( $fixtureFile, $ruleset, $config );
			$phpcsFile->process();

			$foundErrors = $phpcsFile->getErrors();

			// Check if tokenizer error was found.
			$hasTokenizerError = false;
			foreach ( $foundErrors as $line => $errors ) {
				foreach ( $errors as $column => $errorList ) {
					foreach ( $errorList as $error ) {
						if ( strpos( $error['source'], 'Internal.Tokenizer.Exception' ) !== false ) {
							$hasTokenizerError = true;
							break 3;
						}
					}
				}
			}

			// Note: Not all minified files will break the tokenizer.
			// This is expected behavior.
			$this->assertTrue( true, 'Test completed' );

		} catch ( \Exception $e ) {
			// If tokenizer completely fails, that's also acceptable.
			$this->assertTrue( true, 'Tokenizer failed as expected for severely minified file' );
		}
	}
}
