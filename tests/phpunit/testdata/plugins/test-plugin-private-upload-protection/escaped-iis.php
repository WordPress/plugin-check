<?php

$directory = wp_upload_dir();
file_put_contents( $directory['basedir'] . '/private/web.config', "<authorization><deny users=\"*\" /></authorization>" );
