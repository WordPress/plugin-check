=== Plugin Check (PCP) ===

Contributors:      wordpressdotorg
Requires at least: 6.3
Tested up to:      6.5
Stable tag:        1.0.1
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

= 1.0.2 =

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
