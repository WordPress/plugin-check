<?php

wp_upload_dir();
file_put_contents( '.htaccess.bak', 'Deny from all' );
