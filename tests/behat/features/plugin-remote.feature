Feature: Test remote.

  Scenario: Test remote
    Given a WP install with the Plugin Check plugin

    When I run the WP-CLI command `plugin check https://github.com/ernilambar/foo-bar-wp/releases/latest/download/foo-bar-wp.zip --fields=code,type --format=csv`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand,ERROR
      """
    And STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped,ERROR
      """
    And STDOUT should contain:
      """
      outdated_tested_upto_header,ERROR
      """
    And STDERR should be empty
