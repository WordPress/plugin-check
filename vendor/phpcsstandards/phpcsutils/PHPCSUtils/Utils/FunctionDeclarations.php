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
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\Scopes;
use PHPCSUtils\Utils\UseStatements;

/**
 * Utility functions for use when examining function declaration statements.
 *
 * @since 1.0.0 The `FunctionDeclarations::getProperties()` and the
 *              `FunctionDeclarations::getParameters()` methods are based on and
 *              inspired by respectively the `getMethodProperties()`
 *              and `getMethodParameters()` methods in the PHPCS native
 *              `PHP_CodeSniffer\Files\File` class.
 *              Also see {@see \PHPCSUtils\BackCompat\BCFile}.
 */
final class FunctionDeclarations
{

    /**
     * A list of all PHP magic functions.
     *
     * The array keys contain the function names. The values contain the name without the double underscore.
     *
     * The function names are listed in lowercase as these function names in PHP are case-insensitive
     * and comparisons against this list should therefore always be done in a case-insensitive manner.
     *
     * @since 1.0.0
     *
     * @var array<string, string>
     */
    public static $magicFunctions = [
        '__autoload' => 'autoload',
    ];

    /**
     * A list of all PHP magic methods.
     *
     * The array keys contain the method names. The values contain the name without the double underscore.
     *
     * The method names are listed in lowercase as these method names in PHP are case-insensitive
     * and comparisons against this list should therefore always be done in a case-insensitive manner.
     *
     * @since 1.0.0
     *
     * @var array<string, string>
     */
    public static $magicMethods = [
        '__construct'   => 'construct',
        '__destruct'    => 'destruct',
        '__call'        => 'call',
        '__callstatic'  => 'callstatic',
        '__get'         => 'get',
        '__set'         => 'set',
        '__isset'       => 'isset',
        '__unset'       => 'unset',
        '__sleep'       => 'sleep',
        '__wakeup'      => 'wakeup',
        '__tostring'    => 'tostring',
        '__set_state'   => 'set_state',
        '__clone'       => 'clone',
        '__invoke'      => 'invoke',
        '__debuginfo'   => 'debuginfo',   // PHP >= 5.6.
        '__serialize'   => 'serialize',   // PHP >= 7.4.
        '__unserialize' => 'unserialize', // PHP >= 7.4.
    ];

    /**
     * A list of all PHP native non-magic methods starting with a double underscore.
     *
     * These come from PHP modules such as SOAPClient.
     *
     * The array keys are the method names, the values the name of the PHP class containing
     * the function.
     *
     * The method names are listed in lowercase as function names in PHP are case-insensitive
     * and comparisons against this list should therefore always be done in a case-insensitive manner.
     *
     * @since 1.0.0
     *
     * @var array<string, string>
     */
    public static $methodsDoubleUnderscore = [
        '__dorequest'              => 'SOAPClient',
        '__getcookies'             => 'SOAPClient',
        '__getfunctions'           => 'SOAPClient',
        '__getlastrequest'         => 'SOAPClient',
        '__getlastrequestheaders'  => 'SOAPClient',
        '__getlastresponse'        => 'SOAPClient',
        '__getlastresponseheaders' => 'SOAPClient',
        '__gettypes'               => 'SOAPClient',
        '__setcookie'              => 'SOAPClient',
        '__setlocation'            => 'SOAPClient',
        '__setsoapheaders'         => 'SOAPClient',
        '__soapcall'               => 'SOAPClient',
    ];

    /**
     * Returns the declaration name for a function.
     *
     * Alias for the {@see \PHPCSUtils\Utils\ObjectDeclarations::getName()} method.
     *
     * @see \PHPCSUtils\BackCompat\BCFile::getDeclarationName() Original function.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::getName()     PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @codeCoverageIgnore
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the function keyword token.
     *
     * @return string|null The name of the function; or `NULL` if the passed token doesn't exist,
     *                     the function is anonymous or in case of a parse error/live coding.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_FUNCTION` token.
     */
    public static function getName(File $phpcsFile, $stackPtr)
    {
        return ObjectDeclarations::getName($phpcsFile, $stackPtr);
    }

    /**
     * Retrieves the visibility and implementation properties of a method.
     *
     * Main differences with the PHPCS version:
     * - Bugs fixed:
     *   - Handling of PHPCS annotations.
     *   - `"has_body"` index could be set to `true` for functions without body in the case of
     *      parse errors or live coding.
     * - Defensive coding against incorrect calls to this method.
     * - More efficient checking whether a function has a body.
     * - Support for PHP 8.0 identifier name tokens in return types, cross-version PHP & PHPCS.
     * - The results of this function call are cached during a PHPCS run for faster response times.
     *
     * @see \PHP_CodeSniffer\Files\File::getMethodProperties()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::getMethodProperties() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token to
     *                                               acquire the properties for.
     *
     * @return array<string, mixed> Array with information about a function declaration.
     *               The format of the return value is:
     *               ```php
     *               array(
     *                 'scope'                 => string,    // Public, private, or protected
     *                 'scope_specified'       => bool,      // TRUE if the scope keyword was found.
     *                 'return_type'           => string,    // The return type of the method.
     *                 'return_type_token'     => int|false, // The stack pointer to the start of the return type
     *                                                       // or FALSE if there is no return type.
     *                 'return_type_end_token' => int|false, // The stack pointer to the end of the return type
     *                                                       // or FALSE if there is no return type.
     *                 'nullable_return_type'  => bool,      // TRUE if the return type is preceded
     *                                                       // by the nullability operator.
     *                 'is_abstract'           => bool,      // TRUE if the abstract keyword was found.
     *                 'is_final'              => bool,      // TRUE if the final keyword was found.
     *                 'is_static'             => bool,      // TRUE if the static keyword was found.
     *                 'has_body'              => bool,      // TRUE if the method has a body
     *               );
     *               ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_FUNCTION, T_CLOSURE
     *                                                    or T_FN token.
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

        if (isset(Collections::functionDeclarationTokens()[$tokens[$stackPtr]['code']]) === false) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_FUNCTION, T_CLOSURE or T_FN', $tokens[$stackPtr]['type']);
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] === \T_FUNCTION) {
            $valid = Tokens::$methodPrefixes;
        } else {
            $valid = [\T_STATIC => \T_STATIC];
        }

        $valid += Tokens::$emptyTokens;

        $scope          = 'public';
        $scopeSpecified = false;
        $isAbstract     = false;
        $isFinal        = false;
        $isStatic       = false;

        for ($i = ($stackPtr - 1); $i > 0; $i--) {
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
                case \T_ABSTRACT:
                    $isAbstract = true;
                    break;
                case \T_FINAL:
                    $isFinal = true;
                    break;
                case \T_STATIC:
                    $isStatic = true;
                    break;
            }
        }

        $returnType         = '';
        $returnTypeToken    = false;
        $returnTypeEndToken = false;
        $nullableReturnType = false;
        $hasBody            = false;
        $returnTypeTokens   = Collections::returnTypeTokens();

        $parenthesisCloser = null;
        if (isset($tokens[$stackPtr]['parenthesis_closer']) === true) {
            $parenthesisCloser = $tokens[$stackPtr]['parenthesis_closer'];
        }

        if (isset($parenthesisCloser) === true) {
            $scopeOpener = null;
            if (isset($tokens[$stackPtr]['scope_opener']) === true) {
                $scopeOpener = $tokens[$stackPtr]['scope_opener'];
            }

            for ($i = $parenthesisCloser; $i < $phpcsFile->numTokens; $i++) {
                if ($i === $scopeOpener) {
                    // End of function definition.
                    $hasBody = true;
                    break;
                }

                if ($scopeOpener === null && $tokens[$i]['code'] === \T_SEMICOLON) {
                    // End of abstract/interface function definition.
                    break;
                }

                if ($tokens[$i]['code'] === \T_USE) {
                    // Skip over closure use statements.
                    for (
                        $j = ($i + 1);
                        $j < $phpcsFile->numTokens && isset(Tokens::$emptyTokens[$tokens[$j]['code']]) === true;
                        $j++
                    );

                    if ($tokens[$j]['code'] === \T_OPEN_PARENTHESIS) {
                        if (isset($tokens[$j]['parenthesis_closer']) === false) {
                            // Live coding/parse error, stop parsing.
                            break;
                        }

                        $i = $tokens[$j]['parenthesis_closer'];
                        continue;
                    }
                }

                if ($tokens[$i]['code'] === \T_NULLABLE) {
                    $nullableReturnType = true;
                }

                if (isset($returnTypeTokens[$tokens[$i]['code']]) === true) {
                    if ($returnTypeToken === false) {
                        $returnTypeToken = $i;
                    }

                    $returnType        .= $tokens[$i]['content'];
                    $returnTypeEndToken = $i;
                }
            }
        }

        if ($returnType !== '' && $nullableReturnType === true) {
            $returnType = '?' . $returnType;
        }

        $returnValue = [
            'scope'                 => $scope,
            'scope_specified'       => $scopeSpecified,
            'return_type'           => $returnType,
            'return_type_token'     => $returnTypeToken,
            'return_type_end_token' => $returnTypeEndToken,
            'nullable_return_type'  => $nullableReturnType,
            'is_abstract'           => $isAbstract,
            'is_final'              => $isFinal,
            'is_static'             => $isStatic,
            'has_body'              => $hasBody,
        ];

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $returnValue);
        return $returnValue;
    }

    /**
     * Retrieves the method parameters for the specified function token.
     *
     * Also supports passing in a `T_USE` token for a closure use group.
     *
     * The returned array will contain the following information for each parameter:
     *
     * ```php
     * 0 => array(
     *   'name'                => string,    // The variable name.
     *   'token'               => int,       // The stack pointer to the variable name.
     *   'content'             => string,    // The full content of the variable definition.
     *   'has_attributes'      => bool,      // Does the parameter have one or more attributes attached ?
     *   'pass_by_reference'   => bool,      // Is the variable passed by reference?
     *   'reference_token'     => int|false, // The stack pointer to the reference operator
     *                                       // or FALSE if the param is not passed by reference.
     *   'variable_length'     => bool,      // Is the param of variable length through use of `...` ?
     *   'variadic_token'      => int|false, // The stack pointer to the ... operator
     *                                       // or FALSE if the param is not variable length.
     *   'type_hint'           => string,    // The type hint for the variable.
     *   'type_hint_token'     => int|false, // The stack pointer to the start of the type hint
     *                                       // or FALSE if there is no type hint.
     *   'type_hint_end_token' => int|false, // The stack pointer to the end of the type hint
     *                                       // or FALSE if there is no type hint.
     *   'nullable_type'       => bool,      // TRUE if the var type is preceded by the nullability
     *                                       // operator.
     *   'comma_token'         => int|false, // The stack pointer to the comma after the param
     *                                       // or FALSE if this is the last param.
     * )
     * ```
     *
     * Parameters with default values have the following additional array indexes:
     * ```php
     *   'default'             => string, // The full content of the default value.
     *   'default_token'       => int,    // The stack pointer to the start of the default value.
     *   'default_equal_token' => int,    // The stack pointer to the equals sign.
     * ```
     *
     * Parameters declared using PHP 8 constructor property promotion, have these additional array indexes:
     * ```php
     *   'property_visibility' => string,    // The property visibility as declared.
     *   'visibility_token'    => int|false, // The stack pointer to the visibility modifier token.
     *                                       // or FALSE if the visibility is not explicitly declared.
     *   'property_readonly'   => bool,      // TRUE if the readonly keyword was found.
     *   'readonly_token'      => int,       // The stack pointer to the readonly modifier token.
     *                                       // This index will only be set if the property is readonly.
     * ```
     *
     * ... and if the promoted property uses asymmetric visibility, these additional array indexes will also be available:
     * ```php
     *   'set_visibility'       => string, // The property set-visibility as declared.
     *   'set_visibility_token' => int,    // The stack pointer to the set-visibility modifier token.
     * ```
     *
     * Main differences with the PHPCS version:
     * - Defensive coding against incorrect calls to this method.
     * - More efficient and more stable checking whether a `T_USE` token is a closure use.
     * - More efficient and more stable looping of the default value.
     * - Clearer exception message when a non-closure use token was passed to the function.
     * - Support for PHP 8.0 identifier name tokens in parameter types, cross-version PHP & PHPCS.
     * - The results of this function call are cached during a PHPCS run for faster response times.
     *
     * @see \PHP_CodeSniffer\Files\File::getMethodParameters()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::getMethodParameters() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token
     *                                               to acquire the parameters for.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_FUNCTION, T_CLOSURE,
     *                                                    T_FN or T_USE token.
     * @throws \PHPCSUtils\Exceptions\ValueError          If a passed `T_USE` token is not a closure use token.
     */
    public static function getParameters(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if (isset(Collections::functionDeclarationTokens()[$tokens[$stackPtr]['code']]) === false
            && $tokens[$stackPtr]['code'] !== \T_USE
        ) {
            $acceptedTokens = 'T_FUNCTION, T_CLOSURE, T_FN or T_USE';
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        if ($tokens[$stackPtr]['code'] === \T_USE) {
            // This will work PHPCS 3.x/4.x cross-version without much overhead.
            $opener = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if ($opener === false
                || $tokens[$opener]['code'] !== \T_OPEN_PARENTHESIS
                || UseStatements::isClosureUse($phpcsFile, $stackPtr) === false
            ) {
                throw ValueError::create(2, '$stackPtr', 'must be the pointer to a closure use statement');
            }
        } else {
            if (isset($tokens[$stackPtr]['parenthesis_opener']) === false) {
                // Live coding or syntax error, so no params to find.
                return [];
            }

            $opener = $tokens[$stackPtr]['parenthesis_opener'];
        }

        if (isset($tokens[$opener]['parenthesis_closer']) === false) {
            // Live coding or syntax error, so no params to find.
            return [];
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $closer = $tokens[$opener]['parenthesis_closer'];

        $vars               = [];
        $currVar            = null;
        $paramStart         = ($opener + 1);
        $defaultStart       = null;
        $equalToken         = null;
        $paramCount         = 0;
        $hasAttributes      = false;
        $passByReference    = false;
        $referenceToken     = false;
        $variableLength     = false;
        $variadicToken      = false;
        $typeHint           = '';
        $typeHintToken      = false;
        $typeHintEndToken   = false;
        $nullableType       = false;
        $visibilityToken    = null;
        $setVisibilityToken = null;
        $readonlyToken      = null;

        $parameterTypeTokens = Collections::parameterTypeTokens();

        for ($i = $paramStart; $i <= $closer; $i++) {
            if (isset($parameterTypeTokens[$tokens[$i]['code']]) === true
                /*
                 * Self and parent are valid, static invalid, but was probably intended as type declaration.
                 * Note: constructor property promotion does not support static properties, so this should
                 * still be a valid assumption.
                 */
                || $tokens[$i]['code'] === \T_STATIC
            ) {
                if ($typeHintToken === false) {
                    $typeHintToken = $i;
                }

                $typeHint        .= $tokens[$i]['content'];
                $typeHintEndToken = $i;
                continue;
            }

            switch ($tokens[$i]['code']) {
                case \T_ATTRIBUTE:
                    $hasAttributes = true;

                    // Skip to the end of the attribute.
                    $i = $tokens[$i]['attribute_closer'];
                    break;

                case \T_BITWISE_AND:
                    $passByReference = true;
                    $referenceToken  = $i;
                    break;

                case \T_VARIABLE:
                    $currVar = $i;
                    break;

                case \T_ELLIPSIS:
                    $variableLength = true;
                    $variadicToken  = $i;
                    break;

                case \T_NULLABLE:
                    $nullableType     = true;
                    $typeHint        .= $tokens[$i]['content'];
                    $typeHintEndToken = $i;
                    break;

                case \T_PUBLIC:
                case \T_PROTECTED:
                case \T_PRIVATE:
                    $visibilityToken = $i;
                    break;

                case \T_PUBLIC_SET:
                case \T_PROTECTED_SET:
                case \T_PRIVATE_SET:
                    $setVisibilityToken = $i;
                    break;

                case \T_READONLY:
                    $readonlyToken = $i;
                    break;

                case \T_CLOSE_PARENTHESIS:
                case \T_COMMA:
                    // If it's null, then there must be no parameters for this
                    // method.
                    if ($currVar === null) {
                        continue 2;
                    }

                    $vars[$paramCount]            = [];
                    $vars[$paramCount]['token']   = $currVar;
                    $vars[$paramCount]['name']    = $tokens[$currVar]['content'];
                    $vars[$paramCount]['content'] = \trim(
                        GetTokensAsString::normal($phpcsFile, $paramStart, ($i - 1))
                    );

                    if ($defaultStart !== null) {
                        $vars[$paramCount]['default']             = \trim(
                            GetTokensAsString::normal($phpcsFile, $defaultStart, ($i - 1))
                        );
                        $vars[$paramCount]['default_token']       = $defaultStart;
                        $vars[$paramCount]['default_equal_token'] = $equalToken;
                    }

                    $vars[$paramCount]['has_attributes']      = $hasAttributes;
                    $vars[$paramCount]['pass_by_reference']   = $passByReference;
                    $vars[$paramCount]['reference_token']     = $referenceToken;
                    $vars[$paramCount]['variable_length']     = $variableLength;
                    $vars[$paramCount]['variadic_token']      = $variadicToken;
                    $vars[$paramCount]['type_hint']           = $typeHint;
                    $vars[$paramCount]['type_hint_token']     = $typeHintToken;
                    $vars[$paramCount]['type_hint_end_token'] = $typeHintEndToken;
                    $vars[$paramCount]['nullable_type']       = $nullableType;

                    if ($visibilityToken !== null || $setVisibilityToken !== null || $readonlyToken !== null) {
                        $vars[$paramCount]['property_visibility'] = 'public';
                        $vars[$paramCount]['visibility_token']    = false;

                        if ($visibilityToken !== null) {
                            $vars[$paramCount]['property_visibility'] = $tokens[$visibilityToken]['content'];
                            $vars[$paramCount]['visibility_token']    = $visibilityToken;
                        }

                        if ($setVisibilityToken !== null) {
                            $vars[$paramCount]['set_visibility']       = $tokens[$setVisibilityToken]['content'];
                            $vars[$paramCount]['set_visibility_token'] = $setVisibilityToken;
                        }

                        $vars[$paramCount]['property_readonly'] = false;
                        if ($readonlyToken !== null) {
                            $vars[$paramCount]['property_readonly'] = true;
                            $vars[$paramCount]['readonly_token']    = $readonlyToken;
                        }
                    }

                    if ($tokens[$i]['code'] === \T_COMMA) {
                        $vars[$paramCount]['comma_token'] = $i;
                    } else {
                        $vars[$paramCount]['comma_token'] = false;
                    }

                    // Reset the vars, as we are about to process the next parameter.
                    $currVar            = null;
                    $paramStart         = ($i + 1);
                    $defaultStart       = null;
                    $equalToken         = null;
                    $hasAttributes      = false;
                    $passByReference    = false;
                    $referenceToken     = false;
                    $variableLength     = false;
                    $variadicToken      = false;
                    $typeHint           = '';
                    $typeHintToken      = false;
                    $typeHintEndToken   = false;
                    $nullableType       = false;
                    $visibilityToken    = null;
                    $setVisibilityToken = null;
                    $readonlyToken      = null;

                    ++$paramCount;
                    break;

                case \T_EQUAL:
                    $defaultStart = $phpcsFile->findNext(Tokens::$emptyTokens, ($i + 1), null, true);
                    $equalToken   = $i;

                    // Skip past everything in the default value before going into the next switch loop.
                    for ($j = ($i + 1); $j <= $closer; $j++) {
                        // Skip past array()'s et al as default values.
                        if (isset($tokens[$j]['parenthesis_opener'], $tokens[$j]['parenthesis_closer'])) {
                            $j = $tokens[$j]['parenthesis_closer'];

                            if ($j === $closer) {
                                // Found the end of the parameter.
                                break;
                            }

                            continue;
                        }

                        // Skip past short arrays et al as default values.
                        if (isset($tokens[$j]['bracket_opener'])) {
                            $j = $tokens[$j]['bracket_closer'];
                            continue;
                        }

                        if ($tokens[$j]['code'] === \T_COMMA) {
                            break;
                        }
                    }

                    $i = ($j - 1);
                    break;
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $vars);
        return $vars;
    }

    /**
     * Retrieve the stack pointers to the attribute openers for any attribute block
     * which applies to the function declaration.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token to
     *                                               acquire the attributes for.
     *
     * @return array<int> Array with the stack pointers to the applicable attribute openers
     *                    or an empty array if there are no attributes attached to the function declaration.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_FUNCTION, T_CLOSURE
     *                                                    or T_FN token.
     */
    public static function getAttributeOpeners(File $phpcsFile, $stackPtr)
    {
        return AttributeHelper::getOpeners($phpcsFile, $stackPtr, 'function');
    }

    /**
     * Checks if a given function is a PHP magic function.
     *
     * @todo Add check for the function declaration being namespaced!
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$magicFunctions       List of names of magic functions.
     * @see \PHPCSUtils\Utils\FunctionDeclaration::isMagicFunctionName() For when you already know the name of the
     *                                                                   function and scope checking is done in the
     *                                                                   sniff.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The `T_FUNCTION` token to check.
     *
     * @return bool
     */
    public static function isMagicFunction(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]) === false || $tokens[$stackPtr]['code'] !== \T_FUNCTION) {
            return false;
        }

        if (Scopes::isOOMethod($phpcsFile, $stackPtr) === true) {
            return false;
        }

        $name = self::getName($phpcsFile, $stackPtr);
        return self::isMagicFunctionName($name);
    }

    /**
     * Verify if a given function name is the name of a PHP magic function.
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$magicFunctions List of names of magic functions.
     *
     * @since 1.0.0
     *
     * @param string $name The full function name.
     *
     * @return bool
     */
    public static function isMagicFunctionName($name)
    {
        $name = \strtolower($name);
        return (isset(self::$magicFunctions[$name]) === true);
    }

    /**
     * Checks if a given function is a PHP magic method.
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$magicMethods       List of names of magic methods.
     * @see \PHPCSUtils\Utils\FunctionDeclaration::isMagicMethodName() For when you already know the name of the
     *                                                                 method and scope checking is done in the
     *                                                                 sniff.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The `T_FUNCTION` token to check.
     *
     * @return bool
     */
    public static function isMagicMethod(File $phpcsFile, $stackPtr)
    {
        if (Scopes::isOOMethod($phpcsFile, $stackPtr) === false) {
            return false;
        }

        $name = self::getName($phpcsFile, $stackPtr);
        return self::isMagicMethodName($name);
    }

    /**
     * Verify if a given function name is the name of a PHP magic method.
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$magicMethods List of names of magic methods.
     *
     * @since 1.0.0
     *
     * @param string $name The full function name.
     *
     * @return bool
     */
    public static function isMagicMethodName($name)
    {
        $name = \strtolower($name);
        return (isset(self::$magicMethods[$name]) === true);
    }

    /**
     * Checks if a given function is a non-magic PHP native double underscore method.
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$methodsDoubleUnderscore          List of the PHP native non-magic
     *                                                                               double underscore method names.
     * @see \PHPCSUtils\Utils\FunctionDeclaration::isPHPDoubleUnderscoreMethodName() For when you already know the
     *                                                                               name of the method and scope
     *                                                                               checking is done in the sniff.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The `T_FUNCTION` token to check.
     *
     * @return bool
     */
    public static function isPHPDoubleUnderscoreMethod(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]) === false || $tokens[$stackPtr]['code'] !== \T_FUNCTION) {
            return false;
        }

        $scopePtr = Scopes::validDirectScope($phpcsFile, $stackPtr, Tokens::$ooScopeTokens);
        if ($scopePtr === false) {
            return false;
        }

        /*
         * If this is a class, make sure it extends something, as otherwise, the methods
         * still can't be overloads for the SOAPClient methods.
         * For a trait/interface we don't know the concrete implementation context, so skip
         * this check.
         */
        if ($tokens[$scopePtr]['code'] === \T_CLASS || $tokens[$scopePtr]['code'] === \T_ANON_CLASS) {
            $extends = ObjectDeclarations::findExtendedClassName($phpcsFile, $scopePtr);
            if ($extends === false) {
                return false;
            }
        }

        $name = self::getName($phpcsFile, $stackPtr);
        return self::isPHPDoubleUnderscoreMethodName($name);
    }

    /**
     * Verify if a given function name is the name of a non-magic PHP native double underscore method.
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::$methodsDoubleUnderscore List of the PHP native non-magic
     *                                                                      double underscore method names.
     *
     * @since 1.0.0
     *
     * @param string $name The full function name.
     *
     * @return bool
     */
    public static function isPHPDoubleUnderscoreMethodName($name)
    {
        $name = \strtolower($name);
        return (isset(self::$methodsDoubleUnderscore[$name]) === true);
    }

    /**
     * Checks if a given function is a magic method or a PHP native double underscore method.
     *
     * {@internal Not the most efficient way of checking this, but less efficient ways will get
     *            less reliable results or introduce a lot of code duplication.}
     *
     * @see \PHPCSUtils\Utils\FunctionDeclaration::isSpecialMethodName() For when you already know the name of the
     *                                                                   method and scope checking is done in the
     *                                                                   sniff.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The `T_FUNCTION` token to check.
     *
     * @return bool
     */
    public static function isSpecialMethod(File $phpcsFile, $stackPtr)
    {
        if (self::isMagicMethod($phpcsFile, $stackPtr) === true) {
            return true;
        }

        if (self::isPHPDoubleUnderscoreMethod($phpcsFile, $stackPtr) === true) {
            return true;
        }

        return false;
    }

    /**
     * Verify if a given function name is the name of a magic method or a PHP native double underscore method.
     *
     * @since 1.0.0
     *
     * @param string $name The full function name.
     *
     * @return bool
     */
    public static function isSpecialMethodName($name)
    {
        $name = \strtolower($name);
        return (isset(self::$magicMethods[$name]) === true || isset(self::$methodsDoubleUnderscore[$name]) === true);
    }
}
