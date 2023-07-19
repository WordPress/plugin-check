# Technical Overview

The Plugin Checker works by running checks against a single plugin. Each check will test for a specific issue and raise either an error or warning depending on the severity.

The Plugin Checker performs these checks following the process below.

1. Determine checks to run based on the request.
2. Run any check preparations required to prepare the environment.
3. Run all checks against the plugin.
4. Report all errors and warnings raised by the checks run.


## Checks
Checks are single PHP classes that run code to test a plugin for a specific problem.

Every check contains a `run()` method which accepts an instance of the `Check_Result` class. The `Check_Result` instance is used to add any errors or warnings raised by the check to be reported later.

Currently, static and runtime checks are the 2 types of checks that can be run by the Plugin Checker.

### Static Checks

Static checks are used to perform tests against the codebase without running any code. This is similar to other static analysis tools such as PHP Code Sniffer.

Static checks can run existing PHPCodeSniffer sniffs, such as those in the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards), or they can be implemented in a more manual way, e.g. searching for specific patterns across all files, similar to how the checks of the [WordPress Theme Check tool](https://github.com/WordPress/theme-check) are implemented.

Static checks implement the `Static_Check` interface.

### Runtime Checks

Runtime checks perform tests by executing the code within a test environment. The plugin code is required to run in order to test a specific output.

As runtime checks execute code against a test environment they often include preparations in order to prepare the runtime environment with required configuration ahead of check being performed.

Runtime checks implement the `Runtime_Check` interface.

### Check Stabilities

Every check is assigned a stability value so the correct checks are run for each use case. There are currently 2 stability values, Stable and Experimental.

Checks are a assigned a stability value using either the `Stable_Check` or `Experimental_Check` trait. Checks with the `Stable_Check` trait are always run by both the CLI and WordPress admin screens. Experimental checks are only run via the CLI with the `--include-experimental` flag.

### Check Categories

Check categories allow checks to be run for specific use cases. All checks require a category which is set by implementing a `get_categories` method within the check class. This method returns an array containing one or more categories the check belongs to. The categories set should be made up by the constants found in the `Check_Categories` class rather than setting them as strings.

### Preparations

Preparations are used to prepare the test environment ahead of running a runtime check to ensure that they run correctly.

Preparations can include any logic from adding filters, or creating test content to perform checks against.

## The Test Environment

In the context of runtime checks, the Plugin Checker ensures that the checks are performed in a controlled environment separate from the production site. This approach prevents any unintended changes to the actual WordPress site.

To achieve this, the Plugin Checker employs the following practices:

* Separate Database Tables: During runtime checks, a distinct set of database tables is utilized. This isolation ensures that the checks do not interfere with the data of the production site.

* Restricted Plugin Activation: Only the plugin being tested is activated in the runtime environment. By keeping other plugins deactivated, the checks focus solely on the plugin under examination.

It's important to note that while these measures aim to minimize the impact on the WordPress site, it is strongly advised not to perform runtime checks using the Plugin Checker on a production site. Despite the precautions taken, there is still a possibility of unintended consequences or conflicts.
