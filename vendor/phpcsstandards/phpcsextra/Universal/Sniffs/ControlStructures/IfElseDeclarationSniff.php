<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that `else(if)` statements with braces are on a new line.
 *
 * Sister-sniff to the following two PHPCS native sniffs which each demand that `else[]if` is on the
 * same line as the closing curly of the preceding `(else)if`:
 * - `PEAR.ControlStructures.ControlSignature[.Found]`
 * - `Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace`
 *
 * Other related sniffs:
 * - `Squiz.ControlStructures.ElseIfDeclaration` Forbids the use of "elseif", demands "else if".
 * - `PSR2.ControlStructures.ElseIfDeclaration`  Forbids the use of "else if", demands "elseif".
 *
 * @since 1.0.0
 */
final class IfElseDeclarationSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Else(if) on a new line';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            \T_ELSE,
            \T_ELSEIF,
        ];
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
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        /*
         * Check for control structures without braces and alternative syntax.
         */
        $scopePtr = $stackPtr;
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            // Deal with "else if".
            $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if ($tokens[$next]['code'] === \T_IF) {
                $scopePtr = $next;
            }
        }

        if (isset($tokens[$scopePtr]['scope_opener']) === false
            || $tokens[$tokens[$scopePtr]['scope_opener']]['code'] === \T_COLON
        ) {
            // No scope opener found or alternative syntax (not our concern).
            return;
        }

        /*
         * Check whether the else(if) is on a new line.
         */
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevNonEmpty === false || $tokens[$prevNonEmpty]['code'] !== \T_CLOSE_CURLY_BRACKET) {
            // Parse error or mixing braced and non-braced. Not our concern.
            return;
        }

        if ($tokens[$prevNonEmpty]['line'] !== $tokens[$stackPtr]['line']) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'yes');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'no');

        $errorBase = \strtoupper($tokens[$stackPtr]['content']);
        $error     = $errorBase . ' statement must be on a new line.';

        $prevNonWhitespace = $phpcsFile->findPrevious(\T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($prevNonWhitespace !== $prevNonEmpty) {
            // Comment found between previous scope closer and the keyword.
            $fix = $phpcsFile->addError($error, $stackPtr, 'NoNewLine');
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoNewLine');
        if ($fix === false) {
            return;
        }

        /*
         * Fix it.
         */

        // Figure out the indentation for the else(if).
        $indentBase = $prevNonEmpty;
        if (isset($tokens[$prevNonEmpty]['scope_condition']) === true
            && ($tokens[$tokens[$prevNonEmpty]['scope_condition']]['column'] === 1
            || ($tokens[($tokens[$prevNonEmpty]['scope_condition'] - 1)]['code'] === \T_WHITESPACE
                && $tokens[($tokens[$prevNonEmpty]['scope_condition'] - 1)]['column'] === 1))
        ) {
            // Base the indentation off the previous if/elseif if on a line by itself.
            $indentBase = $tokens[$prevNonEmpty]['scope_condition'];
        }

        $indent            = '';
        $firstOnIndentLine = $indentBase;
        if ($tokens[$firstOnIndentLine]['column'] !== 1) {
            while (isset($tokens[($firstOnIndentLine - 1)]) && $tokens[--$firstOnIndentLine]['column'] !== 1);

            if ($tokens[$firstOnIndentLine]['code'] === \T_WHITESPACE) {
                $indent = $tokens[$firstOnIndentLine]['content'];

                // If tabs were replaced, use the original content.
                if (isset($tokens[$firstOnIndentLine]['orig_content']) === true) {
                    $indent = $tokens[$firstOnIndentLine]['orig_content'];
                }
            }
        }

        $phpcsFile->fixer->beginChangeset();

        // Remove any whitespace between the previous scope closer and the else(if).
        for ($i = ($prevNonEmpty + 1); $i < $stackPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($prevNonEmpty, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }
}
