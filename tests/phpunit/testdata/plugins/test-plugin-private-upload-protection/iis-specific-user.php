<?php

wp_upload_dir();
file_put_contents( 'web.config', '<authorization><deny users="someone" /></authorization>' );
