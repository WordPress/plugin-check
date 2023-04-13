# Introduction

The Plugin Checker is a WordPress plugin that can check a plugin for potential problems around plugin development best practices, with a focus on performance and security.

## Technical Overview

The Plugin Checker works by running checks against a single plugin. Each check will test for a specific issue and raise either an error or warning depending on the severity.

The Plugin Checker performs these checks following the process below.

1. Determine Checks to run based on the request.
2. Setup the Runtime Environment if required.
3. Run any Check Preparations required to prepare the environment.
4. Run all Checks against the plugin.
5. Clean up the Runtime Environment.
6. Report all Errors and Warnings raised by the checks run.


## Checks
Checks are single PHP classes that run code to test a plugin for a specific problem.

Every Check will contain a `run()` method which accepts an instance of the `Check_Results` class. The `Check_Results` object is used to add any errors or warnings raised by the Check to be reported later.

Currently, static and runtime checks are the 2 types of checks that can be run by the Plugin Checker.

### Static Checks

Static checks are used to perform tests against the codebase without running any code. This is simillar to other static analysis tools such as PHP Code Sniffer.

Static checks are one of the simplest checks to implement and can be used to run existing code standards sniffs, such as those in the WordPress Coding Standards, against a plugin as part of the Plugin Checker test process.

### Runtime Checks

Runtime checks perform tests by executing the code within a test environment. The plugin code is required to run in order to test a specific output.

As runtime checks execute code against a test environment they often include preparations in order to prepare the runtime environment with required configuration ahead of check being performed.

### Preparations

Preparations are used to prepare the test environment ahead of running a check to ensure that the tests run correctly.

Preparations can include any logic from activating specific themes or plugins, or creating test content to perform checks against.

Preparations can be created as part of the check where the logic contained within the Checks `prepare()` method. Alternatively, preparations can also be created as individual classes so that they may be reusued within other checks.

All preparations return a clean up function which is called after the check run is complete to reverse the changes made by the preparation.

## The Test Environment

When running a Runtime Check the Plugin Checker creates a Runtime Environment in which to perform checks against. This environment is separate from the production environment in order to prevent making changes to the existing site.

The environment is also configured with a minimal setup by using a basic theme and only activating the test plugin. This is to ensure checks only raise errors and warnings for the plugin being tested.


## Creating custom checks

Custom checks can be created to perform specific tests as part of the Plugin Checker process. Custom checks can be contributed to the main repository or created outside of the main repo and added via the `wp_plugin_check_checks` filter.
