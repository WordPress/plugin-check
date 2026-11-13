<?php
/**
 * File in an include cycle with only classes - safe for direct access.
 */

require_once __DIR__ . '/include-cycle-a.php';

/**
 * Test class.
 */
class Include_Cycle_B {
}
