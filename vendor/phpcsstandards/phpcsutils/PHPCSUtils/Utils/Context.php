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
use PHPCSUtils\Utils\Parentheses;

/**
 * Utility functions for examining in which context a certain token is used.
 *
 * Example use-cases:
 * - A sniff looking for the use of certain variables may want to disregard the "use"
 *   of these variables within a call to `isset()` or `empty()`.
 * - A sniff looking for incrementor/decrementors may want to disregard these when used
 *   as the third expression in a `for()` condition.
 *
 * @since 1.0.0
 */
final class Context
{

    /**
     * Check whether an arbitrary token is within a call to empty().
     *
     * _This method is a thin, descriptive wrapper around the {@see Parentheses::getLastOwner()} method.
     * For more complex/combined queries, it is recommended to call the {@see Parentheses::getLastOwner()}
     * method directly._
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return bool
     */
    public static function inEmpty(File $phpcsFile, $stackPtr)
    {
        return (Parentheses::getLastOwner($phpcsFile, $stackPtr, \T_EMPTY) !== false);
    }

    /**
     * Check whether an arbitrary token is within a call to isset().
     *
     * _This method is a thin, descriptive wrapper around the {@see Parentheses::getLastOwner()} method.
     * For more complex/combined queries, it is recommended to call the {@see Parentheses::getLastOwner()}
     * method directly._
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return bool
     */
    public static function inIsset(File $phpcsFile, $stackPtr)
    {
        return (Parentheses::getLastOwner($phpcsFile, $stackPtr, \T_ISSET) !== false);
    }

    /**
     * Check whether an arbitrary token is within a call to unset().
     *
     * _This method is a thin, descriptive wrapper around the {@see Parentheses::getLastOwner()} method.
     * For more complex/combined queries, it is recommended to call the {@see Parentheses::getLastOwner()}
     * method directly._
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return bool
     */
    public static function inUnset(File $phpcsFile, $stackPtr)
    {
        return (Parentheses::getLastOwner($phpcsFile, $stackPtr, \T_UNSET) !== false);
    }

    /**
     * Check whether an arbitrary token is within an attribute.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return bool
     */
    public static function inAttribute(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        if (isset($tokens[$stackPtr]['attribute_opener'], $tokens[$stackPtr]['attribute_closer']) === false) {
            return false;
        }

        return ($stackPtr !== $tokens[$stackPtr]['attribute_opener']
            && $stackPtr !== $tokens[$stackPtr]['attribute_closer']);
    }

    /**
     * Check whether an arbitrary token is in a foreach condition and if so, in which part:
     * before or after the "as".
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return string|false String `'beforeAs'`, `'as'` or `'afterAs'` when the token is within
     *                      a `foreach` condition.
     *                      `FALSE` in all other cases, including for parse errors.
     */
    public static function inForeachCondition(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        $foreach = Parentheses::getLastOwner($phpcsFile, $stackPtr, \T_FOREACH);
        if ($foreach === false) {
            return false;
        }

        $opener = $tokens[$foreach]['parenthesis_opener'];
        $closer = $tokens[$foreach]['parenthesis_closer'];

        for ($i = ($opener + 1); $i < $closer; $i++) {
            // Skip past a short array declaration in the "before as" part.
            if (isset($tokens[$i]['bracket_closer'])) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }

            // Skip past a long array declaration in the "before as" part.
            if (isset($tokens[$i]['parenthesis_closer'])) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            if ($tokens[$i]['code'] === \T_AS) {
                $asPtr = $i;
                break;
            }
        }

        if (isset($asPtr) === false) {
            // Parse error or live coding.
            return false;
        }

        if ($asPtr === $stackPtr) {
            return 'as';
        }

        if ($stackPtr < $asPtr) {
            return 'beforeAs';
        }

        return 'afterAs';
    }

    /**
     * Check whether an arbitrary token is in a for condition and if so, in which part:
     * the first, second or third expression.
     *
     * Note: the semicolons separating the conditions are regarded as belonging with the
     * expression before it.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     *
     * @return string|false String `'expr1'`, `'expr2'` or `'expr3'` when the token is within
     *                      a `for` condition.
     *                      `FALSE` in all other cases, including for parse errors.
     */
    public static function inForCondition(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        $for = Parentheses::getLastOwner($phpcsFile, $stackPtr, \T_FOR);
        if ($for === false) {
            return false;
        }

        $semicolons = [];
        $count      = 0;
        $opener     = $tokens[$for]['parenthesis_opener'];
        $closer     = $tokens[$for]['parenthesis_closer'];
        $level      = $tokens[$for]['level'];
        $parens     = 1;

        if (isset($tokens[$for]['nested_parenthesis'])) {
            $parens = (\count($tokens[$for]['nested_parenthesis']) + 1);
        }

        for ($i = ($opener + 1); $i < $closer; $i++) {
            if ($tokens[$i]['code'] !== \T_SEMICOLON) {
                continue;
            }

            if ($tokens[$i]['level'] !== $level
                || \count($tokens[$i]['nested_parenthesis']) !== $parens
            ) {
                // Disregard semicolons at lower nesting/condition levels.
                continue;
            }

            ++$count;
            $semicolons[$count] = $i;
        }

        if ($count !== 2) {
            return false;
        }

        foreach ($semicolons as $key => $ptr) {
            if ($stackPtr <= $ptr) {
                return 'expr' .  $key;
            }
        }

        return 'expr3';
    }
}
