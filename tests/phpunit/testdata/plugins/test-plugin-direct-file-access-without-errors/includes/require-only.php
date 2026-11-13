<?php
/**
 * File with only require statements of safe files - safe for direct access.
 */

require_once __DIR__ . '/class-parent.php';
require_once dirname( __FILE__ ) . '/interface-only-file.php';
require_once dirname( __FILE__, 2 ) . '/file-with-abspath-guard.php';
