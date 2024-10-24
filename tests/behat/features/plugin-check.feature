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
    Then STDOUT should contain:
      """
      Success: Checks complete. No errors found.
      """

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

  Scenario: Check plugin with special chars in plugin name
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/johns-post-counter/johns-post-counter.php file:
      """
      <?php
      /**
       * Plugin Name: John's — Post & Counter
       */

      """
    And a wp-content/plugins/johns-post-counter/readme.txt file:
      """
      === John's — Post & Counter ===
      """

    When I run the WP-CLI command `plugin check johns-post-counter --format=csv --fields=code,type`
    Then STDOUT should not contain:
      """
      mismatched_plugin_name,WARNING
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
      no_plugin_readme,ERROR
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

  Scenario: Check a plugin from external location with custom plugin slug
    Given a WP install with the Plugin Check plugin
    And an empty external-folder/pxzvccv345nhg directory
    And a external-folder/pxzvccv345nhg/foo-sample.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Sample
       * Plugin URI:  https://foo-sample.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-sample
       */

      """

    When I run the WP-CLI command `plugin check {RUN_DIR}/external-folder/pxzvccv345nhg/ --format=csv --fields=code,type`
    Then STDERR should be empty
    And STDOUT should contain:
      """
      textdomain_mismatch,WARNING
      """
    And STDOUT should contain:
      """
      no_plugin_readme,ERROR
      """

    When I run the WP-CLI command `plugin check {RUN_DIR}/external-folder/pxzvccv345nhg/ --format=csv --fields=code,type --slug=foo-sample`
    Then STDERR should be empty
    And STDOUT should not contain:
      """
      textdomain_mismatch,WARNING
      """
    And STDOUT should contain:
      """
      no_plugin_readme,ERROR
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

  Scenario: Check custom single file plugin that has no errors or warnings
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
          echo esc_html( 'this is a test.' );
        }
      );
      """

    When I run the WP-CLI command `plugin check foo-single.php`
    Then STDOUT should contain:
	  """
	  Success: Checks complete. No errors found.
	  """
