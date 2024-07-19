Feature: Test that the WP-CLI command works.

  Background:
    Given a WP install with the Plugin Check plugin
    And a wp-content/plugins/pcp-addon/class-postsperpage-check.php file:
      """
      <?php
      use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
      use WordPress\Plugin_Check\Traits\Stable_Check;

      class PostsPerPage_Check extends Abstract_PHP_CodeSniffer_Check {

        use Stable_Check;

        public function get_categories() {
          return array( 'new_category' );
        }

        protected function get_args() {
          return array(
            'extensions' => 'php',
            'standard'   => plugin_dir_path( __FILE__ ) . 'postsperpage.xml',
          );
        }
      }
      """
    And a wp-content/plugins/pcp-addon/class-prohibited-text-check.php file:
      """
      <?php
      use WordPress\Plugin_Check\Checker\Check_Result;
      use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
      use WordPress\Plugin_Check\Traits\Amend_Check_Result;
      use WordPress\Plugin_Check\Traits\Stable_Check;

      class Prohibited_Text_Check extends Abstract_File_Check {

        use Amend_Check_Result;
        use Stable_Check;

        public function get_categories() {
          return array( 'new_category' );
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
      """

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
       * Requires Plugins: plugin-check
       */

      add_filter(
        'wp_plugin_check_categories',
        function ( array $categories ) {
          return array_merge( $categories, array( 'new_category' => esc_html__( 'New Category', 'pcp-addon' ) ) );
        }
      );

      add_filter(
        'wp_plugin_check_checks',
        function ( array $checks ) {
          require_once plugin_dir_path( __FILE__ ) . 'class-prohibited-text-check.php';
          require_once plugin_dir_path( __FILE__ ) . 'class-postsperpage-check.php';

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
          <type>error</type>
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

  Scenario: Basic checks with addon
    When I run the WP-CLI command `plugin list --field=name --status=active`
    Then STDOUT should contain:
      """
      pcp-addon
      """
    And STDOUT should contain:
      """
      plugin-check
      """

    When I run the WP-CLI command `plugin list-check-categories --fields=slug,name --format=csv`
    Then STDOUT should contain:
      """
      new_category,"New Category"
      """

    When I run the WP-CLI command `plugin list-checks --fields=slug,category,stability --format=csv`
    Then STDOUT should contain:
      """
      prohibited_text,new_category,stable
      """
    And STDOUT should contain:
      """
      postsperpage,new_category,stable
      """

    When I run the WP-CLI command `plugin list-checks --fields=slug,category --format=csv --categories=new_category`
    Then STDOUT should contain:
      """
      prohibited_text,new_category
      """
    And STDOUT should contain:
      """
      postsperpage,new_category
      """
    And STDOUT should not contain:
      """
      plugin_review_phpcs,plugin_repo
      """

  Scenario: Check no severity level
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --exclude-checks=plugin_readme`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page,ERROR
      """

  Scenario: Check severity level 5
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --exclude-checks=plugin_readme --severity=5`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page,ERROR
      """

  Scenario: Check severity level 8
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --exclude-checks=plugin_readme --severity=8`
    Then STDOUT should contain:
      """
      prohibited_text_detected,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page,ERROR
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """

  Scenario: Check severity level 9
    When I run the WP-CLI command `plugin check foo-sample --fields=code,type --format=csv --exclude-checks=plugin_readme --severity=9`
    Then STDOUT should contain:
      """
      WordPress.WP.PostsPerPage.posts_per_page_posts_per_page,ERROR
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should not contain:
      """
      prohibited_text_detected,ERROR
      """

  Scenario: Check severity level 10
    When I run the WP-CLI command `plugin check foo-sample --exclude-checks=plugin_readme --severity=10`
    Then STDOUT should be empty
