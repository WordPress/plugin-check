<?php

wp_upload_dir();
file_put_contents( '.htaccess', $text . 'Deny from all' );
