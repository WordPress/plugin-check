<?php
// Test file for WriteFileSniff.

// Error: Writing to plugin directory using __FILE__.
file_put_contents( dirname( __FILE__ ) . '/cache/data.txt', 'Some data' );

// Error: Writing to plugin directory using __DIR__.
fwrite( fopen( __DIR__ . '/logs/debug.log', 'w' ), 'Debug info' );

// Error: Writing using plugin_dir_path().
fputs( fopen( plugin_dir_path( __FILE__ ) . 'error.log', 'a' ), 'Error message' );

// Error: Copying to plugin directory using WP_PLUGIN_DIR.
copy( '/tmp/file.txt', WP_PLUGIN_DIR . '/test-plugin/backup.txt' );

// Error: Moving files to plugin directory.
rename( '/tmp/temp.txt', plugin_dir_path( __FILE__ ) . 'moved.txt' );

// Error: Using WP_CONTENT_DIR.
touch( WP_CONTENT_DIR . '/custom-data.txt' );

// Error: Using plugins_url (should error).
file_put_contents( plugins_url() . '/test-plugin/data.json', '{}' );

// Error: Variable assigned from plugin_dir_path.
$plugin_path = plugin_dir_path( __FILE__ ) . 'cache/indirect.txt';
file_put_contents( $plugin_path, 'Some data' );

// Error: Safe helper combined with plugin directory path.
file_put_contents( plugin_dir_path( __FILE__ ) . wp_upload_dir()['subdir'] . '/mixed.txt', 'Some data' );

// OK: Writing to uploads directory (no error).
file_put_contents( wp_upload_dir()['basedir'] . '/my-plugin/data.txt', 'Some data' );

// OK: Using temporary directory (no error).
fwrite( fopen( wp_tempnam( 'my-plugin-' ), 'w' ), 'Temporary data' );

// OK: Using get_temp_dir (no error).
touch( get_temp_dir() . 'my-temp-file.txt' );

// OK: Using WP_CONTENT_DIR uploads subpath (no error).
file_put_contents( WP_CONTENT_DIR . '/uploads/my-plugin/data.txt', 'Some data' );
