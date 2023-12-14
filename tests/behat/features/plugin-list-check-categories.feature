Feature: Test that the WP-CLI plugin list check categories command works.

  Scenario: List check categories in JSON format
    Given a WP install with the Plugin Check plugin

    When I try the WP-CLI command `plugin list-check-categories --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"name":"General","slug":"general"}]
      """
