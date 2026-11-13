<?php

$directory = wp_upload_dir();
file_put_contents( $directory['basedir'] . '/private/web.config', '<configuration><system.web><authorization><deny users="?" /></authorization></system.web></configuration>' );
