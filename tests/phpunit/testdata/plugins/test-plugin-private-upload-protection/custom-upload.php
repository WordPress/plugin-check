<?php

move_uploaded_file( $tmp, $path );
file_put_contents( '/private/.htaccess', 'Deny from all' );
