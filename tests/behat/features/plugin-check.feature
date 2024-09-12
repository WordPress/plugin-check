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
       * Plugin URI: https://foo-single.com
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
      line,column,type,code,message,docs
      16,15,ERROR,WordPress.WP.AlternativeFunctions.rand_mt_rand,"mt_rand() is discouraged. Use the far less predictable wp_rand() instead.",
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
      {"line":16,"column":15,"type":"ERROR","code":"WordPress.WP.AlternativeFunctions.rand_mt_rand","message":"mt_rand() is discouraged. Use the far less predictable wp_rand() instead.","docs":""}
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
       * Plugin URI:  https://foo-plugin.com
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

  Scenario: Exclude files in plugin check
    Given a WP install with the Plugin Check plugin
    And an empty wp-content/plugins/foo-plugin directory
    And an empty wp-content/plugins/foo-plugin/subdirectory directory
    And a wp-content/plugins/foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://foo-plugin.com
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
    And a wp-content/plugins/foo-plugin/bar.php file:
      """
      <?php
      $value = 1;
      echo $value;
      """
    And a wp-content/plugins/foo-plugin/foobar.php file:
      """
      <?php
      $value = 1;
      echo $value;
      """
    And a wp-content/plugins/foo-plugin/subdirectory/error.php file:
      """
      <?php
      $value = 1;
      echo $value;
      """
    When I run the WP-CLI command `plugin check foo-plugin`
    Then STDOUT should contain:
      """
      FILE: bar.php
      """
    And STDOUT should contain:
      """
      FILE: foobar.php
      """

    When I run the WP-CLI command `plugin check foo-plugin --exclude-files=bar.php`
    Then STDOUT should contain:
      """
      FILE: foobar.php
      """
    Then STDOUT should not contain:
      """
      FILE: bar.php
      """

    When I run the WP-CLI command `plugin check foo-plugin --exclude-files=subdirectory/error.php`
    Then STDOUT should not contain:
      """
      FILE: subdirectory/error.php
      """

  Scenario: Perform runtime check
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-single.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Single
       * Plugin URI: https://foo-single.com
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

  Scenario: Perform runtime check for multi-file plugin
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-sample/foo-sample.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Sample
       * Plugin URI: https://foo-sample.com
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
          echo absint( $number );
        }
      );

      add_action(
        'wp_enqueue_scripts',
        function() {
          wp_enqueue_style(
            'style',
            plugin_dir_url( __FILE__ ) . 'style.css',
            array(),
            '1.0'
          );
        }
      );

      """
    And a wp-content/plugins/foo-sample/style.css file:
      """
      a {
        text-decoration: underline;
      }
      """

    When I run the WP-CLI command `plugin activate foo-sample`
    And I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      EnqueuedStylesScope,WARNING
      """
    And STDOUT should contain:
      """
      no_plugin_readme,WARNING
      """

  Scenario: Check a plugin from external location
    Given a WP install with the Plugin Check plugin
    And an empty external-folder/foo-plugin directory
    And a external-folder/foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://foo-plugin.com
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

    When I run the WP-CLI command `plugin check {RUN_DIR}/external-folder/foo-plugin`
    Then STDERR should be empty
    And STDOUT should contain:
      """
      trademarked_term
      """
    And STDOUT should contain:
      """
      no_plugin_readme
      """

  Scenario: Check a plugin from external location but with invalid plugin
    Given a WP install with the Plugin Check plugin
    And an empty external-folder/foo-plugin directory
    And a external-folder/foo-plugin/foo-plugin.php file:
      """
      <?php
      // Not a valid plugin.

      """

    When I try the WP-CLI command `plugin check {RUN_DIR}/non-existent-external-folder/foo-plugin`
    Then STDOUT should be empty
    And STDERR should not contain:
      """
      no_plugin_readme
      """
    And STDERR should contain:
      """
      Invalid plugin slug
      """

    When I try the WP-CLI command `plugin check {RUN_DIR}/external-folder/foo-plugin`
    Then STDOUT should be empty
    And STDERR should not contain:
      """
      no_plugin_readme
      """
    And STDERR should contain:
      """
      Invalid plugin slug
      """

  Scenario: Check a plugin and display output in "wporg" format
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/foo-bar-wp/foo-bar-wp.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Bar WP
       * Plugin URI: https://example.com
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
        }
      );
      """
    And a wp-content/plugins/foo-bar-wp/readme.txt file:
      """
      Contributors: wordpressdotorg
      Tags: foo, bar, tag1
      Stable tag: 0.1.0
      License: GPLv2 or later
      License URI: http://www.gnu.org/licenses/gpl-2.0.html

      Short description will be here.

      == Description ==

      Long description will be here. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

      == Upgrade Notice ==

      Long upgrade notice here. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --format=wporg`
    Then STDOUT should be:
      """
      {"errors":[{"message":"`ALLOW_UNFILTERED_UPLOADS` is not permitted. Setting this constant to true will allow the user to upload any type of file (including PHP and other executables), creating serious potential security risks.","code":"allow_unfiltered_uploads_detected","link":null,"docs":"https:\/\/developer.wordpress.org\/plugins\/wordpress-org\/common-issues\/#files-unfiltered-uploads","severity":7,"type":"ERROR","line":0,"column":0},{"message":"Plugin name header in your readme is missing or invalid. Please update your readme with a valid plugin name header. Eg: \"=== Example Name ===\"","code":"invalid_plugin_name","link":null,"docs":"https:\/\/developer.wordpress.org\/plugins\/wordpress-org\/common-issues\/#incomplete-readme","severity":9,"type":"ERROR","line":0,"column":0}],"warnings":[{"message":"The readme appears to contain default text. This means your readme has to have headers as well as a proper description and documentation as to how it works and how one can use it.","code":"default_readme_text","link":null,"docs":"https:\/\/developer.wordpress.org\/plugins\/wordpress-org\/common-issues\/#incomplete-readme","severity":7,"type":"WARNING","line":0,"column":0},{"message":"The upgrade notice exceeds the limit of 300 characters.","code":"upgrade_notice_limit","link":null,"docs":"","severity":5,"type":"WARNING","line":0,"column":0}]}
      """

    When I run the WP-CLI command `plugin check foo-bar-wp --error-severity=8 --format=wporg`
    Then STDOUT should be:
      """
      {"errors":[{"message":"Plugin name header in your readme is missing or invalid. Please update your readme with a valid plugin name header. Eg: \"=== Example Name ===\"","code":"invalid_plugin_name","link":null,"docs":"https:\/\/developer.wordpress.org\/plugins\/wordpress-org\/common-issues\/#incomplete-readme","severity":9,"type":"ERROR","line":0,"column":0}],"warnings":[{"message":"The readme appears to contain default text. This means your readme has to have headers as well as a proper description and documentation as to how it works and how one can use it.","code":"default_readme_text","link":null,"docs":"https:\/\/developer.wordpress.org\/plugins\/wordpress-org\/common-issues\/#incomplete-readme","severity":7,"type":"WARNING","line":0,"column":0},{"message":"The upgrade notice exceeds the limit of 300 characters.","code":"upgrade_notice_limit","link":null,"docs":"","severity":5,"type":"WARNING","line":0,"column":0}]}
      """

  Scenario: Check a plugin with static checks from an add-on
    Given a WP install with the Plugin Check plugin
    And a Plugin Check add-on being installed

    And a wp-content/plugins/foo-sample/foo-sample.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Sample
       * Plugin URI: https://example.com
       * Description: Sample plugin.
       * Version: 0.1.0
       * Author: WordPress Performance Team
       * Author URI: https://make.wordpress.org/performance/
       * License: GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       */

      $text = 'I am bad'; // This should trigger the error.
      """
    And I run the WP-CLI command `plugin activate foo-sample`

    # The two checks from pcp-addon should be available.
    When I run the WP-CLI command `plugin list-checks --fields=slug,category,stability --format=csv`
    Then STDOUT should contain:
      """
      example_static,new_category,stable
      """
    And STDOUT should contain:
      """
      example_runtime,new_category,stable
      """

    # The new check category should therefore also be available.
    When I run the WP-CLI command `plugin list-check-categories --fields=slug,name --format=csv`
    Then STDOUT should contain:
      """
      new_category,"New Category"
      """

    # Running static checks, including the one from pcp-addon
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv`
    Then STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """

    # Same again, but after filtering only to the new categories from pcp-addon
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --categories=new_category`
    Then STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """

    # Running only the check from pcp-addon
    When I run the WP-CLI command `plugin check foo-sample --checks=example_static --fields=code,type --format=csv`
    Then STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """

  Scenario: Check a plugin with runtime checks from an add-on
    Given a WP install with the Plugin Check plugin
    And a Plugin Check add-on being installed

    And a wp-content/plugins/foo-dependency/foo-dependency.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Dependency
       * Plugin URI: https://example.com
       * Description: Sample plugin.
       * Version: 0.1.0
       * Author: WordPress Performance Team
       * Author URI: https://make.wordpress.org/performance/
       * License: GPL-2.0+
       * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
       */
      """
    And a wp-content/plugins/foo-sample/foo-sample.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Sample
       * Plugin URI: https://example.com
       * Description: Sample plugin.
       * Version: 0.1.0
       * Author: WordPress Performance Team
       * Author URI: https://make.wordpress.org/performance/
       * License: GPL-2.0+
       * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
       * Requires Plugins: foo-dependency
       */

      // This should trigger the error.
      add_action(
        'wp_enqueue_scripts',
        function() {
          wp_enqueue_script( 'test', plugin_dir_url( __FILE__ ) . 'test.js', array(), '1.0' );
        }
      );
      """
    And I run the WP-CLI command `plugin activate foo-dependency foo-sample`

    # Running runtime checks, including the one from pcp-addon
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      Setting up runtime environment.
      """
    And STDOUT should contain:
      """
      Cleaning up runtime environment.
      """
    And STDOUT should contain:
      """
      WordPress.WP.EnqueuedResourceParameters.NotInFooter,WARNING
      """
# This doesn't currently work, because we are not actually loading any other plugins, including pcp-addon.
#    And STDOUT should contain:
#      """
#      ExampleRuntimeCheck.ForbiddenScript,WARNING
#      """

    # Same again, to verify object-cache.php was properly cleared again
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      Setting up runtime environment.
      """
    And STDOUT should contain:
      """
      Cleaning up runtime environment.
      """
    And STDOUT should contain:
      """
      WordPress.WP.EnqueuedResourceParameters.NotInFooter,WARNING
      """

    # This doesn't currently work, because we are not actually loading any other plugins, including pcp-addon.
#    And STDOUT should contain:
#      """
#      ExampleRuntimeCheck.ForbiddenScript,WARNING
#      """

    # This doesn't currently work.
    # Run one runtime check from PCP and one from pcp-addon.
#    When I run the WP-CLI command `plugin check foo-sample --checks=non_blocking_scripts,example_runtime --fields=code,type --format=csv --require=./wp-content/plugins/plugin-check/cli.php`
#    Then STDOUT should contain:
#      """
#      ExampleRuntimeCheck.ForbiddenScript,WARNING
#      """

    # This doesn't currently work, because we are not actually loading any other plugins, including pcp-addon.
    # Run only the runtime check from pcp-addon, no others
#    When I run the WP-CLI command `plugin check foo-sample --checks=example_runtime --fields=code,type --format=csv --require=./wp-content/plugins/plugin-check/cli.php`
#    Then STDOUT should contain:
#      """
#      ExampleRuntimeCheck.ForbiddenScript,WARNING
#      """
