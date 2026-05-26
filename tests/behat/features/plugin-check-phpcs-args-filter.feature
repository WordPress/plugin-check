Feature: Test that the `wp_plugin_check_phpcs_args` filter can override PHPCS arguments.

  Scenario: Bootstrap-registered filter excludes a PHPCS sniff that would otherwise fire
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
      add_filter(
          'wp_plugin_check_phpcs_args',
          static function ( array $args ): array {
              // Append our sniff code to whatever `exclude` already holds (PHPCS expects a CSV string).
              $existing        = isset( $args['exclude'] ) ? (string) $args['exclude'] : '';
              $args['exclude'] = ltrim(
                  $existing . ',WordPress.WP.AlternativeFunctions.rand_mt_rand',
                  ','
              );
              return $args;
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

  Scenario: Filter is a no-op when nobody subscribes
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
