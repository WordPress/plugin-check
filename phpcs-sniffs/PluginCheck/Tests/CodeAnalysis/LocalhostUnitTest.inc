<?php

$var            = 'This file contains https://127.0.0.1/example url.'; // Error.
$sample_url     = 'Sample URL is http://docker.local/example here.'; // Error.
$custom_url     = 'Custom URL https://docker.localhost/example here.'; // Error.
$custom_content = file_get_contents("http://127.0.0.1/api.php?url=".$url); // Error.

wp_register_script( 'custom-js', 'https://127.0.0.1/dist/custom.js' ); // Error.
wp_enqueue_script( 'custom-js', 'https://127.0.0.1/dist/custom.js' ); // Error.
wp_register_style( 'custom-css', 'http://localhost/dist/custom.css' ); // Error.
wp_enqueue_style( 'custom-css', 'http://localhost/dist/custom.css' ); // Error.

$important_links = array(
  'view-pro'      => array(
    'link' => esc_url( 'https://staging.local/resources' ), // Error.
  ),
);

// This file contains https://localhost/test-plugin-localhost-with-errors url. Good.

/*
 * Another comment https://localhost/test-plugin-localhost-with-errors URL.
 */

$multiple_urls = 'Custom URL https://docker.localhost/example and https://localhost/example.php here.'; // Error.
?>

<img src="<?php echo "http://localhost/image.png"; ?>" alt="" /><!-- Error. -->

<p>URL in inline HTML is http://localhost/example2.php bad.</p><!-- Error. -->
