# Creating a Runtime Check

Runtime checks are used to perform tests by executing the plugin's code.

The Plugin Checker uses a number of interfaces to build out runtime checks.

The Plugin Checker provides the `Runtime_Check` interface, which is used to identify a runtime check. This interface does not contain any methods but serves as a marker for runtime checks.

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class My_Custom_Check implements Runtime_Check {

  use Stable_Check;

  public function get_categories() {
    // Return an array of check categories.
    // See the `WordPress\Plugin_Check\Checker\Check_Categories` class for available categories.
  }

  public function run( Check_Result $result ) {
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

In addition, there is also the `Preparation` interface, which can be implemented by a check to prepare the WordPress environment. This interface defines a `prepare()` method which is used to run the logic required to prepare the environment before the check before is run.

Both these interfaces are implemented in the `Abstract_Runtime_Check` class, which developers can use when building out their own runtime checks. This class defines both the `prepare()` and `run()` methods which are required to be implemented for every runtime check.

Below is the basic scaffold when creating a custom runtime check.

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class My_Custom_Check extends Abstract_Runtime_Check {

  use Stable_Check;
  
  public function get_categories() {
    // Return an array of check categories.
    // See the `WordPress\Plugin_Check\Checker\Check_Categories` class for available categories.
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

Preparations in the Plugin Checker are used to set up the environment before running checks, involving logic like adding actions and filters, and creating necessary test content. They utilize the `Preparation` interface, requiring the implementation of a `prepare()` method, and return cleanup functions to revert changes made during preparation.
Three different approaches can be used for creating preparations based on the circumstances:

* Check preparations
* Shared preparations
* Global preparations

### Check preparations

Check preparations are created using the `prepare()` method within a check class.

This type of preparation is useful for carrying out the logic to prepare the environment that is specific to the check that implements it.

Below is an example of a preparation that creates a test post and returns a cleanup function that deletes that post after the check is run.

```php
use WordPress\Plugin_Check\Checker\Checks\Abstract_Runtime_Check;

class My_Custom_Check extends Abstract_Runtime_Check {

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

### Shared Preparations

Shared preparations are individual classes created to handle specific preparation logic in a single place.

The [`Demo_Posts_Creation_Preparation`](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations/Demo_Posts_Creation_Preparation.php) in the Plugin Checker is an example of a preparation class.

Preparation classes are useful for implementing the same preparation logic across multiple checks using the concept of shared preparations.

Shared preparations are used to prevent running the same preparations multiple times.

Before running checks against a plugin all shared preparations are collected and processed to avoid duplicate execution of any shared preparations that are the same. 

Any preparation class can be used as a shared preparation including [preparations already available in the Plugin Checker](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations).

Check classes can define the shared preparations the use by using the `With_Shared_Preparation` interface.

The Check class should then implement the `get_shared_preparations()` method defined by the interface. This method returns an map of shared preparations where the preparation class name is the key and an array of constructor parameters as the value.

Below is an example of how the `Demo_Posts_Creation_Preparation` preparation class can be used to generate a demo post for every viewable post type, which are then removed again after running the checks.

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

A check can have both a check preparation and a shared preparations combined. When a check implements both types of checks it is worth noting that shared preparations are run before check preparations.

## Global preparations

Plugin Checker also includes some preparation classes that are used to prepare the overall environment, i.e. before any runtime check. Global preparations cannot be controlled outside of the Plugin Checker at this point.

An example for a global preparation is the [`Force_Single_Plugin_Preparation`](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations/Force_Single_Plugin_Preparation.php) class, which ensures that only the plugin to check is actually active.

## Amending the check result object

Amending results to the `Check_Result` object works in the same way as static checks. [See the documentation here](./creating-a-static-check.md#amending-the-check-result-object) for details on adding results.

## Add the Check to the Plugin Checker

[See here](./creating-a-static-check.md#add-the-check-to-the-plugin-checker) for details on how to add a check to the Plugin Checker.
