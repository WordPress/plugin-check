Feature: Test that the severity level in plugin check works.

  Scenario: Check a plugin different severity levels
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-bar-wp/foo-bar-wp.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Bar WP
       * Plugin URI: https://foo-bar.com
       * Description: Custom plugin.
       * Version: 0.1.0
       * Requires at least: 6.0
       * Requires PHP: 7.0
       * Author: WordPress Team
       * Author URI: https://make.wordpress.org/plugins/
       * License: GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-bar-wp
       */

      // This file was encoded by.
      define( 'ALLOW_UNFILTERED_UPLOADS', true );

      add_action(
        'init',
        function () {
          $number = mt_rand( 10, 100 );
          echo $number;

          // By default I am not that bad. Only addon tells me such.
          echo 'I am bad';

          $qargs = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'no_found_rows'  => true,
          );
        }
      );
      """
    And a wp-content/plugins/foo-bar-wp/readme.txt file:
      """
      === Foo Bar WP ===

      Contributors: wordpressdotorg
      Tags: foo, bar, tag1
      Tested up to: 6.5
      Stable tag: 0.1.0
      License: GPLv2 or later
      License URI: http://www.gnu.org/licenses/gpl-2.0.html

      Short description will be here.

      == Description ==

      Long description will be here. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

      == Upgrade Notice ==

      Long upgrade notice here. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      obfuscated_code_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR,5
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR,7
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """
    And STDOUT should contain:
      """
      upgrade_notice_limit,WARNING,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --severity=7`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      obfuscated_code_detected,ERROR,7
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR,5
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR,7
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """
    And STDOUT should not contain:
      """
      upgrade_notice_limit,WARNING,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --severity=6`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      obfuscated_code_detected,ERROR,7
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR,5
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR,7
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """
    And STDOUT should not contain:
      """
      upgrade_notice_limit,WARNING,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --error-severity=6`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      obfuscated_code_detected,ERROR,7
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR,5
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR,7
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """
    And STDOUT should contain:
      """
      upgrade_notice_limit,WARNING,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --warning-severity=7`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      obfuscated_code_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR,5
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR,7
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """
    And STDOUT should not contain:
      """
      upgrade_notice_limit,WARNING,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --error-severity=7 --include-low-severity-errors`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR_LOW_SEVERITY,5
      """
    And STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped,ERROR_LOW_SEVERITY,5
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --warning-severity=7 --include-low-severity-warnings`
    Then STDOUT should contain:
      """
      allow_unfiltered_uploads_detected,ERROR,7
      """
    And STDOUT should contain:
      """
      upgrade_notice_limit,WARNING_LOW_SEVERITY,5
      """
    And STDOUT should contain:
      """
      default_readme_text,ERROR,7
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=csv --fields=code,type,severity --severity=10`
    Then STDOUT should be empty
