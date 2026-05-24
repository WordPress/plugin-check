<?php

namespace VariableAnalysis\Lib;

/**
 * Holds details of an enum.
 */
class EnumInfo
{
	/**
	 * The position of the `enum` token.
	 *
	 * @var int
	 */
	public $enumIndex;

	/**
	 * The position of the block opener (curly brace) for the enum.
	 *
	 * @var int
	 */
	public $blockStart;

	/**
	 * The position of the block closer (curly brace) for the enum.
	 *
	 * @var int
	 */
	public $blockEnd;

	/**
	 * @param int $enumIndex
	 * @param int $blockStart
	 * @param int $blockEnd
	 */
	public function __construct(
		$enumIndex,
		$blockStart,
		$blockEnd
	) {
		$this->enumIndex = $enumIndex;
		$this->blockStart = $blockStart;
		$this->blockEnd = $blockEnd;
	}
}
