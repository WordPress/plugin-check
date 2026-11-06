<?php
/**
 * File with a class requiring a dynamic path - should be flagged.
 */

require_once __DIR__ . '/' . $_GET['file'];

/**
 * Test class.
 */
class Require_Dynamic_Path {
}
