Feature: Test that the WP-CLI command works.

  Scenario: Check a non-existent plugin
    Given a WP install with the Plugin Check plugin

    When I try the WP-CLI command `plugin check foo-bar`
    Then STDERR should contain:
      """
      Plugin with slug foo-bar is not installed.
      """

  Scenario: Check custom single file plugin
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-single.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Single
       * Plugin URI: https://example.com
       * Description: Custom plugin.
       * Version: 0.1.0
       * Author: WordPress Performance Team
       * Author URI: https://make.wordpress.org/performance/
       * License: GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       */

      add_action(
        'init',
        function () {
          $number = mt_rand( 10, 100 );
          echo $number;
        }
      );
      """

    When I run the WP-CLI command `plugin check foo-single.php`
    Then STDOUT should contain:
      """
      mt_rand() is discouraged.
      """
    And STDOUT should not contain:
      """
      no_plugin_readme
      """
    And STDOUT should not contain:
      """
      trademarked_term
      """
    And STDOUT should contain:
      """
      All output should be run through an escaping function
      """

    When I run the WP-CLI command `plugin check foo-single.php --format=csv`
    Then STDOUT should contain:
      """
      line,column,type,code,message
      16,15,ERROR,WordPress.WP.AlternativeFunctions.rand_mt_rand,"mt_rand() is discouraged. Use the far less predictable wp_rand() instead."
      """

    When I run the WP-CLI command `plugin check foo-single.php --format=csv --fields=line,column,code`
    Then STDOUT should contain:
      """
      line,column,code
      16,15,WordPress.WP.AlternativeFunctions.rand_mt_rand
      """

    When I run the WP-CLI command `plugin check foo-single.php --format=json`
    Then STDOUT should contain:
      """
      {"line":16,"column":15,"type":"ERROR","code":"WordPress.WP.AlternativeFunctions.rand_mt_rand","message":"mt_rand() is discouraged. Use the far less predictable wp_rand() instead."}
      """

    When I run the WP-CLI command `plugin check foo-single.php --ignore-errors`
    Then STDOUT should be empty

    When I run the WP-CLI command `plugin check foo-single.php --ignore-warnings`
    Then STDOUT should not be empty

    When I run the WP-CLI command `plugin check foo-single.php --checks=plugin_review_phpcs`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    And STDOUT should not contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """

    When I run the WP-CLI command `plugin check foo-single.php --exclude-checks=late_escaping`
    Then STDOUT should not contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """
    And STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    When I run the WP-CLI command `plugin check foo-single.php --categories=security`
    Then STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    When I run the WP-CLI command `plugin check foo-single.php --checks=plugin_review_phpcs,late_escaping --exclude-checks=late_escaping`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    And STDOUT should not contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """

  Scenario: Exclude directories in plugin check
    Given a WP install with the Plugin Check plugin
    And an empty wp-content/plugins/foo-plugin directory
    And an empty wp-content/plugins/foo-plugin/subdirectory directory
    And a wp-content/plugins/foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

      """
    And a wp-content/plugins/foo-plugin/subdirectory/bar.php file:
      """
      <?php
      $value = 1;
      echo $value;
      """

    When I run the WP-CLI command `plugin check foo-plugin`
    Then STDOUT should contain:
      """
      FILE: subdirectory/bar.php
      """

    When I run the WP-CLI command `plugin check foo-plugin --exclude-directories=subdirectory`
    Then STDOUT should not contain:
      """
      FILE: subdirectory/bar.php
      """

  Scenario: Perform runtime check
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-single.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Single
       * Plugin URI: https://example.com
       * Description: Custom plugin.
       * Version: 0.1.0
       * Author: WordPress Performance Team
       * Author URI: https://make.wordpress.org/performance/
       * License: GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       */

      add_action(
        'init',
        function () {
          $number = mt_rand( 10, 100 );
          echo $number;
        }
      );
      """

    When I run the WP-CLI command `plugin check foo-single.php --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      mt_rand() is discouraged.
      """