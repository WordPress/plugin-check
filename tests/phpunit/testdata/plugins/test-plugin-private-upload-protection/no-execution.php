<?php

wp_upload_dir();
throw new \RuntimeException( 'This fixture must not execute.' );
file_put_contents( '.htaccess', 'Deny from all' );
