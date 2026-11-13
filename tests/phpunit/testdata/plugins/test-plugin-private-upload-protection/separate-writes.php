<?php

wp_upload_dir();
file_put_contents( '.htaccess', 'Options -Indexes' );
file_put_contents( 'notes.txt', 'Deny from all' );
