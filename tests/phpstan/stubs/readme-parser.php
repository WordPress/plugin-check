<?php

namespace WordPressdotorg\Plugin_Directory\Readme;

class Parser {
	/**
	 * @var string
	 */
	public $name = '';
	/**
	 * @var array
	 */
	public $tags = array();
	/**
	 * @var string
	 */
	public $requires = '';
	/**
	 * @var string
	 */
	public $tested = '';
	/**
	 * @var string
	 */
	public $requires_php = '';
	/**
	 * @var array
	 */
	public $contributors = array();
	/**
	 * @var string
	 */
	public $stable_tag = '';
	/**
	 * @var string
	 */
	public $donate_link = '';
	/**
	 * @var string
	 */
	public $short_description = '';
	/**
	 * @var string
	 */
	public $license = '';
	/**
	 * @var string
	 */
	public $license_uri = '';
	/**
	 * @var array
	 */
	public $sections = array();
	/**
	 * @var array
	 */
	public $upgrade_notice = array();
	/**
	 * @var array
	 */
	public $screenshots = array();
	/**
	 * @var array
	 */
	public $faq = array();
	/**
	 * Warning flags which indicate specific parsing failures have occurred.
	 *
	 * @var array
	 */
	public $warnings = array();
	/**
	 * These are the readme sections that we expect.
	 *
	 * @var array
	 */
	public $expected_sections = array('description', 'installation', 'faq', 'screenshots', 'changelog', 'upgrade_notice', 'other_notes');
	/**
	 * We alias these sections, from => to
	 *
	 * @var array
	 */
	public $alias_sections = array('frequently_asked_questions' => 'faq', 'change_log' => 'changelog', 'screenshot' => 'screenshots');
	/**
	 * These are the valid header mappings for the header.
	 *
	 * @var array
	 */
	public $valid_headers = array('tested' => 'tested', 'tested up to' => 'tested', 'requires' => 'requires', 'requires at least' => 'requires', 'requires php' => 'requires_php', 'tags' => 'tags', 'contributors' => 'contributors', 'donate link' => 'donate_link', 'stable tag' => 'stable_tag', 'license' => 'license', 'license uri' => 'license_uri');
	/**
	 * These plugin tags are ignored.
	 *
	 * @var array
	 */
	public $ignore_tags = array('plugin', 'wordpress');
	/**
	 * The maximum field lengths for the readme.
	 *
	 * @var array
	 */
	public $maximum_field_lengths = array('short_description' => 150, 'section' => 2500, 'section-changelog' => 5000, 'section-faq' => 5000);
	/**
	 * The raw contents of the readme file.
	 *
	 * @var string
	 */
	public $raw_contents = '';

	public function __construct($string = '') {}
}
