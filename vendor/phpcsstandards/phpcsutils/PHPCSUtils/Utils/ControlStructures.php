<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Utils;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Tokens\Collections;

/**
 * Utility functions for use when examining control structures.
 *
 * @since 1.0.0
 */
final class ControlStructures
{

    /**
     * Check whether a control structure has a body.
     *
     * Some control structures - `while`, `for` and `declare` - can be declared without a body, like:
     * ```php
     * while (++$i < 10);
     * ```
     *
     * All other control structures will always have a body, though the body may be empty, where "empty" means:
     * no _code_ is found in the body. If a control structure body only contains a comment, it will be
     * regarded as empty.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile  The file being scanned.
     * @param int                         $stackPtr   The position of the token we are checking.
     * @param bool                        $allowEmpty Whether a control structure with an empty body should
     *                                                still be considered as having a body.
     *                                                Defaults to `true`.
     *
     * @return bool `TRUE` when the control structure has a body, or in case `$allowEmpty` is set to `FALSE`:
     *              when it has a non-empty body.
     *              `FALSE` in all other cases, including when a non-control structure token has been passed.
     */
    public static function hasBody(File $phpcsFile, $stackPtr, $allowEmpty = true)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false
            || isset(Collections::controlStructureTokens()[$tokens[$stackPtr]['code']]) === false
        ) {
            return false;
        }

        // Handle `else if`.
        if ($tokens[$stackPtr]['code'] === \T_ELSE && isset($tokens[$stackPtr]['scope_opener']) === false) {
            $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if ($next !== false && $tokens[$next]['code'] === \T_IF) {
                $stackPtr = $next;
            }
        }

        /*
         * The scope markers are set. This is the simplest situation.
         */
        if (isset($tokens[$stackPtr]['scope_opener']) === true) {
            if ($allowEmpty === true) {
                return true;
            }

            // Check whether the body is empty.
            $start = ($tokens[$stackPtr]['scope_opener'] + 1);
            $end   = $phpcsFile->numTokens;
            if (isset($tokens[$stackPtr]['scope_closer']) === true) {
                $end = $tokens[$stackPtr]['scope_closer'];
            }

            $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $start, $end, true);
            if ($nextNonEmpty !== false) {
                return true;
            }

            return false;
        }

        /*
         * Control structure without scope markers.
         * Either single line statement or inline control structure.
         *
         * - Single line statement doesn't have a body and is therefore always empty.
         * - Inline control structure has to have a body and can never be empty.
         *
         * This code also needs to take live coding into account where a scope opener is found, but
         * no scope closer.
         */
        $searchStart = ($stackPtr + 1);
        if (isset($tokens[$stackPtr]['parenthesis_closer']) === true) {
            $searchStart = ($tokens[$stackPtr]['parenthesis_closer'] + 1);
        }

        $nextNonEmpty = $phpcsFile->findNext(
            Tokens::$emptyTokens,
            $searchStart,
            null,
            true
        );
        if ($nextNonEmpty === false
            || $tokens[$nextNonEmpty]['code'] === \T_SEMICOLON
            || $tokens[$nextNonEmpty]['code'] === \T_CLOSE_TAG
        ) {
            // Parse error or single line statement.
            return false;
        }

        if ($tokens[$nextNonEmpty]['code'] === \T_OPEN_CURLY_BRACKET) {
            if ($allowEmpty === true) {
                return true;
            }

            // Unrecognized scope opener due to parse error.
            $nextNext = $phpcsFile->findNext(
                Tokens::$emptyTokens,
                ($nextNonEmpty + 1),
                null,
                true
            );

            if ($nextNext === false) {
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Check whether an IF or ELSE token is part of an "else if".
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return bool
     */
    public static function isElseIf(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        if ($tokens[$stackPtr]['code'] === \T_ELSEIF) {
            return true;
        }

        if ($tokens[$stackPtr]['code'] !== \T_ELSE && $tokens[$stackPtr]['code'] !== \T_IF) {
            return false;
        }

        if ($tokens[$stackPtr]['code'] === \T_ELSE && isset($tokens[$stackPtr]['scope_opener']) === true) {
            return false;
        }

        switch ($tokens[$stackPtr]['code']) {
            case \T_ELSE:
                $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
                if ($next !== false && $tokens[$next]['code'] === \T_IF) {
                    return true;
                }
                break;

            case \T_IF:
                $previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
                if ($previous !== false && $tokens[$previous]['code'] === \T_ELSE) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Retrieve the exception(s) being caught in a CATCH condition.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return array<int, array<string, string|int>>
     *               Array with information about the caught Exception(s).
     *               The returned array will contain the following information for
     *               each caught exception:
     *               ```php
     *               0 => array(
     *                 'type'           => string,  // The type declaration for the exception being caught.
     *                 'type_token'     => integer, // The stack pointer to the start of the type declaration.
     *                 'type_end_token' => integer, // The stack pointer to the end of the type declaration.
     *               )
     *               ```
     *               In case of an invalid catch structure, the array may be empty.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_CATCH` token.
     */
    public static function getCaughtExceptions(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_CATCH) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_CATCH', $tokens[$stackPtr]['type']);
        }

        if (isset($tokens[$stackPtr]['parenthesis_opener'], $tokens[$stackPtr]['parenthesis_closer']) === false) {
            return [];
        }

        $opener     = $tokens[$stackPtr]['parenthesis_opener'];
        $closer     = $tokens[$stackPtr]['parenthesis_closer'];
        $exceptions = [];

        $foundName  = '';
        $firstToken = null;
        $lastToken  = null;

        for ($i = ($opener + 1); $i <= $closer; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']])) {
                continue;
            }

            if (isset(Collections::namespacedNameTokens()[$tokens[$i]['code']]) === false) {
                // Add the current exception to the result array if one was found.
                if ($foundName !== '') {
                    $exceptions[] = [
                        'type'           => $foundName,
                        'type_token'     => $firstToken,
                        'type_end_token' => $lastToken,
                    ];
                }

                if ($tokens[$i]['code'] === \T_BITWISE_OR) {
                    // Multi-catch. Reset and continue.
                    $foundName  = '';
                    $firstToken = null;
                    $lastToken  = null;
                    continue;
                }

                break;
            }

            if (isset($firstToken) === false) {
                $firstToken = $i;
            }

            $foundName .= $tokens[$i]['content'];
            $lastToken  = $i;
        }

        return $exceptions;
    }
}
