Feature: Test that the WP-CLI command works.

  Scenario: Check severity level
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/pcp-addon/pcp-addon.php file:
      """
      <?php
      /**
      * Plugin Name: PCP Addon
      * Plugin URI: https://example.com
      * Description: Plugin Check addon.
      * Version: 0.1.0
      * Author: WordPress Performance Team
      * Author URI: https://make.wordpress.org/performance/
      * License: GPL-2.0+
      * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
      */

      use WordPress\Plugin_Check\Checker\Check_Result;
      use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
      use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
      use WordPress\Plugin_Check\Traits\Amend_Check_Result;
      use WordPress\Plugin_Check\Traits\Stable_Check;

      if ( ! class_exists( WordPress\Plugin_Check\Plugin_Main::class, false ) ) {
        require_once WP_PLUGIN_DIR . '/plugin-check/vendor/autoload.php';
      }

      class Prohibited_Text_Check extends Abstract_File_Check {

        use Amend_Check_Result;
        use Stable_Check;

        public function get_categories() {
          return array( 'general' );
        }

        protected function check_files( Check_Result $result, array $files ) {
          $php_files = self::filter_files_by_extension( $files, 'php' );
          $file      = self::file_preg_match( '#I\sam\sbad#', $php_files );
          if ( $file ) {
            $this->add_result_error_for_file(
              $result,
              __( 'Prohibited text found.', 'pcp-addon' ),
              'prohibited_text_detected',
              $file,
              0,
              0,
              8
            );
          }
        }
      }

      class PostsPerPage_Check extends Abstract_PHP_CodeSniffer_Check {

        use Stable_Check;

        public function get_categories() {
          return array( 'general' );
        }

        protected function get_args() {
          return array(
            'extensions' => 'php',
            'standard'   => plugin_dir_path( __FILE__ ) . 'postsperpage.xml',
          );
        }
      }

      add_filter(
        'wp_plugin_check_checks',
        function ( array $checks ) {
          return array_merge(
            $checks,
            array(
              'prohibited_text' => new Prohibited_Text_Check(),
              'postsperpage'    => new PostsPerPage_Check(),
            )
          );
        }
      );
      """
    And a wp-content/plugins/pcp-addon/postsperpage.xml file:
      """
      <?xml version="1.0"?>
      <ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="PCPAddon" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/squizlabs/PHP_CodeSniffer/master/phpcs.xsd">
        <rule ref="WordPress.WP.PostsPerPage">
          <severity>9</severity>
        </rule>
      </ruleset>
      """
    And I run the WP-CLI command `plugin activate pcp-addon`
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

      add_action(
        'init',
        function () {
          echo absint( mt_rand( 10, 100 ) );

          echo 'I am bad'; // This should trigger the error.

          $qargs = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'no_found_rows'  => true,
          );
        }
      );
      """

    When I run the WP-CLI command `plugin list --status=active`
    Then STDOUT should contain:
      """
      pcp-addon
      """
    And STDOUT should contain:
      """
      plugin-check
      """

    When I run the WP-CLI command `plugin list-checks`
    Then STDOUT should contain:
      """
      prohibited_text
      """
    And STDOUT should contain:
      """
      postsperpage
      """

    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme`
    Then STDOUT should contain:
      """
      mt_rand() is discouraged.
      """
    And STDOUT should contain:
      """
      prohibited_text_detected
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
      """

    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme --severity=4`
    Then STDOUT should contain:
      """
      mt_rand() is discouraged.
      """
    And STDOUT should contain:
      """
      prohibited_text_detected
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
      """

    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme --severity=8`
    Then STDOUT should contain:
      """
      prohibited_text_detected
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
      """
    And STDOUT should not contain:
      """
      mt_rand() is discouraged.
      """

    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme --severity=9`
    Then STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
      """
    And STDOUT should not contain:
      """
      mt_rand() is discouraged.
      """
    And STDOUT should not contain:
      """
      prohibited_text_detected
      """

    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme --severity=10`
    Then STDOUT should be empty
