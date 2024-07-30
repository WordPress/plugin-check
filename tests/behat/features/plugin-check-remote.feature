Feature: Test that the WP-CLI plugin check command works with remote ZIP url.

  Background:
    Given a WP install with the Plugin Check plugin

  Scenario: Test with invalid remote ZIP url
    When I try the WP-CLI command `plugin check https://example.com/non-existent-file.zip`
    Then STDERR should be:
      """
      Error: Downloading the zip file failed.
      """
    And STDOUT should be empty

  @require-wp-6.4
  Scenario: Test with valid remote ZIP url but error in extracting
    Given a wp-content/mu-plugins/custom-unzip.php file:
      """
      <?php
      /**
       * Plugin Name: Custom Unzip
       */

       add_filter( 'unzip_file', function() {
          return new WP_Error( 'custom_unzip_error', 'Something bad happened while extracting the zip file.' );
       } );
       """

    When I try the WP-CLI command `plugin check https://github.com/ernilambar/foo-bar-wp/releases/latest/download/foo-bar-wp.zip`
    Then STDERR should be:
      """
      Error: Something bad happened while extracting the zip file.
      """
    And STDOUT should be empty

  Scenario: Test with valid ZIP
    When I run the WP-CLI command `plugin check https://github.com/ernilambar/foo-bar-wp/releases/latest/download/foo-bar-wp.zip --fields=code,type --format=csv`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped,ERROR
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR
      """
    And STDOUT should not contain:
      """
      hello.php
      """
    And STDERR should be empty

  Scenario: Test with valid ZIP and extra wporgapi parameter
    When I run the WP-CLI command `plugin check https://github.com/ernilambar/foo-bar-wp/releases/latest/download/foo-bar-wp.zip#wporgapi:https://gist.githubusercontent.com/ernilambar/5eea472890e8f1b599efd1e563866784/raw/27958669515760d8be70d34ff53243c6598a02f6/just-test-file.json --fields=code,type --format=csv`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped,ERROR
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR
      """
    And STDOUT should not contain:
      """
      hello.php
      """
    And STDERR should be empty
    And the {RUN_DIR}/wp-content/uploads/plugin-check/plugin-info.json file should exist
    And the {RUN_DIR}/wp-content/uploads/plugin-check/plugin-info.json file should be:
      """
      {
          "username": "johndoe",
          "first_name": "John",
          "last_name": "Doe"
      }
      """
