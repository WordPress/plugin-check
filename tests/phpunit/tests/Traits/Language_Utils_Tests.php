<?php
/**
 * Tests for the Language_Utils trait.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Traits\Language_Utils;

/**
 * Test class for Language_Utils trait.
 */
class Language_Utils_Tests extends WP_UnitTestCase {

	use Language_Utils;

	public function test_is_on_official_language_with_english() {
		$content = 'This is a test of the language detection system. It should detect this as English.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_non_english() {
		$content = 'このプラグインは、WordPress プラグインのリードミー ファイルを検証し、一般的なエラーを特定して修正するのに役立ちます。';
		$this->assertFalse( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_spanish() {
		$content = 'Este plugin es una herramienta para verificar los archivos readme de plugins de WordPress y detectar errores comunes.';
		$this->assertFalse( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_short_text() {
		// Short text should return true (benefit of doubt).
		$content = 'Short text';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_code_snippets() {
		$content = 'This plugin helps you validate readme files. Use `function test() { return true; }` in your code.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_urls() {
		$content = 'For more information, visit https://wordpress.org or https://developer.wordpress.org for documentation.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_email_addresses() {
		$content = 'Contact us at support@wordpress.org or plugins@wordpress.org for help with your plugin.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_html_tags() {
		$content = '<p>This plugin provides <strong>awesome features</strong> for your WordPress site.</p>';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_mixed_technical_content() {
		$content = 'The plugin uses API calls to REST endpoints with JSON responses. Configure AJAX handlers and HTTP methods properly.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_code_blocks() {
		$content = 'Here is an example: ```php function example() { return "test"; } ``` This should work fine.';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_empty_string() {
		$content = '';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_whitespace_only() {
		$content = '   ';
		$this->assertTrue( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_french() {
		$content = 'Ce plugin est un outil pour vérifier les fichiers readme des plugins WordPress et détecter les erreurs courantes dans la documentation.';
		$this->assertFalse( $this->is_on_official_language( $content ) );
	}

	public function test_is_on_official_language_with_german() {
		$content = 'Dieses Plugin it in Werkzeug zur Überprüfung der Readme-Dateien von WordPress-Plugins und zur Erkennung häufiger Fehler in der Dokumentation.';
		$this->assertFalse( $this->is_on_official_language( $content ) );
	}
}
