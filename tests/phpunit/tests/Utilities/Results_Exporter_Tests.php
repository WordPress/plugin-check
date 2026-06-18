<?php
/**
 * Tests for the Results_Exporter class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Utilities\Results_Exporter;

class Results_Exporter_Tests extends WP_UnitTestCase {

	public function test_export_ctrf_generates_expected_payload() {
		$errors = array(
			'foo.php' => array(
				10 => array(
					2 => array(
						array(
							'code'     => 'error_code',
							'message'  => 'Error <strong>message</strong>',
							'severity' => 8,
						),
					),
				),
			),
		);

		$warnings = array(
			'bar.php' => array(
				4 => array(
					1 => array(
						array(
							'code'     => 'warning_code',
							'message'  => 'Warning message',
							'severity' => 5,
							'docs'     => 'https://example.com/docs',
						),
					),
				),
			),
		);

		$payload = Results_Exporter::export(
			$errors,
			$warnings,
			Results_Exporter::FORMAT_CTRF,
			array(
				'plugin'             => 'Example Plugin',
				'slug'               => 'example-plugin',
				'timestamp'          => '20260422-123456',
				'timestamp_iso'      => '2026-04-22T12:34:56Z',
				'start_timestamp_ms' => 1713789296000,
				'stop_timestamp_ms'  => 1713789297000,
			)
		);

		$decoded = json_decode( $payload['content'], true );

		$this->assertSame( 'example-plugin-20260422-123456.ctrf.json', $payload['filename'] );
		$this->assertSame( 'application/json', $payload['mime_type'] );
		$this->assertSame( 'CTRF', $decoded['reportFormat'] );
		$this->assertSame( '1.0.0', $decoded['specVersion'] );
		$this->assertSame( 'plugin-check', $decoded['results']['tool']['name'] );
		$this->assertSame( 2, $decoded['results']['summary']['tests'] );
		$this->assertSame( 2, $decoded['results']['summary']['failed'] );
		$this->assertSame( 0, $decoded['results']['summary']['passed'] );
		$this->assertSame( 1713789296000, $decoded['results']['summary']['start'] );
		$this->assertSame( 1713789297000, $decoded['results']['summary']['stop'] );
		$this->assertCount( 2, $decoded['results']['tests'] );
		$this->assertContains(
			'failed',
			array_column( $decoded['results']['tests'], 'status' )
		);
		$this->assertContains(
			'foo.php',
			array_column( $decoded['results']['tests'], 'filePath' )
		);
		$this->assertContains(
			'bar.php',
			array_column( $decoded['results']['tests'], 'filePath' )
		);
	}

	public function test_to_ctrf_json_with_empty_results_still_valid() {
		$content = Results_Exporter::to_ctrf_json(
			array(),
			array(
				'timestamp_iso'      => '2026-04-22T12:34:56Z',
				'start_timestamp_ms' => 1713789296000,
				'stop_timestamp_ms'  => 1713789296000,
			)
		);

		$decoded = json_decode( $content, true );

		$this->assertSame( 'CTRF', $decoded['reportFormat'] );
		$this->assertSame( 0, $decoded['results']['summary']['tests'] );
		$this->assertSame( 0, $decoded['results']['summary']['failed'] );
		$this->assertSame( 1713789296000, $decoded['results']['summary']['start'] );
		$this->assertSame( 1713789296000, $decoded['results']['summary']['stop'] );
		$this->assertCount( 0, $decoded['results']['tests'] );
	}
}
