<?php

\wp_upload_dir();
\file_put_contents( '/private/.htaccess', 'Deny from all' );
