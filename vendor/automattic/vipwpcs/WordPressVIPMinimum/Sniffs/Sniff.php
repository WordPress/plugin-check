<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressVIPMinimum\Sniffs;

use WordPressCS\WordPress\Sniff as WPCS_Sniff;

/**
 * Represents a WordPress\Sniff for sniffing VIP coding standards.
 *
 * Provides a bootstrap for the sniffs, to reduce code duplication.
 */
abstract class Sniff extends WPCS_Sniff {
}
