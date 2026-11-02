<?php
/**
 * File with a class requiring a file with procedural code - should be flagged.
 */

require_once __DIR__ . '/../file-without-guard.php';

/**
 * Test class.
 */
class Require_Procedural_File {
}
