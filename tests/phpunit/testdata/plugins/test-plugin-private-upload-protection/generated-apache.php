<?php

$directory = wp_upload_dir();
file_put_contents( $directory['basedir'] . '/private/.htaccess', 'Require all denied' );
