# Creating a Runtime Check

Runtime checks involve executing the plugin's code to perform tests.

The Plugin Checker utilizes various interfaces to construct runtime checks.

The Plugin Checker provides the `Runtime_Check` interface, designed to identify a runtime check. While this interface does not contain any methods, it serves as a marker for runtime checks.

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

In addition, the Plugin Checker includes the `Preparation` interface, allowing a check to prepare the WordPress environment. This interface defines a `prepare()` method used to execute the necessary logic before the check is run.

Both of these interfaces, `Runtime_Check` and `Preparation`, are implemented within the `Abstract_Runtime_Check` class, providing developers with a foundation for building their own runtime checks. This class defines the `prepare()` and `run()` methods, which are required to be implemented for every runtime check.

Below is the basic framework for creating a custom runtime check.

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

In the Plugin Checker, preparations are employed to set up the environment before executing checks. These preparations involve implementing logic such as adding actions and filters, and creating the necessary test content. They utilize the `Preparation` interface, which mandates the implementation of a `prepare()` method and requires returning cleanup functions to revert changes made during preparation.

Based on the circumstances, preparations can be created using three different approaches:

* Check preparations
* Shared preparations
* Global preparations

### Check preparations

Check preparations are created by defining the `prepare()` method within a check class.

This type of preparation is valuable for executing the logic necessary to prepare the environment specifically tailored to the check that implements it.

Below is an example of a preparation that generates a test post and includes a cleanup function - which is returned as part of the `prepare()` method - to delete the post after the check is executed.

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

Shared preparations are individual classes that consolidate specific preparation logic in a single, centralized location.

The [`Demo_Posts_Creation_Preparation`](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations/Demo_Posts_Creation_Preparation.php) in the Plugin Checker is an example of a preparation class.

Preparation classes are beneficial for implementing the same preparation logic across multiple checks, leveraging the concept of shared preparations.

Shared preparations are used to avoid redundant execution of the same preparations.

Before executing checks against a plugin, all shared preparations are gathered and processed to prevent redundant execution of identical shared preparations.

Any preparation class can be used as a shared preparation including [preparations already available in the Plugin Checker](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations).

Check classes can define and utilize shared preparations by implementing the `With_Shared_Preparation`interface.

The Check class should then implement the `get_shared_preparations()` method as defined by the interface. This method returns a map of shared preparations, with the preparation class name as the key and an array of constructor parameters as the value.

Below is an example of how the `Demo_Posts_Creation_Preparation` preparation class can be used to generate demo posts for every viewable post type, which are then removed after the checks are executed.

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
    array_filter( get_post_types(), 'is_post_type_viewable' )
  );

  return array(
    Demo_Posts_Creation_Preparation::class => array( $demo_posts ),
  );
}
```

A check can combine both a check preparation and shared preparations. It's worth noting that when a check implements both types, shared preparations are executed before check preparations.

## Global preparations

Plugin Checker also includes some preparation classes designed to prepare the overall environment, i.e., before any runtime check. At this point, global preparations cannot be controlled outside of the Plugin Checker.

An example of a global preparation is the [`Force_Single_Plugin_Preparation`](https://github.com/10up/plugin-check/blob/trunk/includes/Checker/Preparations/Force_Single_Plugin_Preparation.php) class, which ensures that only the plugin being checked is active.

## Amending the check result object

Amending results within the `Check_Result` object functions similarly to static checks. For more information on adding results, [refer to the documentation available here](./creating-a-static-check.md#amending-the-check-result-object).

## Add the Check to the Plugin Checker

[See here](./creating-a-static-check.md#add-the-check-to-the-plugin-checker) for details on how to add a check to the Plugin Checker.
