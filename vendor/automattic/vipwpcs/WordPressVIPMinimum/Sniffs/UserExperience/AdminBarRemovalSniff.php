<?php
/**
 * WordPressVIPMinimum Coding Standard.
 *
 * @package VIPCS\WordPressVIPMinimum
 * @link https://github.com/Automattic/VIP-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressVIPMinimum\Sniffs\UserExperience;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Discourages removal of the admin bar.
 *
 * @link https://docs.wpvip.com/technical-references/code-review/vip-warnings/#h-removing-the-admin-bar
 *
 * @since 0.5.0
 */
class AdminBarRemovalSniff extends AbstractFunctionParameterSniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = [
		'PHP',
		'CSS',
	];

	/**
	 * Whether or not the sniff only checks for removal of the admin bar
	 * or any manipulation to the visibility of the admin bar.
	 *
	 * Defaults to true: only check for removal of the admin bar.
	 * Set to false to check for any form of manipulation of the visibility
	 * of the admin bar.
	 *
	 * @var bool
	 */
	public $remove_only = true;

	/**
	 * Functions this sniff is looking for.
	 *
	 * @var array
	 */
	protected $target_functions = [
		'show_admin_bar' => true,
		'add_filter'     => true,
	];

	/**
	 * CSS properties this sniff is looking for.
	 *
	 * @var array
	 */
	protected $target_css_properties = [
		'visibility' => [
			'type'  => '!=',
			'value' => 'hidden',
		],
		'display' => [
			'type'  => '!=',
			'value' => 'none',
		],
		'opacity' => [
			'type'  => '>',
			'value' => 0.3,
		],
	];

	/**
	 * CSS selectors this sniff is looking for.
	 *
	 * @var array
	 */
	protected $target_css_selectors = [
		'.show-admin-bar',
		'#wpadminbar',
	];

	/**
	 * String tokens within PHP files we want to deal with.
	 *
	 * Set from the register() method.
	 *
	 * @var array
	 */
	private $string_tokens = [];

	/**
	 * Regex template for use with the CSS selectors in combination with PHP text strings.
	 *
	 * @var string
	 */
	private $target_css_selectors_regex = '`(?:%s).*?\{(.*)$`';

	/**
	 * Property to keep track of whether a <style> open tag has been encountered.
	 *
	 * @var array
	 */
	private $in_style;

	/**
	 * Property to keep track of whether a one of the target selectors has been encountered.
	 *
	 * @var array
	 */
	private $in_target_selector;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		// Set up all string targets.
		$this->string_tokens = Tokens::$textStringTokens;

		$targets = $this->string_tokens;

		// Add CSS style target.
		$targets[] = \T_STYLE;

		// Set the target selectors regex only once.
		$selectors = array_map(
			'preg_quote',
			$this->target_css_selectors,
			array_fill( 0, \count( $this->target_css_selectors ), '`' )
		);
		// Parse the selectors array into the regex string.
		$this->target_css_selectors_regex = sprintf( $this->target_css_selectors_regex, implode( '|', $selectors ) );

		// Add function call targets.
		$parent = parent::register();
		if ( ! empty( $parent ) ) {
			$targets[] = \T_STRING;
		}

		return $targets;
	}

	/**
	 * Process the token.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stackPtr ) {

		$file_name      = $this->phpcsFile->getFilename();
		$file_extension = substr( strrchr( $file_name, '.' ), 1 );

		if ( $file_extension === 'css' ) {
			if ( $this->tokens[ $stackPtr ]['code'] === \T_STYLE ) {
				$this->process_css_style( $stackPtr );
				return;
			}
		} elseif ( isset( $this->string_tokens[ $this->tokens[ $stackPtr ]['code'] ] ) ) {
			/*
			 * Set $in_style && $in_target_selector to false if it is the first time
			 * this sniff is run on a file.
			 */
			if ( ! isset( $this->in_style[ $file_name ] ) ) {
				$this->in_style[ $file_name ] = false;
			}
			if ( ! isset( $this->in_target_selector[ $file_name ] ) ) {
				$this->in_target_selector[ $file_name ] = false;
			}

			$this->process_text_for_style( $stackPtr, $file_name );
			return;

		} else {
			parent::process_token( $stackPtr );
			return;
		}
	}

	/**
	 * Process the parameters of a matched function.
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched
	 *                                in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$error = false;
		switch ( $matched_content ) {
			case 'show_admin_bar':
				$error = true;
				if ( $this->remove_only === true && $parameters[1]['raw'] === 'true' ) {
					$error = false;
				}
				break;

			case 'add_filter':
				$filter_name = TextStrings::stripQuotes( $parameters[1]['raw'] );
				if ( $filter_name !== 'show_admin_bar' ) {
					break;
				}

				$error = true;
				if ( $this->remove_only === true && isset( $parameters[2]['raw'] ) && TextStrings::stripQuotes( $parameters[2]['raw'] ) === '__return_true' ) {
					$error = false;
				}
				break;
		}

		if ( $error === true ) {
			$message = 'Removal of admin bar is prohibited.';
			$this->phpcsFile->addError( $message, $stackPtr, 'RemovalDetected' );
		}
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int    $stackPtr  The position of the current token in the stack.
	 * @param string $file_name The file name of the current file being processed.
	 *
	 * @return void
	 */
	public function process_text_for_style( $stackPtr, $file_name ) {
		$content = trim( $this->tokens[ $stackPtr ]['content'] );

		// No need to check an empty string.
		if ( $content === '' ) {
			return;
		}

		// Are we in a <style> tag ?
		if ( $this->in_style[ $file_name ] === true ) {
			if ( strpos( $content, '</style>' ) !== false ) {
				// Make sure we check any content on this line before the closing style tag.
				$this->in_style[ $file_name ] = false;
				$content                      = trim( substr( $content, 0, strpos( $content, '</style>' ) ) );
			}
		} elseif ( $this->has_html_open_tag( 'style', $stackPtr, $content ) === true ) {
			// Ok, found a <style> open tag.
			if ( strpos( $content, '</style>' ) === false ) {
				// Make sure we check any content on this line after the opening style tag.
				$this->in_style[ $file_name ] = true;
				$content                      = trim( substr( $content, strpos( $content, '<style' ) + 6 ) );
			} else {
				// Ok, we have open and close style tag on the same line with possibly content within.
				$start   = ( strpos( $content, '<style' ) + 6 );
				$end     = strpos( $content, '</style>' );
				$content = trim( substr( $content, $start, $end - $start ) );
				unset( $start, $end );
			}
		} else {
			return;
		}

		// Are we in one of the target selectors ?
		if ( $this->in_target_selector[ $file_name ] === true ) {
			if ( strpos( $content, '}' ) !== false ) {
				// Make sure we check any content on this line before the selector closing brace.
				$this->in_target_selector[ $file_name ] = false;
				$content                                = trim( substr( $content, 0, strpos( $content, '}' ) ) );
			}
		} elseif ( preg_match( $this->target_css_selectors_regex, $content, $matches ) > 0 ) {
			// Ok, found a new target selector.
			$content = '';

			if ( isset( $matches[1] ) && $matches[1] !== '' ) {
				if ( strpos( $matches[1], '}' ) === false ) {
					// Make sure we check any content on this line before the closing brace.
					$this->in_target_selector[ $file_name ] = true;
					$content                                = trim( $matches[1] );
				} else {
					// Ok, we have the selector open and close brace on the same line.
					$content = trim( substr( $matches[1], 0, strpos( $matches[1], '}' ) ) );
				}
			} else {
				$this->in_target_selector[ $file_name ] = true;
			}
		} else {
			return;
		}
		unset( $matches );

		// Now let's do the check for the CSS properties.
		if ( ! empty( $content ) ) {
			foreach ( $this->target_css_properties as $property => $requirements ) {
				if ( strpos( $content, $property ) !== false ) {
					$error = true;

					// Check the value of the CSS property.
					if ( $this->remove_only === true && preg_match( '`' . preg_quote( $property, '`' ) . '\s*:\s*(.+?)\s*(?:!important)?;`', $content, $matches ) > 0 ) {
						$value = trim( $matches[1] );
						$valid = $this->validate_css_property_value( $value, $requirements['type'], $requirements['value'] );
						if ( $valid === true ) {
							$error = false;
						}
					}

					if ( $error === true ) {
						$this->addHidingDetectedError( $stackPtr );
					}
				}
			}
		}
	}

	/**
	 * Processes this test for T_STYLE tokens in CSS files.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	protected function process_css_style( $stackPtr ) {
		if ( ! isset( $this->target_css_properties[ $this->tokens[ $stackPtr ]['content'] ] ) ) {
			// Not one of the CSS properties we're interested in.
			return;
		}

		$css_property = $this->target_css_properties[ $this->tokens[ $stackPtr ]['content'] ];

		// Check if the CSS selector matches.
		$opener = $this->phpcsFile->findPrevious( \T_OPEN_CURLY_BRACKET, $stackPtr );
		if ( $opener !== false ) {
			for ( $i = ( $opener - 1 ); $i >= 0; $i-- ) {
				if ( isset( Tokens::$commentTokens[ $this->tokens[ $i ]['code'] ] )
					|| $this->tokens[ $i ]['code'] === \T_CLOSE_CURLY_BRACKET
				) {
					break;
				}
			}
			$start    = ( $i + 1 );
			$selector = trim( GetTokensAsString::normal( $this->phpcsFile, $start, ( $opener - 1 ) ) );
			unset( $i );

			foreach ( $this->target_css_selectors as $target_selector ) {
				if ( strpos( $selector, $target_selector ) !== false ) {
					$error = true;

					if ( $this->remove_only === true ) {
						// Check the value of the CSS property.
						$valuePtr = $this->phpcsFile->findNext( [ \T_COLON, \T_WHITESPACE ], $stackPtr + 1, null, true );
						$value    = $this->tokens[ $valuePtr ]['content'];
						$valid    = $this->validate_css_property_value( $value, $css_property['type'], $css_property['value'] );
						if ( $valid === true ) {
							$error = false;
						}
					}

					if ( $error === true ) {
						$this->addHidingDetectedError( $stackPtr );
					}
				}
			}
		}
	}

	/**
	 * Consolidated violation.
	 *
	 * @param int $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	private function addHidingDetectedError( $stackPtr ) {
		$message = 'Hiding of the admin bar is not allowed.';
		$this->phpcsFile->addError( $message, $stackPtr, 'HidingDetected' );
	}

	/**
	 * Verify if a CSS property value complies with an expected value.
	 *
	 * {@internal This is a method stub, doing only what is needed for this sniff.
	 * If at some point in the future other sniff would need similar functionality,
	 * this method should be moved to the WordPress_Sniff class and expanded to cover
	 * all types of comparisons.}}
	 *
	 * @param mixed  $value         The value of CSS property.
	 * @param string $compare_type  The type of comparison to use for the validation.
	 * @param string $compare_value The value to compare against.
	 *
	 * @return bool True if the property value complies, false otherwise.
	 */
	protected function validate_css_property_value( $value, $compare_type, $compare_value ) {
		switch ( $compare_type ) {
			case '!=':
				return $value !== $compare_value;

			case '>':
				return $value > $compare_value;
		}
		return false;
	}

	/**
	 * Check if a content string contains a specific HTML open tag.
	 *
	 * @param string $tag_name The name of the HTML tag without brackets. So if
	 *                         searching for '<span...', this would be 'span'.
	 * @param int    $stackPtr Optional. The position of the current token in the
	 *                         token stack.
	 *                         This parameter needs to be passed if no $content is
	 *                         passed.
	 * @param string $content  Optionally, the current content string, might be a
	 *                         substring of the original string.
	 *                         Defaults to `false` to distinguish between a passed
	 *                         empty string and not passing the $content string.
	 *
	 * @return bool True if the string contains an <tag_name> open tag, false otherwise.
	 */
	public function has_html_open_tag( $tag_name, $stackPtr = null, $content = null ) {
		if ( $content === null && isset( $stackPtr ) ) {
			$content = $this->tokens[ $stackPtr ]['content'];
		}

		return $content !== null && strpos( $content, '<' . $tag_name ) !== false;
	}
}
