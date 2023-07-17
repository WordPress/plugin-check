# Creating a Runtime Check

Runtime checks are used to perform tests by executing the plugin's code.

The Plugin Checker uses a number of interfaces to build out runtime checks.

The Plugin Checker provides the `Runtime_Check` interface, which is used to identify a runtime check. This interface does not contain any methods but serves as a marker for runtime checks.

```php
use WordPress\Plugin_Check\Checker\Check_Result;

class Custom_Check extends Runtime_Check {
  public function run( Check_Result $result );
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

In addtion, there is also the `Preparation` interface, which is used to define checks that have preparations. This interface defines a `prepare()` method which is used to run the logic required to prepare the environment before the check before is run.

Both these interfaces are implemented in the `Abstract_Runtime_Check` class, which developers can use when building out their own runtime checks. This class defines both the `prepare()` and `run()` methods which are required to be implemented for every runtime check.

Below is the basic scaffold when creating a custom runtime check.

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;

class Custom_Check extends Abstract_Runtime_Check {

  use Stable_Check;
  
	public function get_categories() {
		// Return an array of check categories.
	}

  public function prepare() {
    // Handle the Checks preparations and return a cleanup function.
  }

  public function run( Check_Result $result ) {
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

## Preparations

Preparations in the Plugin Checker are used to set up the environment before running checks, involving logic like activating themes or plugins and creating necessary test content. They utilize the `Preparation` interface, requiring the implementation of a `prepare()` method, and return cleanup functions to revert changes made during preparation. Three different approaches can be used for creating preparations based on the circumstances.

### Check Preperations

Check preparations are created using the `prepare()` method within a check class.

This type of preparation is useful for carrying out the logic to prepare the environment that is specific to the check that implements it.

Below is an example of a preparation that creates a test post and returns a cleanup function that deletes that post after the check is run.

```php
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;

class Custom_Check extends Abstract_Runtime_Check {
  /**
   * Check Preparation.
   */
  public function prepare() {
    // Create a test post ahead of running the check.
    $post_id = wp_insert_post(
      array(
        'post_title'   => 'Test post',
        'post_content' => 'This is a test post.',
        'post_type'    => 'post',
      )
    );

    // Return the clean up function.
    return function() use ( $post_id ) {
      // Remove the test post created.
      wp_delete_post( $post_id );
    }
  }
}
```

### Preparation Classes

Preparation classes are individual classes created to handle specific preparation logic in a single place.

The [`Force_Single_Plugin_Preparation`](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations/Force_Single_Plugin_Preparation.php) in the Plugin Checker is an example of a preparation class.

Preparation classes are useful for implementing the same preparation logic across multiple checks using the concept of shared preparations.

### Shared Preparations

Shared preparations are used to prevent running the same preparations multiple times.

Before running checks against a plugin all shared preparations are collected and processed to remove any shared preparations that are the same. 

Check classes can define the shared preparations the use by using the `With_Shared_Preparation` interface.

The Check class should then implement the get_shared_preparations() method defined by the interface. This method returns an map of shared preparations where the preparation class name is the key and an array of constructor parameters as the value.

Below is an example of how the `Enqueued_Scripts_Size_Check` uses shared preparations.

```php
  /**
   * Returns an array of shared preparations for the check.
   *
   * @return array Returns a map of $class_name => $constructor_args pairs. If the class does not
   *               need any constructor arguments, it would just be an empty array.
   */
  public function get_shared_preparations() {
    $demo_posts = array_map(
      function( $post_type ) {
        return array(
          'post_title'   => "Demo {$post_type} post",
          'post_content' => 'Test content',
          'post_type'    => $post_type,
          'post_status'  => 'publish',
        );
      },
      $this->viewable_post_types
    );

    return array(
      Demo_Posts_Creation_Preparation::class => array( $demo_posts ),
    );
  }
```

## Add the Check to the Plugin Checker

In order to run the check as part of the Plugin Checker process it needs to be added to the Plugin Checkers list of available checks.

This is done by using the `wp_plugin_check_checks` filter to register an instance of the check with its slug.

- If you're contributing to the actual plugin checker, add the check to the list in the `Abstract_Check_Runner::register_checks()` method.
- If you're implementing a check in code outside of the actual plugin checker, use the approach you've described here so far.

```php
add_filter(
  'wp_plugin_check_checks',
  function ( array $checks ) {
    // Add the check to the map of all available checks.
    $checks[ 'runtime_check' ] = new Runtime_Check();

    return $checks;
  }
)
```

# Amending the check result object

The checks `run()` method will hold all the logic to test the plugin and raise any warnings or errors that are found.

The run method accepts an instance of the `Check_Results` class which is used to add messages to the results list.

The warnings and errors are added via the `add_message()` method which accepts 3 parameters.

- `$error (bool)` - Whether the message is an error or warning. True for error, false for warning.
- `$message (string)` - The error/warning message.
- `$args (array)` - Additional message arguements to add context.
  - `$code (string)` - Violation code according to the message. Default empty string.
  - `$file (string)` - The file in which the message occurred. Default empty string (unknown file).
  - `$line (int)` - The line on which the message occurred. Default 0 (unknown line).
  - `$column (int)` - The column on which the message occurred. Default 0 (unknown column).

In addition to adding messages, the `Check_Result` instance also provides access to the `Plugin_Context`. The plugin context is useful for getting the plugins path and urls when performing checks.

Below is an example showing how to access the plugin context and add messages using the `Check_Results` instance.

```php
/**
 * Runs the check on the plugin and amends results.
 *
 * @since n.e.x.t
 *
 * @param Check_Result $result The check results to amend and the plugin context.
 */
public function run( Check_Result $result ) {

  // Get the absolute file path for a specific file in the plugin.
  $plugin_file = $result->plugin()->path( 'plugin-file.php' );

  // Run logic to test the plugin for warnings/errors...

  // When an issue is found add a warning.
  $result->add_message(
    false, 
    'Warning message content.', 
    array(
      'code'   => 'warning_code',
      'file'   => $pluging_file,
      'line'   => 1,
      'column' => 10,
    )
  );
}
```
