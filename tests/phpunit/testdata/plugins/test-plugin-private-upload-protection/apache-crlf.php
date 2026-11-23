<?php

wp_upload_dir();
file_put_contents( '.htaccess', "Order allow,deny\r\n  dEnY FROM all # private\r\n" );
