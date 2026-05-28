# Plugin Check

Plugin Check is a WordPress.org tool which provides checks to help plugins meet the directory requirements and follow various best practices.

## Features

### For end users

* Allows analyzing any installed plugin using either a WP Admin screen or a WP-CLI command.
* Supports two kinds of checks:
    * Static checks, which analyze the code, either using PHPCodeSniffer sniffs or custom logic e.g. using regular expressions.
    * Runtime checks, which actually execute certain parts of the code, such as running specific WordPress hooks with the plugin active.
* Allows customizing which checks are run, either via a list of individual check identifiers, or specific check categories.
* Comes with an ever-growing list of checks for various plugin development requirements and best practices. Please see the [`Default_Check_Repository::register_default_checks()` method](/includes/Checker/Default_Check_Repository.php#L31) for a quick overview of currently available checks.

### For developers

* Facilitates efficient yet flexible authoring of new checks, either using a base class for common check patterns, or implementing an interface for more specific checks.
    * Every check has to implement either the [`Static_Check`](/includes/Checker/Static_Check.php) or the [`Runtime_Check`](/includes/Checker/Runtime_Check.php) interface.
    * Most checks will benefit from extending either the [`Abstract_File_Check`](/includes/Checker/Checks/Abstract_File_Check.php), the [`Abstract_PHPCodeSniffer_Check`](/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php), or the [`Abstract_Runtime_Check`](/includes/Checker/Checks/Abstract_Runtime_Check.php) class.
* Comes with comprehensive unit test coverage.

## How to use

The WordPress plugin checker is a WordPress plugin itself, which can be installed on any WordPress site. While it is implemented in a way that should avoid any disruptions on the site that it is being used on, it is still **advised not to use the plugin checker in a production environment**.

There are a few ways to install the plugin checker:

- Search for "Plugin Check (PCP)" on the page to install plugins (`/wp-admin/plugin-install.php`) on your WP site.
- Download it from the WP.org plugins repository: https://wordpress.org/plugins/plugin-check/
- Clone this repository. See the [contributing section below](#contributing) for further instructions.

After having the plugin activated, you can analyze any other plugin installed on the same site, either using the WP Admin user interface or WP-CLI:

* To check a plugin using WP Admin, please navigate to the _Tools > Plugin Check_ menu. You need to be able to manage plugins on your site in order to access that screen.
* To check a plugin using WP-CLI, please use the `wp plugin check` command. For example, to check the "Hello Dolly" plugin: `wp plugin check hello.php`
    * Note that by default when using WP-CLI, only static checks can be executed. In order to also include runtime checks, a workaround is currently required: use the `--require` argument to manually load `cli.php` from the plugin checker directory before WordPress loads. For example: `wp plugin check hello.php --require=./wp-content/plugins/plugin-check/cli.php`
    * You can use an arbitrary path or URL to check a plugin. For example, to check a plugin from a URL: `wp plugin check https://example.com/plugin.zip` or to check a plugin from a path: `wp plugin check /path/to/plugin`

<img alt="WordPress plugin checker UI in WP Admin" src="https://github.com/WordPress/plugin-check/assets/3531426/19d0c1ce-8c37-4efd-b8c6-d252e6ce29c9">
<em>Screenshot of the plugin checker's UI in WP Admin</em>

## Reviewing a pull request in WordPress Playground

Every pull request opened against this repository gets an automatic **"Open in WordPress Playground"** button appended to its description, running this PR's build of Plugin Check in your browser — no local setup required.

The preview boots a fresh WordPress, installs and activates the PR's build of Plugin Check, logs you in as `admin` / `password`, and lands on _Tools → Plugin Check_ so you can run a check straight away. This makes reviewing UI, admin behaviour, and check output dramatically faster, and lowers the bar for non-developer reviewers.

The button is added by the official [`WordPress/action-wp-playground-pr-preview`](https://github.com/WordPress/action-wp-playground-pr-preview) action via `.github/workflows/pr-playground-preview.yml` and `.github/workflows/pr-playground-preview-publish.yml`. The first workflow builds a production zip of the plugin (Composer dependencies installed without `--dev`, dev files excluded via `.distignore`) with read-only permissions and uploads it as a GitHub Actions artifact. After that build succeeds, the publisher workflow exposes the artifact on a public download URL and appends the **"Open in WordPress Playground"** button to the PR description with a blueprint that installs and activates that exact build.

## Contributing

To set up the repository locally, you will need to clone this GitHub repository (or a fork of it) and then install the relevant dependencies:

```
git clone https://github.com/WordPress/plugin-check.git wp-content/plugins/plugin-check
cd wp-content/plugins/plugin-check
composer install
npm install
```

### Built-in development environment (optional)

With the above commands, you can use the plugin in any development environment as you like. The recommended way is to use the built-in development environment, which is based on the [`@wordpress/env` package](https://www.npmjs.com/package/@wordpress/env), as that will allow you to use the preconfigured commands to e.g. run unit tests, linting etc. You will need to have Docker installed to use this environment.

Start the **development** site:

```
npm run wp-env start
```

Start the **tests** stack:

```
npm run wp-env:start:tests
```

Stop each stack when finished:

```
npm run wp-env stop
npm run wp-env:stop:tests
```

For further information on contributing, please see the [contributing guide](/CONTRIBUTING.md).

### Technical documentation

To learn more about the functionality and technical details of the WordPress plugin checker, please refer to the [technical documentation](./docs/README.md).

## License

The WordPress plugin checker is free software, and is released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE](/LICENSE) for complete license.
