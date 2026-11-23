<?php

$directory = wp_upload_dir();
file_put_contents( $directory['basedir'] . '/.htaccess', 'Options -Indexes' );
