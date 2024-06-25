[Back to overview](./README.md)

# Technical Overview

To ensure that all Checks are working correctly, the Team has developed Unit test to run the checks whether it pass or fail, to ensure the consistency of the rest of checks and functions. The tests are based on [wp-env](https://make.wordpress.org/core/2020/03/03/wp-env-simple-local-environments-for-wordpress/), so you could run a clean installation to run all tests.

Follow these instructions to configure and run tests:

1. You would need npm installed in your computer.
2. Ensure that you have installed all npm dependencies with `npm ci`.
3. You need to have Docker installed and running wp-env environment `npm run wp-env start`.
4. Run tests with npm command `npm run test-php`.

If it does not fail, your Pull request is ready to be committed to the repository.

## Where to find folder tests

We have this structure for the tests folder:

- tests/behat
- tests/phpstan
- tests/phpunit Unit tests for the plugin.
  - tests/phpunit/tests/Checker/Checks All tests to run inside the plugin.
  - tests/phpunit/testdata/plugins You will find all data required to run unit tests, organized by folders.

Ensure that you create your Check and test unit at same time.
