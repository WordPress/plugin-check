<?php

$dir = wp_get_upload_dir();
file_put_contents( $dir['basedir'] . '/private/.htaccess', 'Deny from all' );
