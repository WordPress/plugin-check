Feature: Test that the WP-CLI plugin list checks command works.

  Scenario: List checks in JSON format
    Given a WP install with the Plugin Check plugin

    When I try the WP-CLI command `plugin list-checks --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"slug":"i18n_usage","category":"general","stability":"stable"}]
      """
