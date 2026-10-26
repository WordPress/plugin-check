<?php

$directory = wp_upload_dir();
file_put_contents( $directory['basedir'] . '/private/web.config', '<configuration><system.webServer><security><authorization><deny users="*" /></authorization></security></system.webServer></configuration>' );
