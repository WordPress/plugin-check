
=== Plugin Check ===

Contributors:      wordpressdotorg
Requires at least: 6.3
Tested up to:      6.3
Requires PHP:      7.0
Stable tag:        n.e.x.t
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              performance, testing, security

Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.

== Description ==

The Plugin Check is an easy way of testing your plugin and ensure that it's up to the base required standards from the Plugin Review team. With this plugin you will be able to run most of the checks used by the team, and check if your plugin meets the requirements.

The tests are run through a simple admin menu and all results are displayed at once. This is very handy for plugin developers, or anybody looking to make sure that their plugin supports the [latest WordPress plugin standards and practices](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/).

Keep in mind that this plugin is not yet a replacement for the manual review process, but it will help you speed up the process of getting your plugin approved for the WordPress.org plugin repository, and it will also help you avoid some common mistakes.

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

= What if the plugin reports as "error" something that's correct? =

We strived to write a plugin in a way that minimizes false positives but If you find one, please report it in the GitHub repo.

If you can, please consider submitting a Pull Request to fix it.

= Why does it flag something as bad? =

It's not flagging "bad" things, as such. The plugin check is designed to be a non-perfect way to test for compliance with the [Plugin Review guidelines](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/). Not all plugins must adhere to these guidelines. The purpose of the checking tool is to ensure that plugins uploaded to the [central WordPress.org plugin repository](https://wordpress.org/plugins/) meet the latest standards of WordPress plugin and will work on a wide variety of sites.

Many sites use custom plugins, and that's perfectly okay. But plugins that are intended for use on many different kinds of sites by the public need to have a certain minimum level of capabilities, in order to ensure proper functioning in many different environments. The Plugin Review guidelines are created with that goal in mind.

This plugin checker is not perfect, and never will be. It is only a tool to help plugin authors, or anybody else who wants to make their plugin more capable. All plugins submitted to WordPress.org are hand-reviewed by a team of experts. The automated plugin checker is meant to be a useful tool only, not an absolute system of measurement.
