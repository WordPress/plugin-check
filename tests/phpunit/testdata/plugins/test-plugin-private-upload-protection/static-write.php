<?php

wp_upload_dir();
Writer::file_put_contents( '.htaccess', 'Deny from all' );
