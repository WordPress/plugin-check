<?php
/**
 * AIProviderSniff
 *
 * @package PluginCheck
 */

namespace PluginCheckCS\PluginCheck\Sniffs\CodeAnalysis;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Sniff;

/**
 * Detects direct integrations with third-party AI providers.
 *
 * Since WordPress 7.0, plugins are encouraged to use the WordPress AI Client
 * and Connectors infrastructure (`wp_ai_client_prompt()`) instead of calling
 * provider APIs directly, so the site owner can configure their preferred
 * provider once and plugins can avoid managing provider credentials.
 *
 * @link https://make.wordpress.org/core/2025/01/15/ai-building-blocks/
 *
 * @since 2.1.0
 */
final class AIProviderSniff extends Sniff {

	/**
	 * List of known third-party AI provider API hosts to detect.
	 *
	 * Only full API hostnames are listed to keep matching precise and avoid
	 * flagging unrelated usage of a provider's marketing or documentation site.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string>
	 */
	protected $ai_provider_hosts = array(
		'api.openai.com',
		'api.anthropic.com',
		'generativelanguage.googleapis.com',
		'api.x.ai',
		'api.mistral.ai',
		'api.cohere.ai',
		'api.cohere.com',
		'api.groq.com',
		'api.perplexity.ai',
		'api.deepseek.com',
		'openrouter.ai',
	);

	/**
	 * Compiled regex pattern for detecting AI provider hosts.
	 *
	 * @since 2.1.0
	 *
	 * @var string|null
	 */
	private $pattern = null;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * Only string literals are inspected; mentions inside comments or docblocks
	 * are intentionally ignored, as they do not represent a direct integration.
	 *
	 * @since 2.1.0
	 *
	 * @return array<int|string>
	 */
	public function register() {
		return array(
			T_CONSTANT_ENCAPSED_STRING,
			T_DOUBLE_QUOTED_STRING,
			T_HEREDOC,
			T_NOWDOC,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 2.1.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return void
	 */
	public function process_token( $stackPtr ) {
		$content    = $this->tokens[ $stackPtr ]['content'];
		$token_code = $this->tokens[ $stackPtr ]['code'];

		// Heredoc/nowdoc bodies are used as-is; quoted strings have their quotes removed.
		if ( T_HEREDOC === $token_code || T_NOWDOC === $token_code ) {
			$string_content = $content;
		} else {
			$string_content = TextStrings::stripQuotes( $content );
		}

		// Compile the regex pattern on first use.
		if ( null === $this->pattern ) {
			$escaped_hosts = array_map(
				'preg_quote',
				$this->ai_provider_hosts,
				array_fill( 0, count( $this->ai_provider_hosts ), '/' )
			);

			// Require an explicit scheme directly before the host to avoid matching
			// unrelated text and to target actual request URLs.
			$this->pattern = '/https?:\/\/(' . implode( '|', $escaped_hosts ) . ')\b/i';
		}

		if ( preg_match( $this->pattern, $string_content, $matches ) ) {
			$error = 'Direct integration with a third-party AI provider (%s) detected. Consider the WordPress AI Client (wp_ai_client_prompt()) introduced in WordPress 7.0.';
			$this->phpcsFile->addWarning( $error, $stackPtr, 'DirectIntegration', array( $matches[1] ) );
		}
	}
}
