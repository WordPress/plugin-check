<?php

wp_upload_dir();
file_put_contents( '.htaccess', '<Limit GET>
Deny from all
</Limit>' );
