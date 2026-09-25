<?php
/**
 * File with require_once and class - safe for direct access.
 */

require_once __DIR__ . '/class-parent.php';

/**
 * Test class.
 */
class My_Class extends Parent_Class {
	/**
	 * Property.
	 *
	 * @var string
	 */
	private $property = 'test';
}
