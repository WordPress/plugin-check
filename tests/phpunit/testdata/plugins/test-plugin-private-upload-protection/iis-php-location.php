<?php

wp_upload_dir();
file_put_contents( 'web.config', '<location path="script.php"><authorization><deny users="*" /></authorization></location>' );
