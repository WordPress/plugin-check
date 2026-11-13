<?php

wp_upload_dir();
file_put_contents( '.htaccess', '<Files secret.php>
Require all denied
</Files>' );
