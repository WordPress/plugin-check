# Plugin Check Plugin (PCP)

Plugin Check is a tool from the WordPress.org plugin review team, it provides an initial check of whether your plugin meets our requirements for hosting.

## Description

The Plugin Check is an easy way of testing your plugin and ensure that it's up to the base required standards from the Plugin Review team. With this plugin you will be able to run most of the checks used by the team, and check if your plugin meets the requirements.

The tests are run through a simple admin menu and all results are displayed at once. This is very handy for theme developers, or anybody looking to make sure that their plugin supports the [latest WordPress plugin standards and practices](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/).

Keep in mind that this plugin is not yet a replacement for the manual review process, but it will help you speed up the process of getting your plugin approved for the WordPress.org plugin repository, and it will also help you avoid some common mistakes.

## Installation

Head over to the WordPress.org plugin repository and [download the plugin](https://wordpress.org/plugins/plugin-check/), it should be used like any other WordPress plugin.

## Development

Setup steps:
 - `composer install`
 - `npm install`
 - `npm run wp-env start`
 - `npm run setup:tools`

Commands:
 - `npm run wp-env start`
 - `npm test`
