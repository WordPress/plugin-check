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

    When I run the WP-CLI command `plugin list --require=./wp-content/pcp-config.php --require=./wp-content/plugins/plugin-check/cli.php`
    Then STDERR should contain:
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
