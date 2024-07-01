Feature: Test that the WP-CLI plugin list check categories command works.

  Scenario: List check categories
    Given a WP install with the Plugin Check plugin

    When I run the WP-CLI command `plugin list-check-categories --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"name":"General","slug":"general"}]
      """

    When I run the WP-CLI command `plugin list-check-categories --format=csv --fields=slug,name`
    Then STDOUT should contain:
      """
      plugin_repo,"Plugin Repo"
      """
