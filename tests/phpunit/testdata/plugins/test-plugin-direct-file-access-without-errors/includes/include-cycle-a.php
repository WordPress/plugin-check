<?php
/**
 * File in an include cycle with only classes - safe for direct access.
 */

require_once __DIR__ . '/include-cycle-b.php';

/**
 * Test class.
 */
class Include_Cycle_A {
}
