Plugin Check
===============
* Contributors: dd32, davidperezgar, bordoni, frantorres
* Requires at least: 6.2
* Tested up to: 6.3
* Stable tag: 1.0.0
* License: GPLv2 or later
* Requires PHP: 7.2
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin Check is a tool from the WordPress.org plugin review team, it provides an initial check of whether your plugin meets our requirements for hosting.

== Description ==

Plugin Check is a tool from the WordPress.org plugin review team.
It provides an initial check of whether your plugin meets our requirements for hosting.

Development occurs within https://github.com/WordPress/plugin-check/, please submit PRs and Bug Reports there.

== Changelog ==

= [1.0.0] TBD =

* Feature - Enable modification of the PHP Binary path used by the plugin with `PLUGIN_CHECK_PHP_BIN` constant.
* Feature - Include a check for the usage of `ALLOW_UNFILTERED_UPLOADS` on any PHP files - Props EvanHerman at [#45](https://github.com/WordPress/plugin-check/pull/45)
* Feature - Include a check for the presence of the application files (`.a`, `.bin`, `.bpk`, `.deploy`, `.dist`, `.distz`, `.dmg`, `.dms`, `.DS_Store`, `.dump`, `.elc`, `.exe`, `.iso`, `.lha`, `.lrf`, `.lzh`, `.o`, `.obj`, `.phar`, `.pkg`, `.sh`, '.so`) - Props EvanHerman at [#43](https://github.com/WordPress/plugin-check/pull/43)
* Feature - Include a check for the presence of the readme.txt or readme.md file - Props EvanHerman at [#42](https://github.com/WordPress/plugin-check/pull/42)
* Tweak - Disallow functions `move_uploaded_file`, `passthru`, `proc_open` - Props alexsanford at [#50](https://github.com/WordPress/plugin-check/pull/50)
* Tweak - Change the message type for using functions WordPress already includes from Warning to Error. Props davidperezgar at [#18](https://github.com/WordPress/plugin-check/issues/18)
* Tweak - Change the message type for incorrect usage of Stable tag from Notice/Warning to Error. Props davidperezgar at [#3](https://github.com/WordPress/plugin-check/issues/3)
