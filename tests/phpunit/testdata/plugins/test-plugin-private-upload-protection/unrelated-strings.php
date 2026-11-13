<?php

wp_upload_dir();
$filename = '.htaccess';
$help = 'Deny from all';
file_put_contents( 'readme.txt', $help );
