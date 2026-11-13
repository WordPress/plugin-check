<?php
/**
 * Section class.
 *
 * @package Plugin_Check
 */

namespace WordPress\Plugin_Check\SVN;

use JsonSerializable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Section.
 *
 * A named group of checks within a report.
 *
 * @since 2.1.0
 */
class Section implements JsonSerializable {

	/**
	 * Machine-readable section ID.
	 *
	 * @var string
	 */
	public string $id;

	/**
	 * Human-readable section label.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * Checks in this section.
	 *
	 * @var array<int, array{key: string, label: string, status: string, detail: string}>
	 */
	private array $checks = array();

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 *
	 * @param string $id    Section ID.
	 * @param string $label Section label.
	 */
	public function __construct( string $id, string $label ) {
		$this->id    = $id;
		$this->label = $label;
	}

	/**
	 * Add a check.
	 *
	 * @since 2.1.0
	 *
	 * @param string $key    Machine-readable, untranslated check identifier.
	 * @param string $label  Translated check label.
	 * @param string $status pass|warn|fail|info.
	 * @param string $detail Translated detail text.
	 */
	public function add_check( string $key, string $label, string $status, string $detail = '' ): void {
		$this->checks[] = array(
			'key'    => $key,
			'label'  => $label,
			'status' => $status,
			'detail' => $detail,
		);
	}

	/**
	 * Return checks in this section.
	 *
	 * @since 2.1.0
	 *
	 * @return array<int, array{key: string, label: string, status: string, detail: string}>
	 */
	public function get_checks(): array {
		return $this->checks;
	}

	/**
	 * Serialize to JSON.
	 *
	 * @since 2.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return array(
			'id'     => $this->id,
			'label'  => $this->label,
			'checks' => $this->checks,
		);
	}
}
