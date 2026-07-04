<?php
// File with write operations to plugin directory.

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

// Error: Using plugins_url.
file_put_contents( plugins_url() . '/test-plugin/data.json', '{}' );

// Error: Variable assigned from plugin_dir_path().
$plugin_path = plugin_dir_path( __FILE__ ) . 'cache/indirect.txt';
file_put_contents( $plugin_path, 'Some data' );
