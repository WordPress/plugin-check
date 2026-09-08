Feature: Test that the `wp_plugin_check_check_result` filter can suppress individual findings.

  Scenario: Bootstrap-registered filter suppresses a specific finding by code
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
      // PCP loads this file from cli.php before wp-settings.php, so add_filter()
      // is not defined yet. Defer registration to after_wp_config_load.
      WP_CLI::add_hook(
          'after_wp_config_load',
          static function () {
              if ( ! function_exists( 'add_filter' ) ) {
                  require_once ABSPATH . 'wp-includes/plugin.php';
              }
              add_filter(
                  'wp_plugin_check_check_result',
                  static function ( $data ) {
                      if ( is_array( $data )
                           && 'WordPress.WP.AlternativeFunctions.rand_mt_rand' === ( $data['code'] ?? '' )
                      ) {
                          return null;
                      }
                      return $data;
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
    Then STDOUT should not contain:
      """
      mt_rand() is discouraged.
      """

  Scenario: Filter can mutate an entry's message in place
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
      // PCP loads this file from cli.php before wp-settings.php, so add_filter()
      // is not defined yet. Defer registration to after_wp_config_load.
      WP_CLI::add_hook(
          'after_wp_config_load',
          static function () {
              if ( ! function_exists( 'add_filter' ) ) {
                  require_once ABSPATH . 'wp-includes/plugin.php';
              }
              add_filter(
                  'wp_plugin_check_check_result',
                  static function ( $data ) {
                      if ( is_array( $data )
                           && 'WordPress.WP.AlternativeFunctions.rand_mt_rand' === ( $data['code'] ?? '' )
                      ) {
                          $data['message'] = 'PCP-RESULT-FILTER-REWROTE-THIS-MESSAGE';
                      }
                      return $data;
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
      PCP-RESULT-FILTER-REWROTE-THIS-MESSAGE
      """
    And STDOUT should not contain:
      """
      mt_rand() is discouraged.
      """

  Scenario: Without the filter the finding still fires
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
