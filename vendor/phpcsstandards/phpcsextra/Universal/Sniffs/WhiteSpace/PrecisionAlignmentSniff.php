<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Tokens\Collections;

/**
 * Detects when the indentation is not a multiple of a tab-width, i.e. when precision alignment is used.
 *
 * In rare cases, spaces for precision alignment can be intentional and acceptable,
 * but more often than not, precision alignment is a typo.
 *
 * Notes:
 * - When using this sniff with tab-based standards, please ensure that the `tab-width` is set
 *   and either don't set the `$indent` property or set it to the tab-width.
 * - Precision alignment *within* text strings (multi-line text strings, heredocs, nowdocs)
 *   will NOT be flagged by this sniff.
 * - The fixer works based on "best guess" and may not always result in the desired indentation.
 * - This fixer will use tabs or spaces based on whether tabs were present in the original indent.
 *   Use the PHPCS native `Generic.WhiteSpace.DisallowTabIndent` or the
 *   `Generic.WhiteSpace.DisallowSpaceIndent` sniff to clean up the results if so desired.
 *
 * @since 1.0.0
 * @since 1.4.0 Support for scanning JS and CSS files is deprecated and will be removed in the next major.
 */
final class PrecisionAlignmentSniff implements Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
        'CSS',
    ];

    /**
     * The indent used for the codebase.
     *
     * This property is used to determine whether something is indentation or precision alignment.
     * If this property is not set, the sniff will look to the `--tab-width` CLI value.
     * If that also isn't set, the default tab-width of 4 will be used.
     *
     * @since 1.0.0
     *
     * @var int|null
     */
    public $indent = null;

    /**
     * Allow for providing a list of tokens for which (preceding) precision alignment should be ignored.
     *
     * By default, precision alignment will always be flagged.
     *
     * Example usage:
     * ```xml
     * <rule ref="Universal.WhiteSpace.PrecisionAlignment">
     *    <properties>
     *        <property name="ignoreAlignmentBefore" type="array">
     *            <!-- Ignore precision alignment in inline HTML -->
     *            <element value="T_INLINE_HTML"/>
     *            <!-- Ignore precision alignment in multiline chained method calls. -->
     *            <element value="T_OBJECT_OPERATOR"/>
     *        </property>
     *    </properties>
     * </rule>
     * ```
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    public $ignoreAlignmentBefore = [];

    /**
     * Whether or not potential trailing whitespace on otherwise blank lines should be examined or ignored.
     *
     * Defaults to `true`, i.e. ignore blank lines.
     *
     * It is recommended to only set this to `false` if the standard including this sniff does not
     * include the `Squiz.WhiteSpace.SuperfluousWhitespace` sniff (which is included in most standards).
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $ignoreBlankLines = true;

    /**
     * The --tab-width CLI value that is being used.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $tabWidth;

    /**
     * Whitespace tokens and tokens which can contain leading whitespace.
     *
     * A few additional tokens will be added to this list in the register() method.
     *
     * @since 1.0.0
     *
     * @var array<int|string, int|string>
     */
    private $tokensToCheck = [
        \T_WHITESPACE             => \T_WHITESPACE,
        \T_INLINE_HTML            => \T_INLINE_HTML,
        \T_DOC_COMMENT_WHITESPACE => \T_DOC_COMMENT_WHITESPACE,
        \T_COMMENT                => \T_COMMENT,
        \T_END_HEREDOC            => \T_END_HEREDOC,
        \T_END_NOWDOC             => \T_END_NOWDOC,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        // Add the ignore annotation tokens to the list of tokens to check.
        $this->tokensToCheck += Tokens::$phpcsCommentTokens;

        return Collections::phpOpenTags();
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return int Integer stack pointer to skip the rest of the file.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        /*
         * Handle the properties.
         */
        if (isset($this->tabWidth) === false || \defined('PHP_CODESNIFFER_IN_TESTS') === true) {
            $this->tabWidth = Helper::getTabWidth($phpcsFile);
        }

        if (isset($this->indent) === true) {
            $indent = (int) $this->indent;
        } else {
            $indent = $this->tabWidth;
        }

        $ignoreTokens = (array) $this->ignoreAlignmentBefore;
        if (empty($ignoreTokens) === false) {
            $ignoreTokens = \array_flip($ignoreTokens);
        }

        /*
         * Check the whole file in one go.
         */
        $tokens = $phpcsFile->getTokens();

        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] !== 1) {
                // Only interested in the first token on each line.
                continue;
            }

            if (isset($this->tokensToCheck[$tokens[$i]['code']]) === false) {
                // Not one of the target tokens.
                continue;
            }

            if ($tokens[$i]['content'] === $phpcsFile->eolChar) {
                // Skip completely blank lines.
                continue;
            }

            if (isset($ignoreTokens[$tokens[$i]['type']]) === true
                || (isset($tokens[($i + 1)]) && isset($ignoreTokens[$tokens[($i + 1)]['type']]))
            ) {
                // This is one of the tokens being ignored.
                continue;
            }

            $origContent = null;
            if (isset($tokens[$i]['orig_content']) === true) {
                $origContent = $tokens[$i]['orig_content'];
            }

            $spaces  = 0;
            $length  = 0;
            $content = '';
            $closer  = '';

            switch ($tokens[$i]['code']) {
                case \T_WHITESPACE:
                    if ($this->ignoreBlankLines === true
                        && isset($tokens[($i + 1)])
                        && $tokens[$i]['line'] !== $tokens[($i + 1)]['line']
                    ) {
                        // Skip blank lines which only contain trailing whitespace.
                        continue 2;
                    }

                    $spaces = ($tokens[$i]['length'] % $indent);
                    break;

                case \T_DOC_COMMENT_WHITESPACE:
                    /*
                     * Blank lines with trailing whitespace in docblocks are tokenized as
                     * two T_DOC_COMMENT_WHITESPACE tokens: one for the trailing whitespace,
                     * one for the new line character.
                     */
                    if ($this->ignoreBlankLines === true
                        && isset($tokens[($i + 1)])
                        && $tokens[($i + 1)]['content'] === $phpcsFile->eolChar
                        && isset($tokens[($i + 2)])
                        && $tokens[$i]['line'] !== $tokens[($i + 2)]['line']
                    ) {
                        // Skip blank lines which only contain trailing whitespace.
                        continue 2;
                    }

                    $spaces = ($tokens[$i]['length'] % $indent);

                    if (isset($tokens[($i + 1)]) === true
                        && ($tokens[($i + 1)]['code'] === \T_DOC_COMMENT_STAR
                            || $tokens[($i + 1)]['code'] === \T_DOC_COMMENT_CLOSE_TAG)
                        && $spaces !== 0
                    ) {
                        // One alignment space expected before the *.
                        --$spaces;
                    }
                    break;

                case \T_COMMENT:
                case \T_INLINE_HTML:
                    if ($this->ignoreBlankLines === true
                        && \trim($tokens[$i]['content']) === ''
                        && isset($tokens[($i + 1)])
                        && $tokens[$i]['line'] !== $tokens[($i + 1)]['line']
                    ) {
                        // Skip blank lines which only contain trailing whitespace.
                        continue 2;
                    }

                    // Deliberate fall-through.

                case \T_PHPCS_ENABLE:
                case \T_PHPCS_DISABLE:
                case \T_PHPCS_SET:
                case \T_PHPCS_IGNORE:
                case \T_PHPCS_IGNORE_FILE:
                    /*
                     * Indentation is included in the contents of the token for:
                     * - inline HTML
                     * - PHP 7.3+ flexible heredoc/nowdoc closer identifiers (see below);
                     * - subsequent lines of multi-line comments;
                     * - PHPCS native annotations when part of a multi-line comment.
                     */
                    $content    = \ltrim($tokens[$i]['content']);
                    $whitespace = \str_replace($content, '', $tokens[$i]['content']);

                    /*
                     * If there is no content, this is a blank line in a comment or in inline HTML.
                     * In that case, use the predetermined length as otherwise the new line character
                     * at the end of the whitespace will throw the count off.
                     */
                    $length = ($content === '') ? $tokens[$i]['length'] : \strlen($whitespace);
                    $spaces = ($length % $indent);

                    /*
                     * For multi-line star-comments, which use (aligned) stars on subsequent
                     * lines, we don't want to trigger on the one extra space before the star.
                     *
                     * While not 100% correct, don't exclude inline HTML from this check as
                     * otherwise the sniff would trigger on multi-line /*-style inline javascript comments.
                     * This may cause false negatives as there is no check for being in a
                     * <script> tag, but that will be rare.
                     */
                    if (isset($content[0]) === true && $content[0] === '*' && $spaces !== 0) {
                        --$spaces;
                    }
                    break;

                case \T_END_HEREDOC:
                case \T_END_NOWDOC:
                    $content    = $tokens[$i]['content'];
                    $closer     = \ltrim($content);
                    $whitespace = \str_replace($closer, '', $content);
                    $length     = \strlen($whitespace);
                    $spaces     = ($length % $indent);
                    break;
            }

            if ($spaces === 0) {
                continue;
            }

            $fix = $phpcsFile->addFixableWarning(
                'Found precision alignment of %s spaces.',
                $i,
                'Found',
                [$spaces]
            );

            if ($fix === true) {
                if ($tokens[$i]['code'] === \T_END_HEREDOC || $tokens[$i]['code'] === \T_END_NOWDOC) {
                    // For heredoc/nowdoc, always round down to prevent introducing parse errors.
                    $tabstops = (int) \floor($spaces / $indent);
                } else {
                    // For everything else, use "best guess".
                    $tabstops = (int) \round($spaces / $indent, 0);
                }

                switch ($tokens[$i]['code']) {
                    case \T_WHITESPACE:
                        /*
                         * More complex than you'd think as "length" doesn't include new lines,
                         * but we don't want to remove new lines either.
                         */
                        $replaceLength = (((int) ($tokens[$i]['length'] / $indent) + $tabstops) * $indent);
                        $replace       = $this->getReplacement($replaceLength, $origContent);
                        $newContent    = \substr_replace($tokens[$i]['content'], $replace, 0, $tokens[$i]['length']);

                        $phpcsFile->fixer->replaceToken($i, $newContent);
                        break;

                    case \T_DOC_COMMENT_WHITESPACE:
                        $replaceLength = (((int) ($tokens[$i]['length'] / $indent) + $tabstops) * $indent);
                        $replace       = $this->getReplacement($replaceLength, $origContent);

                        if (isset($tokens[($i + 1)]) === true
                            && ($tokens[($i + 1)]['code'] === \T_DOC_COMMENT_STAR
                                || $tokens[($i + 1)]['code'] === \T_DOC_COMMENT_CLOSE_TAG)
                            && $tabstops === 0
                        ) {
                            // Maintain the extra space before the star.
                            $replace .= ' ';
                        }

                        $newContent = \substr_replace($tokens[$i]['content'], $replace, 0, $tokens[$i]['length']);

                        $phpcsFile->fixer->replaceToken($i, $newContent);
                        break;

                    case \T_COMMENT:
                    case \T_INLINE_HTML:
                    case \T_PHPCS_ENABLE:
                    case \T_PHPCS_DISABLE:
                    case \T_PHPCS_SET:
                    case \T_PHPCS_IGNORE:
                    case \T_PHPCS_IGNORE_FILE:
                        $replaceLength = (((int) ($length / $indent) + $tabstops) * $indent);
                        $replace       = $this->getReplacement($replaceLength, $origContent);

                        if (isset($content[0]) === true && $content[0] === '*' && $tabstops === 0) {
                            // Maintain the extra space before the star.
                            $replace .= ' ';
                        }

                        if ($content === '') {
                            // Preserve new lines in blank line comment tokens.
                            $newContent = \substr_replace($tokens[$i]['content'], $replace, 0, $length);
                        } else {
                            $newContent = $replace . $content;
                        }

                        $phpcsFile->fixer->replaceToken($i, $newContent);
                        break;

                    case \T_END_HEREDOC:
                    case \T_END_NOWDOC:
                        $replaceLength = (((int) ($length / $indent) + $tabstops) * $indent);
                        $replace       = $this->getReplacement($replaceLength, $origContent);

                        $phpcsFile->fixer->replaceToken($i, $replace . $closer);
                        break;
                }
            }
        }

        // No need to look at this file again.
        return $phpcsFile->numTokens;
    }

    /**
     * Get the whitespace replacement. Respect tabs vs spaces.
     *
     * @param int         $length      The target length of the replacement.
     * @param string|null $origContent The original token content without tabs replaced (if available).
     *
     * @return string
     */
    private function getReplacement($length, $origContent)
    {
        if ($origContent !== null) {
            // Check whether tabs were part of the indent or inline alignment.
            $content    = \ltrim($origContent);
            $whitespace = $origContent;
            if ($content !== '') {
                $whitespace = \str_replace($content, '', $origContent);
            }

            if (\strpos($whitespace, "\t") !== false) {
                // Original indent used tabs. Use tabs in replacement too.
                $tabs   = (int) ($length / $this->tabWidth);
                $spaces = $length % $this->tabWidth;
                return \str_repeat("\t", $tabs) . \str_repeat(' ', (int) $spaces);
            }
        }

        return \str_repeat(' ', $length);
    }
}
