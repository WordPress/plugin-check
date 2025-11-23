<?php
/**
 * Tests for the Plugin_Review_PHPCS_Check class.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Checker\Check_Context;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Plugin_Repo\Plugin_Review_PHPCS_Check;

class Plugin_Review_PHPCS_Check_Tests extends WP_UnitTestCase {

	public function test_run_with_errors() {
		$plugin_review_phpcs_check = new Plugin_Review_PHPCS_Check();
		$check_context             = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-review-phpcs-errors/load.php' );
		$check_result              = new Check_Result( $check_context );

		$plugin_review_phpcs_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'load.php', $errors );
		$this->assertArrayHasKey( 'file-with-bom.php', $errors );
		$this->assertNotEmpty( $warnings );
		$this->assertArrayHasKey( 'load.php', $warnings );

		// Check for Generic.PHP.DisallowShortOpenTag.Found error on Line no 6 and column no at 1.
		$this->assertArrayHasKey( 6, $errors['load.php'] );
		$this->assertArrayHasKey( 1, $errors['load.php'][6] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][6][1][0] );
		$this->assertEquals( 'Generic.PHP.DisallowShortOpenTag.Found', $errors['load.php'][6][1][0]['code'] );

		// Check for PluginCheck.CodeAnalysis.Heredoc.NotAllowed error on Line no 28 and column no at 8.
		$this->assertArrayHasKey( 28, $errors['load.php'] );
		$this->assertArrayHasKey( 8, $errors['load.php'][28] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][28][8][0] );
		$this->assertEquals( 'PluginCheck.CodeAnalysis.Heredoc.NotAllowed', $errors['load.php'][28][8][0]['code'] );

		// Check for WordPress.WP.DeprecatedFunctions.the_author_emailFound error on Line no 12 and column no at 5.
		$this->assertArrayHasKey( 12, $errors['load.php'] );
		$this->assertArrayHasKey( 5, $errors['load.php'][12] );
		$this->assertArrayHasKey( 'code', $errors['load.php'][12][5][0] );
		$this->assertEquals( 'WordPress.WP.DeprecatedFunctions.the_author_emailFound', $errors['load.php'][12][5][0]['code'] );

		// Check for PluginCheck.CodeAnalysis.RequiredFunctionParameters.parse_str_resultMissing error on Line no 34 and column no at 1.
		$this->assertSame( 'PluginCheck.CodeAnalysis.RequiredFunctionParameters.parse_str_resultMissing', $errors['load.php'][34][1][0]['code'] );

		// Check for Generic.Files.ByteOrderMark.Found error on Line no 1 and column no 1.
		$this->assertSame( 'Generic.Files.ByteOrderMark.Found', $errors['file-with-bom.php'][1][1][0]['code'] );

		// There should not be WordPress.WP.AlternativeFunctions.json_encode_json_encode error on Line no 36 and column no at 18.
		$this->assertCount( 0, wp_list_filter( $errors['load.php'][36][18], array( 'code' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode' ) ) );

		// There should not be WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents error on Line no 38 and column no 1.
		$this->assertCount( 0, wp_list_filter( $errors['load.php'][38][1], array( 'code' => 'WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents' ) ) );

		// There should not be WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents error on Line no 39 and column no 1.
		$this->assertCount( 0, wp_list_filter( $errors['load.php'][39][1], array( 'code' => 'WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents' ) ) );

		// Check for PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound error on Line no 41 and column no at 1.
		$this->assertSame( 'PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound', $errors['load.php'][41][1][0]['code'] );

		// Check for existing forbidden functions.
		$forbidden_found = 'Generic.PHP.ForbiddenFunctions.Found';

		// Check for Generic.PHP.ForbiddenFunctions.create_functionFound error on Line no 44 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][44][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.evalFound error on Line no 45 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][45][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.move_uploaded_fileFound error on Line no 46 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][46][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.passthruFound error on Line no 47 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][47][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.proc_openFound error on Line no 48 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][48][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.str_rot13Found error on Line no 49 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][49][1][0]['code'] );

		// Check for WordPress.Security.ValidatedSanitizedInput warnings on Line no 15 and column no at 27.
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][15][27], array( 'code' => 'WordPress.Security.ValidatedSanitizedInput.InputNotValidated' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][15][27], array( 'code' => 'WordPress.Security.ValidatedSanitizedInput.MissingUnslash' ) ) );
		$this->assertCount( 1, wp_list_filter( $warnings['load.php'][15][27], array( 'code' => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized' ) ) );

		// Check for Squiz.PHP.DiscouragedFunctions.Discouraged warning on Line no 17 and column no at 1.
		$this->assertSame( 'Squiz.PHP.DiscouragedFunctions.Discouraged', $warnings['load.php'][17][1][0]['code'] );

		// Check for Squiz.PHP.DiscouragedFunctions.Discouraged warning on Line no 18 and column no at 1.
		$this->assertSame( 'Squiz.PHP.DiscouragedFunctions.Discouraged', $warnings['load.php'][18][1][0]['code'] );

		// Check for Squiz.PHP.DiscouragedFunctions.Discouraged warning on Line no 19 and column no at 1.
		$this->assertSame( 'Squiz.PHP.DiscouragedFunctions.Discouraged', $warnings['load.php'][19][1][0]['code'] );

		// Check for Squiz.PHP.DiscouragedFunctions.Discouraged warning on Line no 20 and column no at 1.
		$this->assertSame( 'Squiz.PHP.DiscouragedFunctions.Discouraged', $warnings['load.php'][20][1][0]['code'] );

		// Check for WordPress.PHP.DevelopmentFunctions.error_log_var_dump warning on Line no 22 and column no at 1.
		$this->assertSame( 'WordPress.PHP.DevelopmentFunctions.error_log_var_dump', $warnings['load.php'][22][1][0]['code'] );

		// Check for WordPress.PHP.DevelopmentFunctions.error_log_error_log warning on Line no 23 and column no at 1.
		$this->assertSame( 'WordPress.PHP.DevelopmentFunctions.error_log_error_log', $warnings['load.php'][23][1][0]['code'] );

		// Check for WordPress.WP.DiscouragedFunctions.query_posts_query_posts warning on Line no 25 and column no at 1.
		$this->assertSame( 'WordPress.WP.DiscouragedFunctions.query_posts_query_posts', $warnings['load.php'][25][1][0]['code'] );

		// Check for WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query warning on Line no 26 and column no at 1.
		$this->assertSame( 'WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query', $warnings['load.php'][26][1][0]['code'] );

		// Check for new forbidden functions
		// Check for Generic.PHP.ForbiddenFunctions._cleanup_header_commentFound error on Line no 52 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][52][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions._get_plugin_data_markup_translateFound error on Line no 53 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][53][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions._transition_post_statusFound error on Line no 54 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][54][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions._wp_post_revision_fieldsFound error on Line no 55 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][55][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.do_shortcode_tagFound error on Line no 56 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][56][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.get_post_type_labelsFound error on Line no 57 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][57][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.wp_get_sidebars_widgetsFound error on Line no 58 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][58][1][0]['code'] );

		// Check for Generic.PHP.ForbiddenFunctions.wp_get_widget_defaultsFound error on Line no 59 and column no at 1.
		$this->assertSame( $forbidden_found, $errors['load.php'][59][1][0]['code'] );

		// Check for PluginCheck.CodeAnalysis.ShortURL.Found warning on Line no 69 and column no at 14.
		$this->assertArrayHasKey( 69, $warnings['load.php'] );
		$this->assertArrayHasKey( 14, $warnings['load.php'][69] );
		$this->assertArrayHasKey( 'code', $warnings['load.php'][69][14][0] );
		$this->assertEquals( 'PluginCheck.CodeAnalysis.ShortURL.Found', $warnings['load.php'][69][14][0]['code'] );
		$this->assertSame( 6, $warnings['load.php'][69][14][0]['severity'] );
	}

	public function test_run_without_errors() {
		$plugin_review_phpcs_check = new Plugin_Review_PHPCS_Check();
		$check_context             = new Check_Context( UNIT_TESTS_PLUGIN_DIR . 'test-plugin-review-phpcs-without-errors/load.php' );
		$check_result              = new Check_Result( $check_context );

		$plugin_review_phpcs_check->run( $check_result );

		$errors   = $check_result->get_errors();
		$warnings = $check_result->get_warnings();

		$this->assertEmpty( $errors );
		$this->assertEmpty( $warnings );
		$this->assertEquals( 0, $check_result->get_error_count() );
		$this->assertEquals( 0, $check_result->get_warning_count() );
	}
}
