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
use PHPCSUtils\Exceptions\ValueError;
use PHPCSUtils\Internal\AttributeHelper;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Scopes;

/**
 * Utility functions for use when examining constants declared using the "const" keyword.
 *
 * @since 1.1.0
 */
final class Constants
{

    /**
     * Retrieve the visibility and implementation properties of an OO constant.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_CONST` token
     *                                               to acquire the properties for.
     *
     * @return array<string, string|int|bool> Array with information about the constant declaration.
     *         The format of the return value is:
     *         ```php
     *         array(
     *             'scope'           => string,        // Public, private, or protected.
     *             'scope_token'     => integer|false, // The stack pointer to the scope keyword or
     *                                                 // FALSE if the scope was not explicitly specified.
     *             'is_final'        => boolean,       // TRUE if the final keyword was found.
     *             'final_token'     => integer|false, // The stack pointer to the final keyword
     *                                                 // or FALSE if the const is not declared final.
     *             'type'            => string,        // The type of the const (empty if no type specified).
     *             'type_token'      => integer|false, // The stack pointer to the start of the type
     *                                                 // or FALSE if there is no type.
     *             'type_end_token'  => integer|false, // The stack pointer to the end of the type
     *                                                 // or FALSE if there is no type.
     *             'nullable_type'   => boolean,       // TRUE if the type is preceded by the
     *                                                 // nullability operator.
     *             'name_token'      => integer,       // The stack pointer to the constant name.
     *                                                 // Note: for group declarations this points to the
     *                                                 // name of the first constant.
     *             'equal_token'     => integer,       // The stack pointer to the equal sign.
     *                                                 // Note: for group declarations this points to the
     *                                                 // equal sign of the first constant.
     *         );
     *         ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_CONST` token.
     * @throws \PHPCSUtils\Exceptions\ValueError          If the specified position is not an OO constant.
     */
    public static function getProperties(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_CONST) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_CONST', $tokens[$stackPtr]['type']);
        }

        if (Scopes::isOOConstant($phpcsFile, $stackPtr) === false) {
            throw ValueError::create(2, '$stackPtr', 'must be the pointer to an OO constant');
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $find          = [\T_EQUAL, \T_SEMICOLON, \T_OPEN_CURLY_BRACKET, \T_CLOSE_CURLY_BRACKET, \T_CLOSE_TAG];
        $assignmentPtr = $phpcsFile->findNext($find, ($stackPtr + 1));
        if ($assignmentPtr === false || $tokens[$assignmentPtr]['code'] !== \T_EQUAL) {
            // Probably a parse error. Don't cache the result.
            throw ValueError::create(2, '$stackPtr', 'must be the pointer to an OO constant');
        }

        $namePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($assignmentPtr - 1), ($stackPtr + 1), true);

        $returnValue = [
            'scope'          => 'public',
            'scope_token'    => false,
            'is_final'       => false,
            'final_token'    => false,
            'type'           => '',
            'type_token'     => false,
            'type_end_token' => false,
            'nullable_type'  => false,
            'name_token'     => $namePtr,
            'equal_token'    => $assignmentPtr,
        ];

        for ($i = ($stackPtr - 1);; $i--) {
            // Skip over potentially large docblocks.
            if ($tokens[$i]['code'] === \T_DOC_COMMENT_CLOSE_TAG
                && isset($tokens[$i]['comment_opener'])
            ) {
                $i = $tokens[$i]['comment_opener'];
                continue;
            }

            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === true) {
                continue;
            }

            switch ($tokens[$i]['code']) {
                case \T_PUBLIC:
                    $returnValue['scope']       = 'public';
                    $returnValue['scope_token'] = $i;
                    break;

                case \T_PROTECTED:
                    $returnValue['scope']       = 'protected';
                    $returnValue['scope_token'] = $i;
                    break;

                case \T_PRIVATE:
                    $returnValue['scope']       = 'private';
                    $returnValue['scope_token'] = $i;
                    break;

                case \T_FINAL:
                    $returnValue['is_final']    = true;
                    $returnValue['final_token'] = $i;
                    break;

                default:
                    // Any other token means that the start of the statement has been reached.
                    break 2;
            }
        }

        $type               = '';
        $typeToken          = false;
        $typeEndToken       = false;
        $constantTypeTokens = Collections::constantTypeTokens();

        // Now, let's check for a type.
        for ($i = ($stackPtr + 1); $i < $namePtr; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === true) {
                continue;
            }

            if ($tokens[$i]['code'] === \T_NULLABLE) {
                $returnValue['nullable_type'] = true;
                continue;
            }

            if (isset($constantTypeTokens[$tokens[$i]['code']]) === true) {
                $typeEndToken = $i;
                if ($typeToken === false) {
                    $typeToken = $i;
                }

                $type .= $tokens[$i]['content'];
            }
        }

        if ($type !== '' && $returnValue['nullable_type'] === true) {
            $type = '?' . $type;
        }

        $returnValue['type']           = $type;
        $returnValue['type_token']     = $typeToken;
        $returnValue['type_end_token'] = $typeEndToken;

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $returnValue);
        return $returnValue;
    }

    /**
     * Retrieve the stack pointers to the attribute openers for any attribute block
     * which applies to the constant declaration.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_CONST` token
     *                                               to acquire the attributes for.
     *
     * @return array<int> Array with the stack pointers to the applicable attribute openers
     *                    or an empty array if there are no attributes attached to the constant declaration.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_CONST` token.
     */
    public static function getAttributeOpeners(File $phpcsFile, $stackPtr)
    {
        return AttributeHelper::getOpeners($phpcsFile, $stackPtr, 'constant');
    }
}
