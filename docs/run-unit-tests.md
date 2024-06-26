[Back to overview](./README.md)

# Technical Overview

To ensure that all checks are working correctly, the Team has developed Unit test to run the checks whether it pass or fail, to ensure the consistency of the rest of checks and functions. The tests are based on [wp-env](https://make.wordpress.org/core/2020/03/03/wp-env-simple-local-environments-for-wordpress/), so you could run a clean installation to run all tests.

Follow these instructions to configure and run tests:

1. You would need npm installed in your computer.
2. Ensure that you have installed all npm dependencies with `npm ci`.
3. You need to have Docker installed and running wp-env environment `npm run wp-env start`.
4. Run tests with npm command `npm run test-php`.

The full test suite is run against PRs as a GitHub action ( [example](https://github.com/WordPress/plugin-check/actions/runs/9660204610) ) so tests can be run against all supported environments. Making sure those pass are the important thing. Being able to run them locally is meant to help developers while working on or debugging tests, prior to submitting their code for review.

## Where to find folder tests

We have this structure for the tests folder:

- `tests/phpunit` Unit tests for the plugin.
  - `tests/phpunit/tests/` All PHPUnit tests that run as part of the suite.  
  - `tests/phpunit/testdata/` Example classes or plugins that can be used in tests.  
  - `tests/phpunit/utils/` Shared helpers that can be used across different test classes.  

Ensure that you create your Check and test unit at same time.
