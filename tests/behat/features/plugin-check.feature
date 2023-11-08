Feature: Test that the WP-CLI command works.

  Scenario: Check a non-existent plugin
    Given a WP install with the Plugin Check plugin

    When I try the WP-CLI command `plugin check foo-bar`
    Then STDERR should contain:
      """
      Plugin with slug foo-bar is not installed.
      """

  Scenario: Check Hello Dolly
    Given a WP install with the Plugin Check plugin

    When I try the WP-CLI command `plugin check hello.php`
    Then STDOUT should contain:
	  """
	  mt_rand() is discouraged.
	  """
    And STDOUT should contain:
      """
      All output should be run through an escaping function
      """
