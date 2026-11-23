<?php

$upload = wp_upload_dir( ... );
file_put_contents( '.htaccess', 'Deny from all' );
