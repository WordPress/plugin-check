# Plugin Check

This repository is for the WordPress plugin checker, a tool for plugin developers to analyze their plugin code and flag any violations or concerns around plugin development best practices, from basic requirements like correct usage of internationalization functions to accessibility, performance, and security best practices.

The WordPress plugin checker was [first proposed in summer 2022](https://make.wordpress.org/plugins/2022/07/05/proposal-for-a-wordpress-plugin-checker/) and is now at an early MVP stage.

## Features

### For end users

* Allows analyzing any installed plugin using either a WP Admin screen or a WP-CLI command.
* Supports two kinds of checks:
    * Static checks, which analyze the code, either using PHPCodeSniffer sniffs or custom logic e.g. using regular expressions.
    * Runtime checks, which actually execute certain parts of the code, such as running specific WordPress hooks with the plugin active.
* Allows customizing which checks are run, either via a list of individual check identifiers, or specific check categories.
* Comes with an ever-growing list of checks for various plugin development requirements and best practices. Please see the [`Abstract_Check_Runner::register_checks()` method](/includes/Checker/Abstract_Check_Runner.php#L358) for a quick overview of currently available checks.

### For developers

* Facilitates efficient yet flexible authoring of new checks, either using a base class for common check patterns, or implementing an interface for more specific checks.
    * Every check has to implement either the [`Static_Check`](/includes/Checker/Static_Check.php) or the [`Runtime_Check`](/includes/Checker/Runtime_Check.php) interface.
    * Most checks will benefit from extending either the [`Abstract_File_Check`](/includes/Checker/Checks/Abstract_File_Check.php), the [`Abstract_PHPCodeSniffer_Check`](/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php), or the [`Abstract_Runtime_Check`](/includes/Checker/Checks/Abstract_Runtime_Check.php) class.
* Comes with comprehensive unit test coverage.

## How to use

The WordPress plugin checker is a WordPress plugin itself, which can be installed on any WordPress site. While it is implemented in a way that should avoid any disruptions on the site that it is being used on, it is still **advised not to use the plugin checker in a production environment**.

Currently, the only way to install the plugin checker is to download it from this GitHub repository. Please see the [contributing section below](#contributing) for further instructions. Once a first beta version is available, it will be distributed in a standalone ZIP file, e.g. via the wordpress.org plugin repository.

After having the plugin activated, you can analyze any other plugin installed on the same site, either using the WP Admin user interface or WP-CLI:

* To check a plugin using WP Admin, please navigate to the _Tools > Plugin Check_ menu. You need to be able to manage plugins on your site in order to access that screen.
* To check a plugin using WP-CLI, please use the `wp plugin check` command. For example, to check the "Hello Dolly" plugin: `wp plugin check hello.php`
    * Note that by default when using WP-CLI, only static checks can be executed. In order to also include runtime checks, a workaround is currently necessary using the `--require` argument of WP-CLI, to manually load the `cli.php` file within the plugin checker directory before WordPress is loaded. For example: `wp plugin check hello.php --require=./wp-content/plugins/plugin-check/cli.php`

<img alt="WordPress plugin checker UI in WP Admin" src="https://github.com/10up/plugin-check/assets/3531426/19d0c1ce-8c37-4efd-b8c6-d252e6ce29c9">
<em>Screenshot of the plugin checker's UI in WP Admin</em>

## Contributing

To set up the repository locally, you will need to clone this GitHub repository (or a fork of it) and then install the relevant dependencies:

```
git clone https://github.com/10up/plugin-check.git wp-content/plugins/plugin-check
cd wp-content/plugins/plugin-check
composer install
npm install
```

### Built-in development environment (optional)

With the above commands, you can use the plugin in any development environment as you like. The recommended way is to use the built-in development environment, which is based on the [`@wordpress/env` package](https://www.npmjs.com/package/@wordpress/env), as that will allow you to use the preconfigured commands to e.g. run unit tests, linting etc. You will need to have Docker installed to use this environment.

You can start the built-in environment as follows:
```
npm run wp-env start
```

If you want to stop the environment again, you can use:
```
npm run wp-env stop
```

For further information on contributing, please see the [contributing guide](/CONTRIBUTING.md).

### Technical documentation

To learn more about the functionality and technical details of the WordPress plugin checker, please refer to the [technical documentation](./docs/README.md).

## License

The WordPress plugin checker is free software, and is released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE](/LICENSE) for complete license.
