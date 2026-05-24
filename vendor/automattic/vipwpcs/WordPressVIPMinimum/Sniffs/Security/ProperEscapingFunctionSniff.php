<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 */

namespace WordPressVIPMinimum\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\BCFile;
use PHPCSUtils\Utils\TextStrings;
use WordPressVIPMinimum\Sniffs\Sniff;

/**
 * Checks whether proper escaping function is used.
 */
class ProperEscapingFunctionSniff extends Sniff {

	/**
	 * Regular expression to match the end of HTML attributes.
	 *
	 * @var string
	 */
	const ATTR_END_REGEX = '`(?<attrname>href|src|url|(^|\s+)action)?(?<=[a-z0-9_-])=(?:\\\\)?["\']*$`i';

	/**
	 * List of escaping functions which are being tested.
	 *
	 * @var array
	 */
	protected $escaping_functions = [
		'esc_url'    => 'url',
		'esc_attr'   => 'attr',
		'esc_attr__' => 'attr',
		'esc_attr_x' => 'attr',
		'esc_attr_e' => 'attr',
		'esc_html'   => 'html',
		'esc_html__' => 'html',
		'esc_html_x' => 'html',
		'esc_html_e' => 'html',
	];

	/**
	 * List of tokens we can skip.
	 *
	 * @var array
	 */
	private $echo_or_concat_tokens =
	[
		T_ECHO               => T_ECHO,
		T_OPEN_TAG           => T_OPEN_TAG,
		T_OPEN_TAG_WITH_ECHO => T_OPEN_TAG_WITH_ECHO,
		T_STRING_CONCAT      => T_STRING_CONCAT,
		T_NS_SEPARATOR       => T_NS_SEPARATOR,
	];

	/**
	 * List of attributes associated with url outputs.
	 *
	 * @deprecated 2.3.1 Currently unused by the sniff, but needed for
	 *                   for public methods which extending sniffs may be
	 *                   relying on.
	 *
	 * @var array
	 */
	private $url_attrs = [
		'href',
		'src',
		'url',
		'action',
	];

	/**
	 * List of syntaxes for inside attribute detection.
	 *
	 * @deprecated 2.3.1 Currently unused by the sniff, but needed for
	 *                   for public methods which extending sniffs may be
	 *                   relying on.
	 *
	 * @var array
	 */
	private $attr_endings = [
		'=',
		'="',
		"='",
		"=\\'",
		'=\\"',
	];

	/**
	 * Keep track of whether or not we're currently in the first statement of a short open echo tag.
	 *
	 * @var int|false Integer stack pointer to the end of the first statement in the current
	 *                short open echo tag or false when not in a short open echo tag.
	 */
	private $in_short_echo = false;

	/**
	 * Keep track of the current file, so we can reset $in_short_echo for each new file.
	 *
	 * @var string Absolute file name of the file being processed. Defaults to an empty string.
	 */
	private $current_file = '';

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		$this->echo_or_concat_tokens += Tokens::$emptyTokens;

		return [
			T_STRING,
			T_OPEN_TAG_WITH_ECHO,
		];
	}

	/**
	 * Process this test when one of its tokens is encountered
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {
		// Reset short echo context tracking variable for a new file.
		if ( $this->phpcsFile->getFilename() !== $this->current_file ) {
			$this->in_short_echo = false;
			$this->current_file  = $this->phpcsFile->getFilename();
		}

		/*
		 * Short open echo tags will act as an echo for the first expression and
		 * allow for passing multiple comma-separated parameters.
		 * However, short open echo tags also allow for additional statements after, but
		 * those have to be full PHP statements, not expressions.
		 *
		 * This snippet of code will keep track of whether or not we're in the first
		 * expression in a short open echo tag.
		 * $phpcsFile->findStartOfStatement() unfortunately is useless, as it will return
		 * the first token in the statement, which can be anything - variable, text string -
		 * without any indication of whether this is the start of a normal statement or
		 * a short open echo expression.
		 * So, if we used that, we'd need to walk back from every start of statement to
		 * the previous non-empty to see if it is the short open echo tag.
		 */
		if ( $this->tokens[ $stackPtr ]['code'] === T_OPEN_TAG_WITH_ECHO ) {
			$end_of_echo = $this->phpcsFile->findNext( [ T_SEMICOLON, T_CLOSE_TAG ], ( $stackPtr + 1 ) );
			if ( $end_of_echo === false ) {
				$this->in_short_echo = $this->phpcsFile->numTokens;
			} else {
				$this->in_short_echo = $end_of_echo;
			}

			return;
		}

		if ( $this->in_short_echo !== false && $this->in_short_echo < $stackPtr ) {
			$this->in_short_echo = false;
		}

		$function_name = strtolower( $this->tokens[ $stackPtr ]['content'] );

		if ( isset( $this->escaping_functions[ $function_name ] ) === false ) {
			return;
		}

		$next_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );
		if ( $next_non_empty === false || $this->tokens[ $next_non_empty ]['code'] !== T_OPEN_PARENTHESIS ) {
			// Not a function call.
			return;
		}

		$ignore = $this->echo_or_concat_tokens;
		if ( $this->in_short_echo !== false ) {
			$ignore[ T_COMMA ] = T_COMMA;
		} else {
			$start_of_statement = BCFile::findStartOfStatement( $this->phpcsFile, $stackPtr, T_COMMA );
			if ( $this->tokens[ $start_of_statement ]['code'] === T_ECHO ) {
				$ignore[ T_COMMA ] = T_COMMA;
			}
		}

		$html = $this->phpcsFile->findPrevious( $ignore, $stackPtr - 1, null, true );

		// Use $textStringTokens b/c heredoc and nowdoc tokens will never be encountered in this context anyways..
		if ( $html === false || isset( Tokens::$textStringTokens[ $this->tokens[ $html ]['code'] ] ) === false ) {
			return;
		}

		$data = [ $function_name ];

		$content = $this->tokens[ $html ]['content'];
		if ( isset( Tokens::$stringTokens[ $this->tokens[ $html ]['code'] ] ) === true ) {
			$content = TextStrings::stripQuotes( $content );
		}

		$escaping_type = $this->escaping_functions[ $function_name ];

		if ( $escaping_type === 'attr' && $this->is_outside_html_attr_context( $content ) ) {
			$message = 'Wrong escaping function, using `%s()` in a context outside of HTML attributes may not escape properly.';
			$this->phpcsFile->addError( $message, $html, 'notAttrEscAttr', $data );
			return;
		}

		if ( preg_match( self::ATTR_END_REGEX, $content, $matches ) !== 1 ) {
			return;
		}

		if ( $escaping_type !== 'url' && empty( $matches['attrname'] ) === false ) {
			$message = 'Wrong escaping function. href, src, and action attributes should be escaped by `esc_url()`, not by `%s()`.';
			$this->phpcsFile->addError( $message, $stackPtr, 'hrefSrcEscUrl', $data );
			return;
		}

		if ( $escaping_type === 'html' ) {
			$message = 'Wrong escaping function. HTML attributes should be escaped by `esc_attr()`, not by `%s()`.';
			$this->phpcsFile->addError( $message, $stackPtr, 'htmlAttrNotByEscHTML', $data );
			return;
		}
	}

	/**
	 * Tests whether provided string ends with open attribute which expects a URL value.
	 *
	 * @deprecated 2.3.1
	 *
	 * @param string $content Haystack in which we look for an open attribute which exects a URL value.
	 *
	 * @return bool True if string ends with open attribute which expects a URL value.
	 */
	public function attr_expects_url( $content ) {
		$attr_expects_url = false;
		foreach ( $this->url_attrs as $attr ) {
			foreach ( $this->attr_endings as $ending ) {
				if ( $this->endswith( $content, $attr . $ending ) === true ) {
					$attr_expects_url = true;
					break;
				}
			}
		}
		return $attr_expects_url;
	}

	/**
	 * Tests whether provided string ends with open HMTL attribute.
	 *
	 * @deprecated 2.3.1
	 *
	 * @param string $content Haystack in which we look for open HTML attribute.
	 *
	 * @return bool True if string ends with open HTML attribute.
	 */
	public function is_html_attr( $content ) {
		$is_html_attr = false;
		foreach ( $this->attr_endings as $ending ) {
			if ( $this->endswith( $content, $ending ) === true ) {
				$is_html_attr = true;
				break;
			}
		}
		return $is_html_attr;
	}

	/**
	 * Tests whether an attribute escaping function is being used outside of an HTML tag.
	 *
	 * @param string $content Haystack where we look for the end of a HTML tag.
	 *
	 * @return bool True if the passed string ends a HTML tag.
	 */
	public function is_outside_html_attr_context( $content ) {
		return $this->endswith( trim( $content ), '>' );
	}

	/**
	 * A helper function which tests whether string ends with some other.
	 *
	 * @param string $haystack String which is being tested.
	 * @param string $needle   The substring, which we try to locate on the end of the $haystack.
	 *
	 * @return bool True if haystack ends with needle.
	 */
	public function endswith( $haystack, $needle ) {
		return substr( $haystack, -strlen( $needle ) ) === $needle;
	}
}
