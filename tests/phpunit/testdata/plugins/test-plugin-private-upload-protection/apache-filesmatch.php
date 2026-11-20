<?php

wp_upload_dir();
file_put_contents( '.htaccess', '<FilesMatch "\.php$">
Deny from all
</FilesMatch>' );
