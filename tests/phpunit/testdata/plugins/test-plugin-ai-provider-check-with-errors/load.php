<?php
/**
 * Plugin Name: Test Plugin AI Provider Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the AI Provider check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-ai-provider-check-with-errors
 *
 * @package test-plugin-ai-provider-check-with-errors
 */

// Direct integration with a third-party AI provider (should be flagged).
$response = wp_remote_post(
	'https://api.openai.com/v1/chat/completions',
	array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => '{}',
	)
);

// Another provider host in a double-quoted string (should be flagged).
$endpoint = "https://api.anthropic.com/v1/messages";

// A bare host without scheme and an unrelated URL (should NOT be flagged).
$host    = 'api.openai.com';
$unrelated = 'https://example.com/v1/chat/completions';
