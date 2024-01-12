Feature: Test that the WP-CLI plugin list checks command works.

  Scenario: List checks
    Given a WP install with the Plugin Check plugin

    When I run the WP-CLI command `plugin list-checks --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"slug":"i18n_usage","category":"general","stability":"stable"}]
      """
    And STDOUT should not contain:
      """
      experimental
      """

    When I run the WP-CLI command `plugin list-checks --format=csv --fields=slug,category`
    Then STDOUT should contain:
      """
      plugin_header_text_domain,plugin_repo
      """

    When I run the WP-CLI command `plugin list-checks --include-experimental`
    Then STDOUT should not be empty

    When I run the WP-CLI command `plugin list-checks --format=csv --categories=general`
    Then STDOUT should contain:
      """
      i18n_usage,general,stable
      """

    When I run the WP-CLI command `plugin list-checks --format=csv --categories="general, security"`
    Then STDOUT should contain:
      """
      i18n_usage,general,stable
      """
    And STDOUT should contain:
      """
      late_escaping,security,stable
      """

    When I try the WP-CLI command `plugin list-checks --categories=nonexistent_category`
    Then STDERR should contain:
      """
      Invalid check category 'nonexistent_category' found. Try 'wp plugin list-check-categories' to view the available check categories.
      """

    When I try the WP-CLI command `plugin list-checks --categories="general, nonexistent_category, another_category"`
    Then STDERR should contain:
      """
      Invalid check categories 'nonexistent_category, another_category' found. Try 'wp plugin list-check-categories' to view the available check categories.
      """
