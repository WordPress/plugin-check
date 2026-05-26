Feature: Test that WP_PLUGIN_CHECK_BOOTSTRAP_FILE is loaded in the WP-CLI path.

  Scenario: Bootstrap file is required when the constant points to an existing file
    Given a WP install with the Plugin Check plugin
    And a wp-content/pcp-bootstrap.php file:
      """
      <?php
      WP_CLI::log( 'PCP bootstrap loaded' );
      """
    And a wp-content/pcp-config.php file:
      """
      <?php
      define( 'WP_PLUGIN_CHECK_BOOTSTRAP_FILE', __DIR__ . '/pcp-bootstrap.php' );
      """

    When I run the WP-CLI command `plugin list --require=./wp-content/pcp-config.php --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      PCP bootstrap loaded
      """

  Scenario: Missing bootstrap file surfaces a warning and does not halt execution
    Given a WP install with the Plugin Check plugin
    And a wp-content/pcp-config.php file:
      """
      <?php
      define( 'WP_PLUGIN_CHECK_BOOTSTRAP_FILE', __DIR__ . '/pcp-bootstrap-missing.php' );
      """

    # `try` rather than `run`: the warning is the test point and `run` rejects any non-empty STDERR.
    When I try the WP-CLI command `plugin list --require=./wp-content/pcp-config.php --require=./wp-content/plugins/plugin-check/cli.php`
    Then the return code should be 0
    And STDERR should contain:
      """
      WP_PLUGIN_CHECK_BOOTSTRAP_FILE
      """
    And STDERR should contain:
      """
      but the file does not exist
      """

  Scenario: Undefined constant is a no-op
    Given a WP install with the Plugin Check plugin

    When I run the WP-CLI command `plugin list --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDERR should not contain:
      """
      WP_PLUGIN_CHECK_BOOTSTRAP_FILE
      """

  Scenario: Bootstrap file can register listeners that fire on setup/cleanup hooks
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
    And a wp-content/pcp-bootstrap.php file:
      """
      <?php
      // PCP loads this file from cli.php before wp-settings.php, so add_action()
      // is not defined yet. Defer registration to after_wp_config_load and
      // require plugin.php manually since wp-settings.php has not run.
      WP_CLI::add_hook(
          'after_wp_config_load',
          static function () {
              if ( ! function_exists( 'add_action' ) ) {
                  require_once ABSPATH . 'wp-includes/plugin.php';
              }
              add_action(
                  'wp_plugin_check_before_runtime_setup',
                  static function () {
                      WP_CLI::log( 'PCP before_setup fired' );
                  }
              );
              add_action(
                  'wp_plugin_check_after_runtime_cleanup',
                  static function () {
                      WP_CLI::log( 'PCP after_cleanup fired' );
                  }
              );
          }
      );
      """
    And a wp-content/pcp-config.php file:
      """
      <?php
      define( 'WP_PLUGIN_CHECK_BOOTSTRAP_FILE', __DIR__ . '/pcp-bootstrap.php' );
      """

    When I run the WP-CLI command `plugin check foo-single.php --require=./wp-content/pcp-config.php --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDOUT should contain:
      """
      PCP before_setup fired
      """
    And STDOUT should contain:
      """
      PCP after_cleanup fired
      """
