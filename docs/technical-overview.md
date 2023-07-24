[Back to overview](./README.md)

# Technical Overview

The Plugin Checker works by executing checks against a single plugin. Each check will test for a specific issue and raise either an error or a warning depending on the severity.

The Plugin Checker performs these checks by following the process below:

1. Determine which checks to run based on the request.
2. Execute necessary check preparations to set up the environment.
3. Execute a comprehensive set of checks against the plugin.
4. Ensure all errors and warnings raised during the checks are reported.


## Checks
Checks are individual PHP classes designed to test a plugin for a specific problem when executed.

Each check contains a `run()` method that accepts an instance of the `Check_Result` class. This `Check_Result` instance is used to collect and report any errors or warnings raised by the check.

The Plugin Checker currently supports two types of checks: static and runtime checks.

### Static Checks

Static checks are used to perform tests against the codebase without running any code within the plugin being checked, similar to other static analysis tools like PHP Code Sniffer.

Static checks can utilize existing PHPCodeSniffer sniffs, such as those in the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards), or they can be implemented manually by searching for specific patterns across all files, similar to the checks in the [WordPress Theme Check tool](https://github.com/WordPress/theme-check).

Static checks implement the `Static_Check` interface.

### Runtime Checks

Runtime checks perform tests by executing the plugin code within a dedicated test environment, allowing for verification of specific outputs.

Runtime checks execute code within a test environment, often requiring preparations to configure the runtime environment with the necessary configuration prior to performing the checks.

Runtime checks implement the `Runtime_Check` interface.

### Check Stabilities

Each check is assigned a stability value to ensure appropriate check selection based on specific use cases. Currently, there are two stability values: Stable and Experimental.

Checks are assigned a stability value using either the `Stable_Check` or `Experimental_Check` trait. Those with the `Stable_Check` trait are always executed on both the CLI and WordPress admin screens, whereas Experimental checks are exclusively run via the CLI using the `--include-experimental` flag.

### Check Categories

Check categories enable the execution of checks for specific use cases. Each check requires a category, which is determined by implementing a `get_categories` method within the check class. This method returns an array containing one or more categories to which the check belongs. The categories should be specified using the constants found in the `Check_Categories` class, rather than setting them as strings.

It should be noted that if categories are assigned that are _not_ part of the constants found in the `Check_Categories` class, said checks may not appear in the dashboard (unless they also contain other categories that _are_ defined in the `Check_Categories` class as constants). They will, however, be able to be surfaced via the wp-cli method of running checks, where checks assigned to any category (existing or custom) will be able to be surfaced and filtered via applicable cli arguments. 

### Preparations

Preparations are utilized to set up the test environment before running a runtime check, ensuring their successful execution.

Preparations can encompass various tasks, such as adding filters or creating test content, which are performed to enable comprehensive checks.

## The Test Environment

In the context of runtime checks, the Plugin Checker ensures that the checks are executed within a controlled environment separate from the production site, thus preventing any unintended changes to the actual WordPress site.

To achieve this, the Plugin Checker implements the following practices:

* Separate Database Tables: During runtime checks, the Plugin Checker utilizes a distinct set of database tables. This isolation ensures that the checks do not interfere with the data of the production site.

* Restricted Plugin Activation: In the runtime environment, only the plugin being tested is activated. By deactivating other plugins, the checks focus solely on the plugin under examination.

It is important to note that while these measures aim to minimize the impact on the WordPress site, it is strongly advised not to perform runtime checks using the Plugin Checker on a production site. Despite the precautions taken, there remains a possibility of unintended consequences or conflicts.
