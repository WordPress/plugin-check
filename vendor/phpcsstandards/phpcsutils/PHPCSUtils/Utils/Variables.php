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
use PHPCSUtils\Utils\TextStrings;

/**
 * Utility functions for use when examining variables.
 *
 * @since 1.0.0 The `Variables::getMemberProperties()` method is based on and inspired by
 *              the method of the same name in the PHPCS native `PHP_CodeSniffer\Files\File` class.
 *              Also see {@see \PHPCSUtils\BackCompat\BCFile}.
 */
final class Variables
{

    /**
     * List of PHP Reserved variables.
     *
     * The array keys are the variable names without the leading dollar sign, the values indicate
     * whether the variable is a superglobal or not.
     *
     * The variables names are set without the leading dollar sign to allow this array
     * to be used with array index keys as well. Think: `'_GET'` in `$GLOBALS['_GET']`.}
     *
     * @link https://php.net/reserved.variables PHP Manual on reserved variables
     *
     * @since 1.0.0
     *
     * @var array<string, bool>
     */
    public static $phpReservedVars = [
        '_SERVER'              => true,
        '_GET'                 => true,
        '_POST'                => true,
        '_REQUEST'             => true,
        '_SESSION'             => true,
        '_ENV'                 => true,
        '_COOKIE'              => true,
        '_FILES'               => true,
        'GLOBALS'              => true,
        'http_response_header' => false,
        'argc'                 => false,
        'argv'                 => false,

        // Deprecated.
        'php_errormsg'         => false,

        // Removed PHP 5.4.0.
        'HTTP_SERVER_VARS'     => false,
        'HTTP_GET_VARS'        => false,
        'HTTP_POST_VARS'       => false,
        'HTTP_SESSION_VARS'    => false,
        'HTTP_ENV_VARS'        => false,
        'HTTP_COOKIE_VARS'     => false,
        'HTTP_POST_FILES'      => false,

        // Removed PHP 5.6.0.
        'HTTP_RAW_POST_DATA'   => false,
    ];

    /**
     * Retrieve the visibility and implementation properties of a class member variable.
     *
     * Main differences with the PHPCS version:
     * - Removed the parse error warning for properties in enums (PHPCS 4.0 makes the same change).
     *   This will now throw the same _"$stackPtr is not a class member var"_ runtime exception as
     *   other non-property variables passed to the method.
     * - Defensive coding against incorrect calls to this method.
     * - Support PHP 8.0 identifier name tokens in property types, cross-version PHP & PHPCS.
     * - The results of this function call are cached during a PHPCS run for faster response times.
     *
     * @see \PHP_CodeSniffer\Files\File::getMemberProperties()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::getMemberProperties() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_VARIABLE` token
     *                                               to acquire the properties for.
     *
     * @return array<string, mixed> Array with information about the class member variable.
     *               The format of the return value is:
     *               ```php
     *               array(
     *                 'scope'           => string,        // Public, private, or protected.
     *                 'scope_specified' => boolean,       // TRUE if the scope was explicitly specified.
     *                 'set_scope'       => string|false,  // Scope for asymmetric visibility.
     *                                                     // Either public, private, or protected or
     *                                                     // FALSE if no set scope is specified.
     *                 'is_static'       => boolean,       // TRUE if the static keyword was found.
     *                 'is_readonly'     => boolean,       // TRUE if the readonly keyword was found.
     *                 'is_final'        => boolean,       // TRUE if the final keyword was found.
     *                 'is_abstract'     => boolean,       // TRUE if the abstract keyword was found.
     *                 'type'            => string,        // The type of the var (empty if no type specified).
     *                 'type_token'      => integer|false, // The stack pointer to the start of the type
     *                                                     // or FALSE if there is no type.
     *                 'type_end_token'  => integer|false, // The stack pointer to the end of the type
     *                                                     // or FALSE if there is no type.
     *                 'nullable_type'   => boolean,       // TRUE if the type is preceded by the
     *                                                     // nullability operator.
     *               );
     *               ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_VARIABLE` token.
     * @throws \PHPCSUtils\Exceptions\ValueError          If the specified position is not a class member variable.
     */
    public static function getMemberProperties(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_VARIABLE) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_VARIABLE', $tokens[$stackPtr]['type']);
        }

        if (Scopes::isOOProperty($phpcsFile, $stackPtr) === false) {
            throw ValueError::create(2, '$stackPtr', 'must be the pointer to a class member var');
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $valid = Collections::propertyModifierKeywords() + Tokens::$emptyTokens;

        $scope          = 'public';
        $scopeSpecified = false;
        $setScope       = false;
        $isStatic       = false;
        $isReadonly     = false;
        $isFinal        = false;
        $isAbstract     = false;

        $startOfStatement = $phpcsFile->findPrevious(
            [
                \T_SEMICOLON,
                \T_OPEN_CURLY_BRACKET,
                \T_CLOSE_CURLY_BRACKET,
                \T_ATTRIBUTE_END,
            ],
            ($stackPtr - 1)
        );

        for ($i = ($startOfStatement + 1); $i < $stackPtr; $i++) {
            if (isset($valid[$tokens[$i]['code']]) === false) {
                break;
            }

            switch ($tokens[$i]['code']) {
                case \T_PUBLIC:
                    $scope          = 'public';
                    $scopeSpecified = true;
                    break;
                case \T_PRIVATE:
                    $scope          = 'private';
                    $scopeSpecified = true;
                    break;
                case \T_PROTECTED:
                    $scope          = 'protected';
                    $scopeSpecified = true;
                    break;
                case \T_PUBLIC_SET:
                    $setScope = 'public';
                    if ($scopeSpecified === false) {
                        $scope = 'public';
                    }
                    break;
                case \T_PROTECTED_SET:
                    $setScope = 'protected';
                    if ($scopeSpecified === false) {
                        $scope = 'public';
                    }
                    break;
                case \T_PRIVATE_SET:
                    $setScope = 'private';
                    if ($scopeSpecified === false) {
                        $scope = 'public';
                    }
                    break;
                case \T_STATIC:
                    $isStatic = true;
                    break;
                case \T_READONLY:
                    $isReadonly = true;
                    break;
                case \T_FINAL:
                    $isFinal = true;
                    break;
                case \T_ABSTRACT:
                    $isAbstract = true;
                    break;
            }
        }

        $type               = '';
        $typeToken          = false;
        $typeEndToken       = false;
        $nullableType       = false;
        $propertyTypeTokens = Collections::propertyTypeTokens();

        if ($i < $stackPtr) {
            // We've found a type.
            for ($i; $i < $stackPtr; $i++) {
                if ($tokens[$i]['code'] === \T_VARIABLE) {
                    // Hit another variable in a group definition.
                    break;
                }

                if ($tokens[$i]['code'] === \T_NULLABLE) {
                    $nullableType = true;
                }

                if (isset($propertyTypeTokens[$tokens[$i]['code']]) === true) {
                    $typeEndToken = $i;
                    if ($typeToken === false) {
                        $typeToken = $i;
                    }

                    $type .= $tokens[$i]['content'];
                }
            }

            if ($type !== '' && $nullableType === true) {
                $type = '?' . $type;
            }
        }

        $returnValue = [
            'scope'           => $scope,
            'scope_specified' => $scopeSpecified,
            'set_scope'       => $setScope,
            'is_static'       => $isStatic,
            'is_readonly'     => $isReadonly,
            'is_final'        => $isFinal,
            'is_abstract'     => $isAbstract,
            'type'            => $type,
            'type_token'      => $typeToken,
            'type_end_token'  => $typeEndToken,
            'nullable_type'   => $nullableType,
        ];

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $returnValue);
        return $returnValue;
    }

    /**
     * Retrieve the stack pointers to the attribute openers for any attribute block which applies to an OO property
     * or function declaration parameters.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the variable token to
     *                                               acquire the attributes for.
     *
     * @return array<int> Array with the stack pointers to the applicable attribute openers
     *                    or an empty array if there are no attributes attached to the OO property
     *                    or function declaration parameter.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_VARIABLE` token.
     * @throws \PHPCSUtils\Exceptions\ValueError          If the token passed does not point to an OO property token
     *                                                    or a parameter in a function declaration.
     */
    public static function getAttributeOpeners(File $phpcsFile, $stackPtr)
    {
        return AttributeHelper::getOpeners($phpcsFile, $stackPtr, 'variable');
    }

    /**
     * Verify if a given variable name is the name of a PHP reserved variable.
     *
     * @see \PHPCSUtils\Utils\Variables::$phpReservedVars List of variables names reserved by PHP.
     *
     * @since 1.0.0
     *
     * @param string $name The full variable name with or without leading dollar sign.
     *                     This allows for passing an array key variable name, such as
     *                     `'_GET'` retrieved from `$GLOBALS['_GET']`.
     *                     > Note: when passing an array key, string quotes are expected
     *                     to have been stripped already.
     *                     Also see: {@see \PHPCSUtils\Utils\TextStrings::stripQuotes()}.
     *
     * @return bool
     */
    public static function isPHPReservedVarName($name)
    {
        if (\strpos($name, '$') === 0) {
            $name = \substr($name, 1);
        }

        return (isset(self::$phpReservedVars[$name]) === true);
    }

    /**
     * Verify if a given variable or array key token points to a PHP superglobal.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position in the stack of a `T_VARIABLE`
     *                                               token or of the `T_CONSTANT_ENCAPSED_STRING`
     *                                               array key to a variable in `$GLOBALS`.
     *
     * @return bool `TRUE` if this points to a superglobal; `FALSE` when not.
     *              > Note: This includes returning `FALSE` when an unsupported token has
     *              been passed, when a `T_CONSTANT_ENCAPSED_STRING` has been passed which
     *              is not an array index key; or when it is, but is not an index to the
     *              `$GLOBALS` variable.
     */
    public static function isSuperglobal(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]) === false
            || ($tokens[$stackPtr]['code'] !== \T_VARIABLE
                && $tokens[$stackPtr]['code'] !== \T_CONSTANT_ENCAPSED_STRING)
        ) {
            return false;
        }

        $content = $tokens[$stackPtr]['content'];

        if ($tokens[$stackPtr]['code'] === \T_CONSTANT_ENCAPSED_STRING) {
            $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
            $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if (($prev === false || $tokens[$prev]['code'] !== \T_OPEN_SQUARE_BRACKET)
                || ($next === false || $tokens[$next]['code'] !== \T_CLOSE_SQUARE_BRACKET)
            ) {
                // Not a single string array index key.
                return false;
            }

            $pprev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prev - 1), null, true);
            if ($pprev === false
                || $tokens[$pprev]['code'] !== \T_VARIABLE
                || $tokens[$pprev]['content'] !== '$GLOBALS'
            ) {
                // Not accessing the `$GLOBALS` array.
                return false;
            }

            // Strip quotes.
            $content = TextStrings::stripQuotes($content);
        }

        return self::isSuperglobalName($content);
    }

    /**
     * Verify if a given variable name is the name of a PHP superglobal.
     *
     * @since 1.0.0
     *
     * @param string $name The full variable name with or without leading dollar sign.
     *                     This allows for passing an array key variable name, such as
     *                     `'_GET'` retrieved from `$GLOBALS['_GET']`.
     *                     > Note: when passing an array key, string quotes are expected
     *                     to have been stripped already.
     *                     Also see: {@see \PHPCSUtils\Utils\TextStrings::stripQuotes()}.
     *
     * @return bool
     */
    public static function isSuperglobalName($name)
    {
        if (\strpos($name, '$') === 0) {
            $name = \substr($name, 1);
        }

        if (isset(self::$phpReservedVars[$name]) === false) {
            return false;
        }

        return self::$phpReservedVars[$name];
    }
}
