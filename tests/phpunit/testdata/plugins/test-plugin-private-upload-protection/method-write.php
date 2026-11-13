<?php

wp_upload_dir();
$writer->file_put_contents( '.htaccess', 'Deny from all' );
