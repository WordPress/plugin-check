<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2025 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Utils;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;

/**
 * Utility functions to retrieve information related to attributes.
 *
 * @since 1.2.0
 */
final class AttributeBlock
{

    /**
     * Given an attribute opener, find the relevant construct token the attribute applies to.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the T_ATTRIBUTE (attribute opener) token.
     *
     * @return int|false The stackPtr to the OO, function, closure, fn, constant, or variable token the attribute block
     *                   applies to; or FALSE if the attribute target could not be determined.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not an attribute token and
     *                                                    not within an attribute.
     */
    public static function appliesTo(File $phpcsFile, $stackPtr)
    {
        static $allowedTokens;

        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_ATTRIBUTE
            && $tokens[$stackPtr]['code'] !== \T_ATTRIBUTE_END
            && Context::inAttribute($phpcsFile, $stackPtr) === false
        ) {
            $acceptedTokens = 'T_ATTRIBUTE, T_ATTRIBUTE_END or a token within an attribute';
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        if (isset($tokens[$stackPtr]['attribute_closer']) === false) {
            return false;
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $attributeTarget = false;

        if (isset($allowedTokens) === false) {
            /*
             * Allow every type of token which could be encountered between the attribute and the target construct,
             * even though some are only allowed in specific circumstances or for specific constructs.
             * That, however, is not a concern for this method.
             * Parse error tolerance prevails to give sniffs the most flexibility.
             */
            $allowedTokens = Tokens::$emptyTokens;

            // OO constants.
            $allowedTokens += Collections::constantModifierKeywords();

            // Functions, closures, arrow functions and methods.
            $allowedTokens += [\T_STATIC => \T_STATIC];
            $allowedTokens += Tokens::$methodPrefixes;

            // OO declarations.
            $allowedTokens += Collections::classModifierKeywords();

            // Properties and parameters
            $allowedTokens += [\T_NULLABLE => \T_NULLABLE];
            $allowedTokens += Collections::propertyModifierKeywords();
            $allowedTokens += Collections::propertyTypeTokens();
            $allowedTokens += Collections::parameterTypeTokens();
            $allowedTokens += [
                \T_BITWISE_AND => \T_BITWISE_AND,
                \T_ELLIPSIS    => \T_ELLIPSIS,
            ];
        }

        for ($i = ($tokens[$stackPtr]['attribute_closer'] + 1); $i <= $phpcsFile->numTokens; $i++) {
            // Skip over potentially large docblocks.
            if ($tokens[$i]['code'] === \T_DOC_COMMENT_OPEN_TAG
                && isset($tokens[$i]['comment_closer'])
            ) {
                $i = $tokens[$i]['comment_closer'];
                continue;
            }

            if ($tokens[$i]['code'] === \T_ATTRIBUTE
                && isset($tokens[$i]['attribute_closer']) === true
            ) {
                $i = $tokens[$i]['attribute_closer'];
                continue;
            }

            if (isset($allowedTokens[$tokens[$i]['code']])) {
                continue;
            }

            // Okay, so this _must_ be the token for the construct.
            if (isset(Tokens::$ooScopeTokens[$tokens[$i]['code']]) === true
                || isset(Collections::functionDeclarationTokens()[$tokens[$i]['code']]) === true
                || $tokens[$i]['code'] === \T_CONST
                || $tokens[$i]['code'] === \T_VARIABLE
            ) {
                $attributeTarget = $i;
            }

            break;
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $attributeTarget);
        return $attributeTarget;
    }

    /**
     * Retrieve information on each attribute instantiation within an attribute block.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the T_ATTRIBUTE (attribute opener) token.
     *
     * @return array<array<string, int|string|false>>
     *               A multi-dimentional array with information on each attribute instantiation in the block.
     *               The information gathered about each attribute instantiation is in the following format:
     *               ```php
     *               array(
     *                 'name'        => string,    // The full name of the attribute being instantiated.
     *                                             // This will be name as passed without namespace resolution.
     *                 'name_token'  => int,       // The stack pointer to the last token in the attribute name.
     *                                             // Pro-tip: this token can be passed on to the methods in the
     *                                             // {@see PassedParameters} class to retrieve the
     *                                             // parameters passed to the attribute constructor.
     *                 'start'       => int,       // The stack pointer to the first token in the attribute instantiation.
     *                                             // Note: this may be a leading whitespace/comment token.
     *                 'end'         => int,       // The stack pointer to the last token in the attribute instantiation.
     *                                             // Note: this may be a trailing whitespace/comment token.
     *                 'comma_token' => int|false, // The stack pointer to the comma after the attribute instantiation
     *                                             // or FALSE if this is the last attribute and there is no comma.
     *               )
     *               ```
     *               If no attributes are found, an empty array will be returned.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_ATTRIBUTE` token.
     */
    public static function getAttributes(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_ATTRIBUTE) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_ATTRIBUTE', $tokens[$stackPtr]['type']);
        }

        if (isset($tokens[$stackPtr]['attribute_closer']) === false) {
            return [];
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $opener = $stackPtr;
        $closer = $tokens[$stackPtr]['attribute_closer'];

        $attributes  = [];
        $currentName = '';
        $nameToken   = null;
        $start       = ($opener + 1);

        for ($i = ($opener + 1); $i <= $closer; $i++) {
            // Skip over potentially large docblocks.
            if ($tokens[$i]['code'] === \T_DOC_COMMENT_OPEN_TAG
                && isset($tokens[$i]['comment_closer'])
            ) {
                $i = $tokens[$i]['comment_closer'];
                continue;
            }

            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']])) {
                continue;
            }

            if (isset(Collections::namespacedNameTokens()[$tokens[$i]['code']])) {
                $currentName .= $tokens[$i]['content'];
                $nameToken    = $i;
                continue;
            }

            if ($tokens[$i]['code'] === \T_OPEN_PARENTHESIS
                && isset($tokens[$i]['parenthesis_closer']) === true
            ) {
                // Skip over whatever is passed to the Attribute constructor.
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            if ($tokens[$i]['code'] === \T_COMMA
                || $i === $closer
            ) {
                // We've reached the end of the name.
                if ($currentName === '') {
                    // Parse error. Stop parsing this attribute block.
                    break;
                }

                $attributes[] = [
                    'name'        => $currentName,
                    'name_token'  => $nameToken,
                    'start'       => $start,
                    'end'         => ($i - 1),
                    'comma_token' => ($tokens[$i]['code'] === \T_COMMA ? $i : false),
                ];

                if ($i === $closer) {
                    break;
                }

                // Check if there are more tokens before the attribute closer.
                // Prevents atrtibute blocks with trailing comma's from setting an extra attribute.
                $hasNext = $phpcsFile->findNext(Tokens::$emptyTokens, ($i + 1), $closer, true);
                if ($hasNext === false) {
                    break;
                }

                // Prepare for the next attribute instantiation.
                $currentName = '';
                $nameToken   = null;
                $start       = ($i + 1);
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $attributes);
        return $attributes;
    }

    /**
     * Count the number of attributes being instantiated in an attribute block.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the T_ATTRIBUTE (attribute opener) token.
     *
     * @return int
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_ATTRIBUTE` token.
     */
    public static function countAttributes(File $phpcsFile, $stackPtr)
    {
        return \count(self::getAttributes($phpcsFile, $stackPtr));
    }
}
