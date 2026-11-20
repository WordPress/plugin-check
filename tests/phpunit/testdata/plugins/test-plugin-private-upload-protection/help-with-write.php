<?php

wp_upload_dir();
__( 'Deny from all' );
file_put_contents( '.htaccess', 'Options -Indexes' );
