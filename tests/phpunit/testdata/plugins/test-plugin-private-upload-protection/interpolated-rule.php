<?php

wp_upload_dir();
file_put_contents( '.htaccess', "# $comment\nDeny from all" );
