<?php

wp_upload_dir();
file_put_contents( 'web.config', '<authorization><add accessType="Deny" users="*" /></authorization>' );
