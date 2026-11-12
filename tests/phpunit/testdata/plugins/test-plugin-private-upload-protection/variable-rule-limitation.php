<?php

wp_upload_dir();
$rule = 'Deny from all';
file_put_contents( '.htaccess', $rule );
