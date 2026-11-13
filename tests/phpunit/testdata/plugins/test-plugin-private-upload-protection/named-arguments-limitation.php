<?php

wp_upload_dir();
file_put_contents( filename: '.htaccess', data: 'Deny from all' );
