<?php

wp_upload_dir();
file_put_contents( '.htaccess', "# Deny from all\n  # Require all denied\n" );
