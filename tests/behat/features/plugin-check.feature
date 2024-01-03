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

    When I run the WP-CLI command `plugin check hello.php`
    Then STDOUT should contain:
      """
      mt_rand() is discouraged.
      """
    And STDOUT should not contain:
      """
      no_plugin_readme
      """
    And STDOUT should not contain:
      """
      trademarked_term
      """
    And STDOUT should contain:
      """
      All output should be run through an escaping function
      """

    When I run `wp plugin check hello.php --format=csv`
    Then STDOUT should contain:
      """
      line,column,type,code,message
      49,31,ERROR,WordPress.WP.AlternativeFunctions.rand_mt_rand,"mt_rand() is discouraged. Use the far less predictable wp_rand() instead."
      """

    When I run `wp plugin check hello.php --format=csv --fields=line,column,code`
    Then STDOUT should contain:
      """
      line,column,code
      49,31,WordPress.WP.AlternativeFunctions.rand_mt_rand
      """

    When I run `wp plugin check hello.php --format=json`
    Then STDOUT should contain:
      """
      {"line":49,"column":31,"type":"ERROR","code":"WordPress.WP.AlternativeFunctions.rand_mt_rand","message":"mt_rand() is discouraged. Use the far less predictable wp_rand() instead."}
      """

    When I run `wp plugin check hello.php --ignore-errors`
    Then STDOUT should be empty

    When I run `wp plugin check hello.php --ignore-warnings`
    Then STDOUT should not be empty

    When I run `wp plugin check hello.php --checks=plugin_review_phpcs`
    Then STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    And STDOUT should not contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """

    When I run `wp plugin check hello.php --exclude-checks=late_escaping`
    Then STDOUT should not contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """
    And STDOUT should contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """
    When I run `wp plugin check hello.php --categories=security`
    Then STDOUT should contain:
      """
      WordPress.Security.EscapeOutput.OutputNotEscaped
      """
    And STDOUT should not contain:
      """
      WordPress.WP.AlternativeFunctions.rand_mt_rand
      """

  Scenario: Check Akismet
    Given a WP install with the Plugin Check plugin

    When I run the WP-CLI command `plugin check akismet`
    Then STDOUT should contain:
      """
      FILE: views/config.php
      """

    When I run the WP-CLI command `plugin check akismet --exclude-directories=views`
    Then STDOUT should not contain:
      """
      FILE: views/config.php
      """
