<?php
/**
 * Plugin Name: Test Plugin Image Functions check with errors
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Test plugin for the Image Functions check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-image-functions-with-errors
 *
 * @package test-plugin-image-functions-with-errors
 */

?>

<img src="https://example.com/image.jpeg" />

<?php

echo '<img src="https://example.com/image.jpeg" />';
