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
use PHPCSUtils\BackCompat\BCFile;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\RuntimeException;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\Parentheses;

/**
 * Utility functions for use when examining T_NAMESPACE tokens and to determine the
 * namespace of arbitrary tokens.
 *
 * @link https://www.php.net/language.namespaces PHP Manual on namespaces.
 *
 * @since 1.0.0
 */
final class Namespaces
{

    /**
     * Determine what a T_NAMESPACE token is used for.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_NAMESPACE` token.
     *
     * @return string Either `'declaration'`, `'operator'` or an empty string.
     *                An empty string will be returned if it couldn't be
     *                reliably determined what the `T_NAMESPACE` token is used for,
     *                which, in most cases, will mean the code contains a parse/fatal error.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_NAMESPACE` token.
     */
    public static function getType(File $phpcsFile, $stackPtr)
    {
        static $findAfter;

        if (isset($findAfter) === false) {
            /*
             * Set up array of tokens which can only be used in combination with the keyword as operator
             * and which cannot be confused with other keywords.
             */
            $findAfter = Tokens::$assignmentTokens
                + Tokens::$comparisonTokens
                + Tokens::$operators
                + Tokens::$castTokens
                + Tokens::$blockOpeners
                + Collections::incrementDecrementOperators()
                + Collections::objectOperators()
                + Collections::shortArrayListOpenTokensBC();

            $findAfter[\T_OPEN_CURLY_BRACKET] = \T_OPEN_CURLY_BRACKET;
        }

        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_NAMESPACE) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_NAMESPACE', $tokens[$stackPtr]['type']);
        }

        $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($next === false) {
            // Live coding or parse error.
            return '';
        }

        if (empty($tokens[$stackPtr]['conditions']) === false
            || empty($tokens[$stackPtr]['nested_parenthesis']) === false
        ) {
            /*
             * Namespace declarations are only allowed at top level, so this can definitely not
             * be a namespace declaration.
             */
            if ($tokens[$next]['code'] === \T_NS_SEPARATOR) {
                return 'operator';
            }

            return '';
        }

        $start = BCFile::findStartOfStatement($phpcsFile, $stackPtr);
        if ($start === $stackPtr
            && ($tokens[$next]['code'] === \T_STRING
                || $tokens[$next]['code'] === \T_NAME_QUALIFIED
                || $tokens[$next]['code'] === \T_OPEN_CURLY_BRACKET)
        ) {
            return 'declaration';
        }

        if (($tokens[$next]['code'] === \T_NS_SEPARATOR
            || $tokens[$next]['code'] === \T_NAME_FULLY_QUALIFIED) // PHP >= 8.0 parse error.
            && ($start !== $stackPtr
                || $phpcsFile->findNext($findAfter, ($stackPtr + 1), null, false, null, true) !== false)
        ) {
            return 'operator';
        }

        return '';
    }

    /**
     * Determine whether a T_NAMESPACE token is the keyword for a namespace declaration.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of a `T_NAMESPACE` token.
     *
     * @return bool `TRUE` if the token passed is the keyword for a namespace declaration.
     *              `FALSE` if not.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_NAMESPACE` token.
     */
    public static function isDeclaration(File $phpcsFile, $stackPtr)
    {
        return (self::getType($phpcsFile, $stackPtr) === 'declaration');
    }

    /**
     * Determine whether a T_NAMESPACE token is used as an operator.
     *
     * @link https://www.php.net/language.namespaces.nsconstants PHP Manual about the use of the
     *                                                           namespace keyword as an operator.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of a `T_NAMESPACE` token.
     *
     * @return bool `TRUE` if the namespace token passed is used as an operator. `FALSE` if not.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_NAMESPACE` token.
     */
    public static function isOperator(File $phpcsFile, $stackPtr)
    {
        return (self::getType($phpcsFile, $stackPtr) === 'operator');
    }

    /**
     * Get the complete namespace name as declared.
     *
     * For hierarchical namespaces, the namespace name will be composed of several tokens,
     * i.e. "MyProject\Sub\Level", which will be returned together as one string.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of a `T_NAMESPACE` token.
     * @param bool                        $clean     Optional. Whether to get the name stripped
     *                                               of potentially interlaced whitespace and/or
     *                                               comments. Defaults to `true`.
     *
     * @return string|false The namespace name; or `FALSE` if the specified position is not a
     *                      `T_NAMESPACE` token, the token points to a namespace operator
     *                      or when parse errors are encountered/during live coding.
     *                      > Note: The name can be an empty string for a valid global
     *                      namespace declaration.
     */
    public static function getDeclaredName(File $phpcsFile, $stackPtr, $clean = true)
    {
        try {
            if (self::isDeclaration($phpcsFile, $stackPtr) === false) {
                // Not a namespace declaration.
                return false;
            }
        } catch (RuntimeException $e) {
            // Non-existent token or not a namespace keyword token.
            return false;
        }

        $endOfStatement = $phpcsFile->findNext(Collections::namespaceDeclarationClosers(), ($stackPtr + 1));
        if ($endOfStatement === false) {
            // Live coding or parse error.
            return false;
        }

        $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), ($endOfStatement + 1), true);
        if ($next === $endOfStatement) {
            // Declaration of global namespace. I.e.: namespace {}.
            // If not a scoped {} namespace declaration, no name/global declarations are invalid
            // and result in parse errors, but that's not our concern.
            return '';
        }

        if ($clean === false) {
            return \trim(GetTokensAsString::origContent($phpcsFile, $next, ($endOfStatement - 1)));
        }

        return \trim(GetTokensAsString::noEmpties($phpcsFile, $next, ($endOfStatement - 1)));
    }

    /**
     * Find the stack pointer to the namespace declaration applicable for an arbitrary token.
     *
     * Take note:
     * 1. When a namespace declaration token or a token which is part of the namespace
     *    name is passed to this method, the result will be `false` as technically, these tokens
     *    are not _within_ a namespace.
     * 2. This method has no opinion on whether the token passed is actually _subject_
     *    to namespacing.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The token for which to determine
     *                                               the namespace.
     *
     * @return int|false Token pointer to the namespace keyword for the applicable namespace
     *                   declaration; or `FALSE` if it couldn't be determined or
     *                   if no namespace applies.
     */
    public static function findNamespacePtr(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        // The namespace keyword in a namespace declaration is itself not namespaced.
        if ($tokens[$stackPtr]['code'] === \T_NAMESPACE
            && self::isDeclaration($phpcsFile, $stackPtr) === true
        ) {
            return false;
        }

        // Check for scoped namespace {}.
        $namespacePtr = Conditions::getCondition($phpcsFile, $stackPtr, \T_NAMESPACE);
        if ($namespacePtr !== false) {
            return $namespacePtr;
        }

        /*
         * Not in a scoped namespace, so let's see if we can find a non-scoped namespace instead.
         * Keeping in mind that:
         * - there can be multiple non-scoped namespaces in a file (bad practice, but is allowed);
         * - the namespace keyword can also be used as an operator;
         * - a non-named namespace resolves to the global namespace;
         * - and that namespace declarations can't be nested in anything, so we can skip over any
         *   nesting structures.
         */
        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        // Start by breaking out of any scoped structures this token is in.
        $prev           = $stackPtr;
        $firstCondition = Conditions::getFirstCondition($phpcsFile, $stackPtr);
        if ($firstCondition !== false) {
            $prev = $firstCondition;
        }

        // And break out of any surrounding parentheses as well.
        $firstParensOpener = Parentheses::getFirstOpener($phpcsFile, $prev);
        if ($firstParensOpener !== false) {
            $prev = $firstParensOpener;
        }

        $find        = [
            \T_NAMESPACE,
            \T_CLOSE_CURLY_BRACKET,
            \T_CLOSE_PARENTHESIS,
            \T_CLOSE_SHORT_ARRAY,
            \T_CLOSE_SQUARE_BRACKET,
            \T_DOC_COMMENT_CLOSE_TAG,
            \T_ATTRIBUTE_END,
        ];
        $returnValue = false;

        do {
            $prev = $phpcsFile->findPrevious($find, ($prev - 1));
            if ($prev === false) {
                break;
            }

            if ($tokens[$prev]['code'] === \T_CLOSE_CURLY_BRACKET) {
                // Stop if we encounter a scoped namespace declaration as we already know we're not in one.
                if (isset($tokens[$prev]['scope_condition']) === true
                    && $tokens[$tokens[$prev]['scope_condition']]['code'] === \T_NAMESPACE
                ) {
                    break;
                }

                // Skip over other scoped structures for efficiency.
                if (isset($tokens[$prev]['scope_condition']) === true) {
                    $prev = $tokens[$prev]['scope_condition'];
                } elseif (isset($tokens[$prev]['scope_opener']) === true) {
                    // Shouldn't be possible, but just in case.
                    $prev = $tokens[$prev]['scope_opener']; // @codeCoverageIgnore
                }

                continue;
            }

            // Skip over other nesting structures for efficiency.
            if (isset($tokens[$prev]['bracket_opener']) === true) {
                $prev = $tokens[$prev]['bracket_opener'];
                continue;
            }

            if (isset($tokens[$prev]['parenthesis_owner']) === true) {
                $prev = $tokens[$prev]['parenthesis_owner'];
                continue;
            } elseif (isset($tokens[$prev]['parenthesis_opener']) === true) {
                $prev = $tokens[$prev]['parenthesis_opener'];
                continue;
            }

            // Skip over potentially large attributes.
            if (isset($tokens[$prev]['attribute_opener'])) {
                $prev = $tokens[$prev]['attribute_opener'];
                continue;
            }

            // Skip over potentially large docblocks.
            if (isset($tokens[$prev]['comment_opener'])) {
                $prev = $tokens[$prev]['comment_opener'];
                continue;
            }

            // So this is a namespace keyword, check if it's a declaration.
            if ($tokens[$prev]['code'] === \T_NAMESPACE
                && self::isDeclaration($phpcsFile, $prev) === true
            ) {
                // Now make sure the token was not part of the declaration.
                $endOfStatement = $phpcsFile->findNext(Collections::namespaceDeclarationClosers(), ($prev + 1));
                if ($endOfStatement > $stackPtr) {
                    // Token is part of the declaration, return false.
                    break;
                }

                $returnValue = $prev;
                break;
            }
        } while (true);

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $returnValue);
        return $returnValue;
    }

    /**
     * Determine the namespace name an arbitrary token lives in.
     *
     * Note: this method has no opinion on whether the token passed is actually _subject_
     * to namespacing.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The token for which to determine
     *                                               the namespace.
     *
     * @return string Namespace name; or an empty string if the namespace couldn't be
     *                determined or when no namespace applies.
     */
    public static function determineNamespace(File $phpcsFile, $stackPtr)
    {
        $namespacePtr = self::findNamespacePtr($phpcsFile, $stackPtr);
        if ($namespacePtr === false) {
            return '';
        }

        $namespace = self::getDeclaredName($phpcsFile, $namespacePtr);
        if ($namespace !== false) {
            return $namespace;
        }

        return '';
    }
}
