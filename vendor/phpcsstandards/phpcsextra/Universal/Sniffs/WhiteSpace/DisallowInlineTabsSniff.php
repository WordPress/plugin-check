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
use PHPCSExtra\Universal\Helpers\DummyTokenizer;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Tokens\Collections;

/**
 * Enforces using spaces for mid-line alignment.
 *
 * While tab versus space based indentation is a question of preference, for mid-line
 * alignment, spaces should always be preferred, as using tabs will result in inconsistent
 * formatting depending on the dev-user's chosen tab width.
 *
 * This sniff is especially useful for tab-indentation based standards which use the
 * `Generic.Whitespace.DisallowSpaceIndent` sniff to enforce this.
 *
 * **DO** make sure to set the PHPCS native `tab-width` configuration for the best results.
 * <code>
 *   <arg name="tab-width" value="4"/>
 * </code>
 *
 * The PHPCS native `Generic.Whitespace.DisallowTabIndent` sniff oversteps its reach and silently
 * does mid-line tab to space replacements as well.
 * However, the sister-sniff `Generic.Whitespace.DisallowSpaceIndent` leaves mid-line tabs/spaces alone.
 * This sniff fills that gap.
 *
 * @since 1.0.0
 */
final class DisallowInlineTabsSniff implements Sniff
{

    /**
     * The --tab-width CLI value that is being used.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $tabWidth;

    /**
     * Tokens to check for mid-line tabs.
     *
     * @since 1.0.0
     *
     * @var array<int|string, true>
     */
    private $find = [
        \T_WHITESPACE             => true,
        \T_DOC_COMMENT_WHITESPACE => true,
        \T_DOC_COMMENT_STRING     => true,
        \T_COMMENT                => true,
        \T_START_HEREDOC          => true,
        \T_START_NOWDOC           => true,
        \T_YIELD_FROM             => true,
    ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
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
        if (isset($this->tabWidth) === false) {
            $this->tabWidth = (int) Helper::getTabWidth($phpcsFile);
        }

        if (\defined('PHP_CODESNIFFER_IN_TESTS')) {
            $this->tabWidth = (int) Helper::getCommandLineData($phpcsFile, 'tabWidth');
        }

        $tokens = $phpcsFile->getTokens();
        $dummy  = new DummyTokenizer('', $phpcsFile->config);

        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            // Skip all non-target tokens and skip whitespace at the start of a new line.
            if (isset($this->find[$tokens[$i]['code']]) === false
                || (($tokens[$i]['code'] === \T_WHITESPACE
                    || $tokens[$i]['code'] === \T_DOC_COMMENT_WHITESPACE)
                    && $tokens[$i]['column'] === 1)
            ) {
                continue;
            }

            // If tabs haven't been converted to spaces by the tokenizer, do so now.
            $token = $tokens[$i];
            if (isset($token['orig_content']) === false) {
                if ($token['content'] === '' || \strpos($token['content'], "\t") === false) {
                    // If there are no tabs, we can continue, no matter what.
                    continue;
                }

                $dummy->replaceTabsInToken($token);
            }

            /*
             * Tokens only have the 'orig_content' key if they contain tabs,
             * so from here on out, we **know** there will be tabs in the content.
             */
            $origContent = $token['orig_content'];
            $commentOnly = '';

            $multiLineComment = false;
            if (($tokens[$i]['code'] === \T_COMMENT
                || isset(Tokens::$phpcsCommentTokens[$tokens[$i]['code']]))
                && $tokens[$i]['column'] === 1
                && ($tokens[($i - 1)]['code'] === \T_COMMENT
                || isset(Tokens::$phpcsCommentTokens[$tokens[($i - 1)]['code']]))
            ) {
                $multiLineComment = true;
            }

            if ($multiLineComment === true) {
                // This is the subsequent line of a multi-line comment. Account for indentation.
                $commentOnly = \ltrim($origContent);
                if ($commentOnly === '' || \strpos($commentOnly, "\t") === false) {
                    continue;
                }
            }

            $fix = $phpcsFile->addFixableError(
                'Spaces must be used for mid-line alignment; tabs are not allowed',
                $i,
                'NonIndentTabsUsed'
            );

            if ($fix === false) {
                continue;
            }

            $indent = '';
            if ($multiLineComment === true) {
                // Take the original indent (tabs/spaces) and combine with the tab-replaced comment content.
                $indent           = \str_replace($commentOnly, '', $origContent);
                $token['content'] = \ltrim($token['content']);
            }

            $phpcsFile->fixer->replaceToken($i, $indent . $token['content']);
        }

        // Scanned the whole file in one go. Don't scan this file again.
        return $phpcsFile->numTokens;
    }
}
