<?php

/**
 * @group Checks
 * @group CodeObfuscation
 */
class Test_Code_Obfuscation extends PluginCheck_TestCase {
	/**
	 * @dataProvider data_obfuscation
	 */
	public function test_obfuscation( $file_structure ) {
		$results = $this->run_against_virtual_files( $file_structure );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'obfuscated_code_detected', 'needle' => 'Zend Guard' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'obfuscated_code_detected', 'needle' => 'Source Gardian' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'obfuscated_code_detected', 'needle' => 'ionCube' ] );
	}

	public function data_obfuscation() {
		return [
			[
				[
					'plugin.php'         => "// Silence is golden.",
					'zend-encoded.php'   => '<?php @Zend; die();',
					'sourcegaurdian.php' => "if ( function_exists('sg_load') ) {}",
					'ioncube.php'        => "if(!extension_loaded('ionCube Loader'))",
				]
			],
			[
				[
					'plugin.php'         => "// Silence is golden.",
					'zend-encoded.php'   => '// This file was encoded by a special program.',
					'sourceguardian.php' => '// This file is protected by sourceguardian.com',
					'ioncube.php'        => 'echo "To run this plugin, please install ionCube',
				]
			]
		];
	}
}
