<?php

/**
 * @group Checks
 * @group File
 */
class Test_File_Checks extends PluginCheck_TestCase {
	public function test_filetypes() {
		$results = $this->run_against_virtual_files( [
			'plugin.php'     => "// Silence is golden.",
			'plugin.zip'     => 'ZIP CONTENTS',
			'plugin.phar'    => 'PHAR CONTENTS',
			'.svn/.hidden'   => 'SVN Empty file',
			'vendor/.hidden' => 'Ignored Empty file',
		] );

		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'compressed_files', 'needle' => '.zip' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'error', 'code' => 'phar_detected' ] );
		$this->assertHasErrorType( $results, [ 'type' => 'warning', 'code' => 'hidden_files', 'needle' => '.hidden' ] );
		$this->assertHasErrorType( $results, [ 'code' => 'vcs_present', 'needle' => '.svn' ] ); // VCS may be notice or error depending on environment.

		// Check the hidden file in vendor is skipped.
		$this->assertNotHasErrorType( $results, [ 'code' => 'hidden_files', 'needle' => 'vendor/' ] );
	}
}
