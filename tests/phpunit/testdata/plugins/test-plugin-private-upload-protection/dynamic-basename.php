<?php

wp_upload_dir();
file_put_contents( $prefix . '.htaccess', 'Deny from all' );
