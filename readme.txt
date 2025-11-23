=== Plugin Check (PCP) ===

Contributors:      wordpressdotorg
Tested up to:      6.9
Stable tag:        1.7.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              plugin best practices, testing, accessibility, performance, security

Plugin Check is a WordPress.org tool which provides checks to help plugins meet the directory requirements and follow various best practices.

== Description ==

Plugin Check is a tool for testing whether your plugin meets the required standards for the WordPress.org plugin directory. With this plugin you will be able to run most of the checks used for new submissions, and check if your plugin meets the requirements.

Additionally, the tool flags violations or concerns around plugin development best practices, from basic requirements like correct usage of internationalization functions to accessibility, performance, and security best practices.

The checks can be run either using the WP Admin user interface or WP-CLI:

* To check a plugin using WP Admin, please navigate to the _Tools > Plugin Check_ menu. You need to be able to manage plugins on your site in order to access that screen.
* To check a plugin using WP-CLI, please use the `wp plugin check` command. For example, to check the "Hello Dolly" plugin: `wp plugin check hello.php`
    * Note that by default when using WP-CLI, only static checks can be executed. In order to also include runtime checks, a workaround is currently necessary using the `--require` argument of WP-CLI, to manually load the `cli.php` file within the plugin checker directory before WordPress is loaded. For example: `wp plugin check hello.php --require=./wp-content/plugins/plugin-check/cli.php`
    * You could use arbitrary path or URL to check a plugin. For example, to check a plugin from a URL: `wp plugin check https://example.com/plugin.zip` or to check a plugin from a path: `wp plugin check /path/to/plugin`

The checks are grouped into several categories, so that you can customize which kinds of checks you would like to run on a plugin.

Keep in mind that this plugin is not a replacement for the manual review process, but it will help you speed up the process of getting your plugin approved for the WordPress.org plugin repository, and it will also help you avoid some common mistakes.

Even if you do not intend to host your plugin in the WordPress.org directory, you are encouraged to use Plugin Check so that your plugin follows the base requirements and best practices for WordPress plugins.

== Installation ==

= Installation from within WordPress =

1. Visit **Plugins > Add New**.
2. Search for **Plugin Check**.
3. Install and activate the Plugin Check plugin.

= Manual installation =

1. Upload the entire `plugin-check` folder to the `/wp-content/plugins/` directory.
2. Visit **Plugins**.
3. Activate the Plugin Check plugin.

== Frequently Asked Questions ==

= Where can I contribute to the plugin? =

All development for this plugin is handled via [GitHub](https://github.com/WordPress/plugin-check/) any issues or pull requests should be posted there.

= What if the plugin reports something that's correct as an "error" or "warning"? =

We strive to write a plugin in a way that minimizes false positives but if you find one, please report it in the GitHub repo. For certain false positives, such as those detected by PHPCodeSniffer, you may be able to annotate the code to ignore the specific problem for a specific line.

= Why does it flag something as bad? =

It's not flagging "bad" things, as such. Plugin Check is designed to be a non-perfect way to test for compliance with the [Plugin Review guidelines](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/), as well as additional plugin development best practices in accessibility, performance, security and other areas. Not all plugins must adhere to these guidelines. The purpose of the checking tool is to ensure that plugins uploaded to the [central WordPress.org plugin repository](https://wordpress.org/plugins/) meet the latest standards of WordPress plugin and will work on a wide variety of sites.

Many sites use custom plugins, and that's perfectly okay. But plugins that are intended for use on many different kinds of sites by the public need to have a certain minimum level of capabilities, in order to ensure proper functioning in many different environments. The Plugin Review guidelines are created with that goal in mind.

This plugin checker is not perfect, and never will be. It is only a tool to help plugin authors, or anybody else who wants to make their plugin more capable. All plugins submitted to WordPress.org are hand-reviewed by a team of experts. The automated plugin checker is meant to be a useful tool only, not an absolute system of measurement.

= Does a plugin need to pass all checks to be approved in the WordPress.org plugin directory? =

To be approved in the WordPress.org plugin directory, a plugin must typically pass all checks in the "Plugin repo" category. Other checks are additional and may not be required to pass.

In any case, passing the checks in this tool likely helps to achieve a smooth plugin review process, but is no guarantee that a plugin will be approved in the WordPress.org plugin directory.

== Changelog ==

= 1.7.0 =

* Enhancement - Add Minified File Detection Check to identify and handle minified files in plugins.
* Enhancement - Implement check for insecure use of wp_verify_nonce() to improve security validation.
* Enhancement - Add direct database query sniff to detect direct database calls without using WordPress functions.
* Enhancement - Add prefixing check to ensure proper function and class name prefixing.
* Enhancement - Update localhost sniff regex to improve detection of localhost URLs including *.local domains.
* Enhancement - Disallow runtime checks when custom user table constants are defined for better compatibility.
* Enhancement - Add forbidden functions check to detect usage of disallowed PHP functions.
* Enhancement - New check for wp_safe_redirect to encourage use of WordPress safe redirect function.
* Enhancement - Improve mismatched text domain check for better internationalization validation.
* Enhancement - Detect links that request five-star reviews to enforce plugin directory guidelines.
* Enhancement - Add The Unlicense to GPL-compatible license check.
* Enhancement - Improve localhost sniff code for more accurate detection.
* Fix - Ignore vendor_prefixed and vendor-prefixed folders in checks to prevent false positives.
* Fix - Handle possible empty element in scanner to prevent PHP warnings.
* Fix - Hide error output in scanner for cleaner output.
* Fix - Call ReflectionProperty::setAccessible() only in older PHP versions for better PHP 8.1+ compatibility.
* Fix - Prevent deletion of custom WordPress tables during cleanup in test environment.

= 1.6.0 =

* Enhancement - Support strict output format for CLI commands.
* Enhancement - Improve check for donate link in readme.
* Enhancement - Improve info check in Version utils.
* Enhancement - Improve URL validation for plugin header fields.
* Enhancement - Improve ruleset files.
* Enhancement - Increased severity for invalid plugin uri domain and plugin description checks in plugin header.
* Enhancement - Remove CallTimePassByReference as it's deprecated rule.
* Enhancement - Disallow special characters in textdomain.
* Enhancement - Imported readme parser for preventing conflicts with wordpress.org. Use dotorg readme parser if available.
* Enhancement - Discourage the use of `load_plugin_textdomain` found in plugins as it's not necessary in wordpress.org.
* Enhancement - Upgrade severity for missing readme headers.
* Enhancement - Show tested up to minor check only when it is current major version.
* Enhancement - Added link in plugins page to run the plugin check.
* Fix - Dynamic WP Content folder.
* Fix - Fix test for special chars in file names giving problems to users after clone.
* Fix - Remove Image_Functions_Check as they were making false positives.
* Fix - Prevent WordPress version 10+ from being flagged as an error in the "Tested up to" check.

= 1.5.0 =

* Enhancement - Improve url validation to check duplicate protocol.
* Enhancement - Update severity for incorrect textdomains and i18n error codes.
* Enhancement - Now issues in URL Author check are ERROR instead of WARNING.
* Enhancement - New check for minor version in Tested up.
* Enhancement - Make sure headers are not empty in the requires header check.
* Enhancement - Include experimental option in admin.
* Enhancement - Add Behat test for experimental checks from addons.
* Enhancement - Improve license check for Apache.
* Enhancement - Warn if requires headers are not same in readme and plugin header.
* Fix - Remove warning for dynamic callback in register_setting check.
* Fix - Incorrect database tables being referenced on subsites in Multisite.

= 1.4.0 =

* Enhancement - Allow ISC license in the License check.
* Enhancement - Added check for use of settings with sanitization callback.
* Enhancement - Added --ignore-codes in CLI to introduce a mechanism to ignore specific error codes.
* Enhancement - New utils for fetching necessary version info details.
* Enhancement - Added check for unsupported plugin name in plugin header field.
* Enhancement - Segregate the severity of i18n checks. Make sure that is giving errors in the right context.
* Enhancement - Provide more detailed information about checks when the README does not include a tested version or a list of contributors.
* Enhancement - Added rules from WPCS to prevent issues with content being sent before headers and warn about the use of Call-time pass-by-reference.
* Enhancement - Give more context in the error of check wrong named files.
* Enhancement - Simplified PHPUnit setup. Now it does not depend of running inside a WordPress installation.
* Enhancement - Added new check for restricted contributors.
* Fix - Delete transients in unit tests to avoid false positives.
* Fix - Incorrect Tested up to version comparison will make error for two major versions up.
* Fix - Excluded the use of functions file_get_contents and file_put_contents in the check to prevent false positives.
* Fix - Duplicated error message in the check for wrong named files.
* Fix - Use of Json encode wasn't firing the error message.
* Fix - Change error type of NonEnqueuedImage in ImageFunctions sniff from ERROR to WARNING.

= 1.3.1 =

* Enhancement - Add version utilities.
* Fix - Escape error messages.
* Fix - Renamed error type to ERROR_LOW_SEVERITY and WARNING_LOW_SEVERITY.
* Fix - Fix PHPCS checks on unwritable filesystems.

= 1.3.0 =

* Enhancement - Update disallowed domains for Plugin URI check.
* Enhancement - Added new checks for Plugin Header fields: missing plugin description, missing plugin version and invalid plugin version.
* Enhancement - New check for validation of donate link in the readme file.
* Enhancement - Increased severity for wrong Plugin Requires.
* Enhancement - Added check Restrict parse_str() without second argument.
* Enhancement - New check for Disallow usage of HEREDOC and NOWDOC.
* Enhancement - Added acronyms allowed in Trademark checks.
* Enhancement - Added option in CLI to add low severity errors and warnings.
* Enhancement - Change error type for License check error codes.
* Enhancement - Always use prefixed tables during runtime check requests.
* Enhancement - Created a new class for checking licenses.
* Enhancement - Added support for MPL-2.0 license.
* Enhancement - Implement gherkin linter in GH action.
* Enhancement - Update check for Contributors in markdown readme files.
* Enhancement - CLI: Fix confusing runtime environment setup order.
* Enhancement - Allow custom checks to provide installed_paths.
* Enhancement - Improved the use of localhost URLs in the Plugin.
* Enhancement - Documented checks in the plugin.
* Enhancement - Increased severity for Code obfuscation checks.
* Enhancement - Differentiate between non-existent readme and default readme file.
* Enhancement - Encourage developers to use native functions for loading images in templates.
* Enhancement - Added a check for not allowing include libraries already in WordPress core.
* Enhancement - Warning for usage of query_posts() in favor of WP_Query.
* Fix - Fix for the local environment is set up before testing.
* Fix - Fix addon checks not being executed when running runtime checks.
* Fix - Allow `default` as a text domain in the text domain check.
* Fix - Allow GitHub URLs in the Plugin URI field.
* Fix - Don't flag Apache license. It's allowed in the WordPress.org plugin repository.
* Fix - Removes the path before the plugin, so it won't affect to badly named files.

= 1.2.0 =

* Enhancement - Added a check for badly used names in files.
* Enhancement - Increased severity for `BacktickOperator`, `DisallowShortOpenTag`, `DisallowAlternativePHPTags`, `RestrictedClasses`, and `RestrictedFunctions`.
* Enhancement - Added security checks to the Plugin repository category.
* Enhancement - Allowed `runtime-set` in code sniffer checks.
* Enhancement - Changed warnings to errors in plugin header checks.
* Enhancement - Detect forbidden plugin headers such as repository URIs in the Directory.
* Enhancement - Added a new check for development functions that are not allowed in final plugins.
* Enhancement - Created new images and icons for the plugin.
* Enhancement - Introduced a slug argument in the CLI.
* Enhancement - Added a check for discouraged PHP functions.
* Enhancement - Added validation for Contributors in the readme file.
* Enhancement - Added a warning for mismatched plugin names in the plugin header and readme file.
* Enhancement - Checked for validation of Plugin Header fields: Name, Plugin URI, Description, Author URI, Requires at least, Requires PHP, and Requires Plugins.
* Enhancement - Added a warning if the "Tested up to" value in the readme file exceeds the released version of WordPress.
* Fix - Display a success message if no errors or warnings are found.
* Fix - Made table results responsive.
* Fix - Prevent proceeding to the next check if the Stable Tag value is set to `trunk`.
* Fix - Allow runtime initialization even when only add-on checks are requested.
* Fix - Fixed an SPDX warning for the `GPL version 3` license.
* Fix - Prevent runtime checks in the CLI context when they cannot be used.

= 1.1.0 =

* Feature - New `Non_Blocking_Scripts_Check` (`non_blocking_scripts`) runtime check to warn about enqueued scripts that use neither `defer` nor `async`.
* Enhancement - Changed the namespace of included checks.
* Enhancement - Introduced severity levels for all errors and warnings.
* Enhancement - CLI: Support checking a plugin from a path or URL.
* Enhancement - Added short descriptions and URLs for each check.
* Enhancement - Improved messaging in check results.
* Enhancement - Updated code obfuscation check with more accurate results.
* Enhancement - Updated plugin review check to flag missing input sanitization (`WordPress.Security.ValidatedSanitizedInput`).
* Fix - Improve readme checks to exclude invalid files.
* Fix - Only show edit link if files are actually editable.

= 1.0.2 =

* Feature - New `Enqueued_Scripts_Scope_Check` (`enqueued_scripts_scope`), `Enqueued_Styles_Size_Check` (`enqueued_styles_size`) and `Enqueued_Resources_Check` (`enqueued_resources`) performance checks.
* Enhancement - Improved readme check and added a new `wp_plugin_check_ignored_readme_warnings` filter.
* Enhancement - New `wp_plugin_check_default_categories` filter to change the categories which are selected by default.
* Enhancement - New `wp_plugin_check_ignore_files` filter to allow ignoring specific files.
* Fix - Correct detection of readme files in Windows by normalizing file paths.

= 1.0.1 =

* Fix - Add missing `test-content` folder needed for runtime checks.
* Fix - Do not send emails when setting up test environment.
* Fix - Prevent PHP warning when the `argv` variable isn't set.

= 1.0.0 =

* Feature - Complete overhaul of the plugin, its architecture, and all checks.
* Feature - Added new [WP-CLI commands](https://github.com/WordPress/plugin-check/blob/trunk/docs/CLI.md) for running checks and listing available options.
* Enhancement - Added option to only run checks for a specific category.

= 0.2.3 =

* Tweak - Use version [3.8.0 of the PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/releases/tag/3.8.0) library, moving away from `squizlabs/PHP_CodeSniffer` to use `PHPCSStandards/PHP_CodeSniffer`.
* Fix - Ensure the plugin works as expected on the WP playground environment to enable reviewers to use PCP. Props @tellyworth.
* Fix - Undefined array key "argv" when running the plugin check in certain environments. Props @afragen. [#340](https://github.com/WordPress/plugin-check/pull/340)

= 0.2.2 =

* Enhancement - Include support for Windows Servers.
* Enhancement - Avoid using PHP CLI directly, which enables plugin developers to use PCP in a variety of new environments.
* Fix - Remove dependency on `shell_exec` and `exec` functions, which enables plugin developers to use PCP in a variety of new environments.
* Fix - Prevent problems with Readme parser warning related to `contributor_ignored` for when running the check outside WP.org. Props @dev4press. [#276](https://github.com/10up/plugin-check/pull/276)
* Fix - Remove extra period on the end of the sentence for Phar warning. Props @pixolin. [#275](https://github.com/10up/plugin-check/pull/275)

= 0.2.1 =

* Added - 'View in code editor' link beneath each PHPCS error or warning. Props @EvanHerman, @westonruter, @felixarntz, @mukeshpanchal27 [#262](https://github.com/10up/plugin-check/pull/262)
* Fix - Ensure `readme.txt` has priority over `readme.md` when both are present. Props @bordoni, @afragen [#258](https://github.com/10up/plugin-check/pull/258)
* Fix - Ensure that the PHPCS check runs even when the PHPCS binary is not executable. Props @bordoni, @shawn-digitalpoint, @mrfoxtalbot [#254](https://github.com/10up/plugin-check/pull/254)
* Fix - Readme changes and typos. Props @aaronjorbin. [#261](https://github.com/10up/plugin-check/pull/261)
* Fix - Long lines of code with PHPCS check no longer expand over the size of the notice. Props @bordoni, @felixarntz. [#263](https://github.com/10up/plugin-check/pull/263)
* Fix - Ensure that we have PHP 7.2 compatibility remove trailing comma. Props @bordoni, @leoloso. [#265](https://github.com/10up/plugin-check/issues/265)
* Fix - Include all strings that were missed in the previous release. Props @bordoni, @pixolin. [#270](https://github.com/10up/plugin-check/issues/270)

= 0.2.0 =

* Feature - Enable modification of the PHP Binary path used by the plugin with `PLUGIN_CHECK_PHP_BIN` constant.
* Feature - Include a check for the usage of `ALLOW_UNFILTERED_UPLOADS` on any PHP files - Props EvanHerman at [#45](https://github.com/WordPress/plugin-check/pull/45)
* Feature - Include a check for the presence of the application files (`.a`, `.bin`, `.bpk`, `.deploy`, `.dist`, `.distz`, `.dmg`, `.dms`, `.DS_Store`, `.dump`, `.elc`, `.exe`, `.iso`, `.lha`, `.lrf`, `.lzh`, `.o`, `.obj`, `.phar`, `.pkg`, `.sh`, '.so`) - Props EvanHerman at [#43](https://github.com/WordPress/plugin-check/pull/43)
* Feature - Include a check for the presence of the readme.txt or readme.md file - Props EvanHerman at [#42](https://github.com/WordPress/plugin-check/pull/42)
* Fix - Ensure that Readme parsing is included properly when a readme.md or readme.txt file is present. Props Bordoni [#52](https://github.com/WordPress/plugin-check/pull/52)
* Tweak - Disallow functions `move_uploaded_file`, `passthru`, `proc_open` - Props alexsanford at [#50](https://github.com/WordPress/plugin-check/pull/50)
* Tweak - Change the message type for using functions WordPress already includes from Warning to Error. Props davidperezgar at [#18](https://github.com/WordPress/plugin-check/issues/18)
* Tweak - Change the message type for incorrect usage of Stable tag from Notice/Warning to Error. Props davidperezgar at [#3](https://github.com/WordPress/plugin-check/issues/3)

= [0.1] 2011-09-04 =

Original version of the plugin check tool, not a released version of the plugin, this changelog is here for historical purposes only.
