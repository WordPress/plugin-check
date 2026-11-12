<?php

wp_upload_dir();
file_put_contents( '.htaccess', '<LimitExcept POST>
Require all denied
</LimitExcept>' );
