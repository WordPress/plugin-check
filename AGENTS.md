# Plugin Check – AI Coding Agent Instructions

This file provides context for AI coding agents (Claude Code, OpenAI Codex, Gemini CLI, GitHub Copilot, etc.) working on the Plugin Check repository.

## Project Overview

**Plugin Check** (also known as PCP – Plugin Check Plugin) is a WordPress.org tool that helps plugin authors ensure their plugins meet the [WordPress.org Plugin Directory requirements](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) and follow best practices.

It supports two modes of operation:
- **WP Admin UI** – accessible via _Tools > Plugin Check_.
- **WP-CLI** – via `wp plugin check <plugin-slug>`.

## Repository Structure

```
plugin-check/
├── plugin.php              # Main plugin entry point
├── cli.php                 # WP-CLI bootstrap (loaded via --require for runtime checks)
├── includes/               # Core PHP source (PSR-4 autoloaded under WordPress\Plugin_Check\)
│   ├── Admin/              # WP Admin UI screens and assets registration
│   ├── Checker/            # Core check interfaces, abstract classes, and all check implementations
│   │   ├── Checks/         # Individual check classes (static and runtime)
│   │   ├── Abstract_Check_Runner.php
│   │   ├── Check_Categories.php
│   │   ├── Check_Result.php
│   │   ├── Default_Check_Repository.php  # Registers all built-in checks
│   │   ├── Runtime_Check.php             # Interface for runtime checks
│   │   └── Static_Check.php              # Interface for static checks
│   ├── CLI/                # WP-CLI command classes
│   ├── Lib/                # Third-party libraries (do not lint)
│   ├── Scanner/            # Plugin scanner and file utilities
│   ├── Traits/             # Reusable traits (Stable_Check, Experimental_Check, etc.)
│   ├── Utilities/          # Helper utilities
│   └── Vars/               # Static data (e.g., wp-functions-since.json)
├── tests/
│   ├── phpunit/
│   │   ├── tests/          # All PHPUnit test classes
│   │   ├── testdata/       # Example plugins and classes used in tests
│   │   └── utils/          # Shared test helpers
│   └── behat/              # End-to-end Behat/WP-CLI tests
├── docs/                   # Technical documentation
├── assets/                 # JS/CSS for the WP Admin UI
├── phpcs-sniffs/           # Custom PHPCS sniffs bundled with the plugin
├── phpcs-rulesets/         # PHPCS ruleset definitions
├── templates/              # PHP templates for the Admin UI
├── tools/                  # Developer scripts (e.g., generate WP function data)
├── drop-ins/               # WordPress drop-in files used during runtime checks
└── runtime-content/        # Content used to set up the runtime check environment
```

## Coding Standards

All PHP code **must** follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- **PHP minimum version**: 7.4
- **WordPress minimum version**: 6.3
- **Namespace**: `WordPress\Plugin_Check\` (PSR-4, mapped to `includes/`)
- Use tabs for indentation (not spaces).
- All public methods and classes must have proper PHPDoc blocks (except in test files).
- Text domain: `plugin-check` (or `default` for core strings).
- Do **not** add docblocks to unit test methods/classes/files (excluded by PHPCS config).
- Third-party code lives in `includes/Lib/` and is excluded from linting.

### JavaScript

- Follows [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts) ESLint configuration.
- Source files are in `assets/js/` and `assets/css/`.

## Architecture: Adding a New Check

Every check must implement either `Static_Check` or `Runtime_Check` (both in `includes/Checker/`).

### Static Check (no code execution)

Extend `Abstract_File_Check` or `Abstract_PHP_CodeSniffer_Check`, or implement `Static_Check` directly:

```php
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class My_Custom_Check implements Static_Check {
    use Stable_Check;

    public function get_categories(): array {
        return array( Check_Categories::CATEGORY_PLUGIN_REPO );
    }

    public function run( Check_Result $result ): void {
        // Add errors/warnings to $result.
    }
}
```

### Runtime Check (executes plugin code)

Extend `Abstract_Runtime_Check`. Runtime checks may also need a `Preparation` class.

### Registering a Check

Add the new check class to `Default_Check_Repository::register_default_checks()` in `includes/Checker/Default_Check_Repository.php`.

### Check Stability

- Use the `Stable_Check` trait for checks that run by default.
- Use the `Experimental_Check` trait for checks only available via `--include-experimental`.

### Check Categories

Use constants from `Check_Categories`:
- `CATEGORY_GENERAL` – General best practices.
- `CATEGORY_PLUGIN_REPO` – WordPress.org Plugin Directory requirements.
- `CATEGORY_SECURITY` – Security-related checks.
- `CATEGORY_PERFORMANCE` – Performance-related checks.
- `CATEGORY_ACCESSIBILITY` – Accessibility checks.

## Common Commands

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Development Environment (requires Docker)

```bash
# Start the development site
npm run wp-env start

# Start the test stack
npm run wp-env:start:tests

# Stop each stack
npm run wp-env stop
npm run wp-env:stop:tests
```

### Linting

```bash
# PHP linting (PHPCS)
composer lint
# or via npm (uses wp-env):
npm run lint-php

# PHP auto-fix (PHPCBF)
composer format
# or via npm:
npm run format-php

# JavaScript linting
npm run lint-js

# JavaScript auto-fix
npm run format-js

# Gherkin/Behat feature files
npm run lint-gherkin

# PHPStan static analysis
composer phpstan
# or via npm:
npm run phpstan
```

### Testing

```bash
# Run PHPUnit tests (requires test stack running)
npm run test-php

# Run PHPUnit with coverage
npm run test-php-coverage

# Run multisite PHPUnit tests
npm run test-php-multisite

# Run Behat/WP-CLI integration tests
composer behat
```

### Running the plugin locally

Use [WordPress Playground CLI](https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli) to run WordPress with the plugin auto-mounted:

```bash
npx @wp-playground/cli@latest server --auto-mount --php=8.1 --login --port=9400
```

This starts WordPress at `http://127.0.0.1:9400` with the plugin already active. No Docker, MySQL, or Apache needed for manual testing — Playground uses SQLite internally.

### WP-CLI Usage (on a local site)

```bash
# Static checks only
wp plugin check <plugin-slug>

# Static + runtime checks
wp plugin check <plugin-slug> --require=./wp-content/plugins/plugin-check/cli.php

# Check from a ZIP URL
wp plugin check https://example.com/plugin.zip --require=./wp-content/plugins/plugin-check/cli.php

# List available checks
wp plugin check-list
```

## Testing Guidelines

- Every new check **must** have a corresponding PHPUnit test in `tests/phpunit/tests/`.
- Test data (example plugins/classes) goes in `tests/phpunit/testdata/`.
- Shared test helpers go in `tests/phpunit/utils/`.
- Tests are run via GitHub Actions on every PR; passing tests is required to merge.
- The test bootstrap is at `tests/phpunit/bootstrap.php`.

## Contribution Guidelines

- All contributions must follow the [WordPress Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).
- All code is licensed under **GPL-2.0-or-later**.
- Open issues and PRs on [GitHub](https://github.com/WordPress/plugin-check).
- See [CONTRIBUTING.md](./CONTRIBUTING.md) for the full guide.
- See [SECURITY.md](./SECURITY.md) for reporting security issues.

## Key Documentation

- [Technical Overview](./docs/technical-overview.md)
- [Creating a Static Check](./docs/creating-a-static-check.md)
- [Creating a Runtime Check](./docs/creating-a-runtime-check.md)
- [Available Checks](./docs/checks.md)
- [CLI Commands](./docs/CLI.md)
- [Running Unit Tests](./docs/running-unit-tests.md)
- [Releasing a New Version](./docs/releasing.md)
