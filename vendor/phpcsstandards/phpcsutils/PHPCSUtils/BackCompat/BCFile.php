<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 *
 * The methods in this class are imported from the PHP_CodeSniffer project.
 * Note: this is not a one-on-one import of the `File` class!
 *
 * Copyright of the original code in this class as per the import:
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Jaroslav Hanslík <kukulich@kukulich.cz>
 * @author    jdavis <jdavis@bamboohr.com>
 * @author    Klaus Purer <klaus.purer@gmail.com>
 * @author    Juliette Reinders Folmer <jrf@phpcodesniffer.info>
 * @author    Nick Wilde <nick@briarmoon.ca>
 * @author    Martin Hujer <mhujer@gmail.com>
 * @author    Chris Wilkinson <c.wilkinson@elifesciences.org>
 *
 * With documentation contributions from:
 * @author    Pascal Borreli <pascal@borreli.com>
 * @author    Diogo Oliveira de Melo <dmelo87@gmail.com>
 * @author    Stefano Kowalke <blueduck@gmx.net>
 * @author    George Mponos <gmponos@gmail.com>
 * @author    Tyson Andre <tysonandre775@hotmail.com>
 * @author    Klaus Purer <klaus.purer@protonmail.ch>
 *
 * @copyright 2006-2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */

namespace PHPCSUtils\BackCompat;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;

/**
 * PHPCS native utility functions.
 *
 * Backport of the latest versions of PHPCS native utility functions to make them
 * available in older PHPCS versions without the bugs and other quirks that the
 * older versions of the native functions had.
 *
 * Additionally, this class works round the following tokenizer issues for
 * any affected utility functions:
 * - `readonly` classes.
 * - Constructor property promotion with `readonly` without visibility.
 * - OO methods called `self`, `parent` or `static`.
 *
 * Most functions in this class will have a related twin-function in the relevant
 * class in the `PHPCSUtils\Utils` namespace.
 * These will be indicated with `@see` tags in the docblock of the function.
 *
 * The PHPCSUtils native twin-functions will often have additional features and/or
 * improved functionality, but will generally be fully compatible with the PHPCS
 * native functions.
 * The differences between the functions here and the twin functions are documented
 * in the docblock of the respective twin-function.
 *
 * @see \PHP_CodeSniffer\Files\File Original source of these utility methods.
 *
 * @since 1.0.0
 */
final class BCFile
{

    /**
     * Returns the declaration name for classes, interfaces, traits, enums, and functions.
     *
     * PHPCS cross-version compatible version of the `File::getDeclarationName()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - PHPCS 4.0: The method no longer accepts `T_CLOSURE` and `T_ANON_CLASS` tokens.
     * - PHPCS 4.0: The method will now always return a string.
     *
     * @see \PHP_CodeSniffer\Files\File::getDeclarationName() Original source.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::getName()   PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the declaration token
     *                                               which declared the class, interface,
     *                                               trait, enum or function.
     *
     * @return string The name of the class, interface, trait, or function or an empty string
     *                if the name could not be determined (live coding).
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified token is not of type
     *                                                      `T_FUNCTION`, `T_CLASS`, `T_TRAIT`, `T_ENUM`, or `T_INTERFACE`.
     */
    public static function getDeclarationName(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $tokenCode = $tokens[$stackPtr]['code'];

        if ($tokenCode !== T_FUNCTION
            && $tokenCode !== T_CLASS
            && $tokenCode !== T_INTERFACE
            && $tokenCode !== T_TRAIT
            && $tokenCode !== T_ENUM
        ) {
            throw new RuntimeException('Token type "' . $tokens[$stackPtr]['type'] . '" is not T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT or T_ENUM');
        }

        if ($tokenCode === T_FUNCTION
            && strtolower($tokens[$stackPtr]['content']) !== 'function'
        ) {
            // This is a function declared without the "function" keyword.
            // So this token is the function name.
            return $tokens[$stackPtr]['content'];
        }

        $stopPoint = $phpcsFile->numTokens;
        if (isset($tokens[$stackPtr]['parenthesis_opener']) === true) {
            // For functions, stop searching at the parenthesis opener.
            $stopPoint = $tokens[$stackPtr]['parenthesis_opener'];
        } elseif (isset($tokens[$stackPtr]['scope_opener']) === true) {
            // For OO tokens, stop searching at the open curly.
            $stopPoint = $tokens[$stackPtr]['scope_opener'];
        }

        $content = '';
        for ($i = $stackPtr; $i < $stopPoint; $i++) {
            if ($tokens[$i]['code'] === T_STRING) {
                $content = $tokens[$i]['content'];
                break;
            }
        }

        return $content;
    }

    /**
     * Returns the method parameters for the specified function token.
     *
     * Also supports passing in a `T_USE` token for a closure use group.
     *
     * Each parameter is in the following format:
     * ```php
     * 0 => array(
     *   'name'                => string,        // The variable name.
     *   'token'               => integer,       // The stack pointer to the variable name.
     *   'content'             => string,        // The full content of the variable definition.
     *   'has_attributes'      => boolean,       // Does the parameter have one or more attributes attached ?
     *   'pass_by_reference'   => boolean,       // Is the variable passed by reference?
     *   'reference_token'     => integer|false, // The stack pointer to the reference operator
     *                                           // or FALSE if the param is not passed by reference.
     *   'variable_length'     => boolean,       // Is the param of variable length through use of `...` ?
     *   'variadic_token'      => integer|false, // The stack pointer to the ... operator
     *                                           // or FALSE if the param is not variable length.
     *   'type_hint'           => string,        // The type hint for the variable.
     *   'type_hint_token'     => integer|false, // The stack pointer to the start of the type hint
     *                                           // or FALSE if there is no type hint.
     *   'type_hint_end_token' => integer|false, // The stack pointer to the end of the type hint
     *                                           // or FALSE if there is no type hint.
     *   'nullable_type'       => boolean,       // TRUE if the param type is preceded by the nullability
     *                                           // operator.
     *   'comma_token'         => integer|false, // The stack pointer to the comma after the param
     *                                           // or FALSE if this is the last param.
     * )
     * ```
     *
     * Parameters with default values have the following additional array indexes:
     * ```php
     *   'default'             => string,  // The full content of the default value.
     *   'default_token'       => integer, // The stack pointer to the start of the default value.
     *   'default_equal_token' => integer, // The stack pointer to the equals sign.
     * ```
     *
     * Parameters declared using PHP 8 constructor property promotion, have these additional array indexes:
     * ```php
     *   'property_visibility' => string,        // The property visibility as declared.
     *   'visibility_token'    => integer|false, // The stack pointer to the visibility modifier token.
     *                                           // or FALSE if the visibility is not explicitly declared.
     *   'property_readonly'   => boolean,       // TRUE if the readonly keyword was found.
     *   'readonly_token'      => integer,       // The stack pointer to the readonly modifier token.
     *                                           // This index will only be set if the property is readonly.
     * ```
     *
     * ... and if the promoted property uses asymmetric visibility, these additional array indexes will also be available:
     * ```php
     *   'set_visibility'       => string,       // The property set-visibility as declared.
     *   'set_visibility_token' => integer,      // The stack pointer to the set-visibility modifier token.
     * ```
     *
     * PHPCS cross-version compatible version of the `File::getMethodParameters()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - PHPCS 3.8.0: Added support for constructor property promotion with readonly without explicit visibility.
     *
     * @see \PHP_CodeSniffer\Files\File::getMethodParameters()      Original source.
     * @see \PHPCSUtils\Utils\FunctionDeclarations::getParameters() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     * @since 1.0.6 Sync with PHPCS 3.8.0, support for readonly properties without explicit visibility. PHPCS#3801.
     * @since 1.1.0 Sync with PHPCS 3.13.1, support for asymmetric properties. PHPCS(new)#851
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token
     *                                               to acquire the parameters for.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified `$stackPtr` is not of
     *                                                      type `T_FUNCTION`, `T_CLOSURE`, `T_USE`,
     *                                                      or `T_FN`.
     */
    public static function getMethodParameters(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset(Collections::functionDeclarationTokens()[$tokens[$stackPtr]['code']]) === false
            && $tokens[$stackPtr]['code'] !== T_USE
        ) {
            throw new RuntimeException('$stackPtr must be of type T_FUNCTION or T_CLOSURE or T_USE or T_FN');
        }

        if ($tokens[$stackPtr]['code'] === T_USE) {
            $opener = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($stackPtr + 1));
            if ($opener === false
                || (isset($tokens[$opener]['parenthesis_owner']) === true
                // BC: as of PHPCS 4.x, closure use tokens are parentheses owners.
                && $tokens[$opener]['parenthesis_owner'] !== $stackPtr)
            ) {
                throw new RuntimeException('$stackPtr was not a valid T_USE');
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

        for ($i = $paramStart; $i <= $closer; $i++) {
            // Check to see if this token has a parenthesis or bracket opener. If it does
            // it's likely to be an array which might have arguments in it. This
            // could cause problems in our parsing below, so lets just skip to the
            // end of it.
            if ($tokens[$i]['code'] !== T_TYPE_OPEN_PARENTHESIS
                && isset($tokens[$i]['parenthesis_opener']) === true
            ) {
                // Don't do this if it's the close parenthesis for the method.
                if ($i !== $tokens[$i]['parenthesis_closer']) {
                    $i = ($tokens[$i]['parenthesis_closer'] + 1);
                }
            }

            if (isset($tokens[$i]['bracket_opener']) === true) {
                // Don't do this if it's the close parenthesis for the method.
                if ($i !== $tokens[$i]['bracket_closer']) {
                    $i = ($tokens[$i]['bracket_closer'] + 1);
                }
            }

            switch ($tokens[$i]['code']) {
                case T_ATTRIBUTE:
                    $hasAttributes = true;

                    // Skip to the end of the attribute.
                    $i = $tokens[$i]['attribute_closer'];
                    break;
                case T_BITWISE_AND:
                    if ($defaultStart === null) {
                        $passByReference = true;
                        $referenceToken  = $i;
                    }
                    break;
                case T_VARIABLE:
                    $currVar = $i;
                    break;
                case T_ELLIPSIS:
                    $variableLength = true;
                    $variadicToken  = $i;
                    break;
                case T_CALLABLE:
                    if ($typeHintToken === false) {
                        $typeHintToken = $i;
                    }

                    $typeHint        .= $tokens[$i]['content'];
                    $typeHintEndToken = $i;
                    break;
                case T_SELF:
                case T_PARENT:
                case T_STATIC:
                    // Self and parent are valid, static invalid, but was probably intended as type hint.
                    if (isset($defaultStart) === false) {
                        if ($typeHintToken === false) {
                            $typeHintToken = $i;
                        }

                        $typeHint        .= $tokens[$i]['content'];
                        $typeHintEndToken = $i;
                    }
                    break;
                case T_STRING:
                case T_NAME_QUALIFIED:
                case T_NAME_FULLY_QUALIFIED:
                case T_NAME_RELATIVE:
                    // This is an identifier name, so it may be a type declaration, but it could
                    // also be a constant used as a default value.
                    $prevComma = false;
                    for ($t = $i; $t >= $opener; $t--) {
                        if ($tokens[$t]['code'] === T_COMMA) {
                            $prevComma = $t;
                            break;
                        }
                    }

                    if ($prevComma !== false) {
                        $nextEquals = false;
                        for ($t = $prevComma; $t < $i; $t++) {
                            if ($tokens[$t]['code'] === T_EQUAL) {
                                $nextEquals = $t;
                                break;
                            }
                        }

                        if ($nextEquals !== false) {
                            break;
                        }
                    }

                    if ($defaultStart === null) {
                        if ($typeHintToken === false) {
                            $typeHintToken = $i;
                        }

                        $typeHint        .= $tokens[$i]['content'];
                        $typeHintEndToken = $i;
                    }
                    break;
                case T_NAMESPACE:
                case T_NS_SEPARATOR:
                case T_TYPE_UNION:
                case T_TYPE_INTERSECTION:
                case T_TYPE_OPEN_PARENTHESIS:
                case T_TYPE_CLOSE_PARENTHESIS:
                case T_FALSE:
                case T_TRUE:
                case T_NULL:
                    // Part of a type hint or default value.
                    if ($defaultStart === null) {
                        if ($typeHintToken === false) {
                            $typeHintToken = $i;
                        }

                        $typeHint        .= $tokens[$i]['content'];
                        $typeHintEndToken = $i;
                    }
                    break;
                case T_NULLABLE:
                    if ($defaultStart === null) {
                        $nullableType     = true;
                        $typeHint        .= $tokens[$i]['content'];
                        $typeHintEndToken = $i;
                    }
                    break;
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                    if ($defaultStart === null) {
                        $visibilityToken = $i;
                    }
                    break;
                case T_PUBLIC_SET:
                case T_PROTECTED_SET:
                case T_PRIVATE_SET:
                    if ($defaultStart === null) {
                        $setVisibilityToken = $i;
                    }
                    break;
                case T_READONLY:
                    if ($defaultStart === null) {
                        $readonlyToken = $i;
                    }
                    break;
                case T_CLOSE_PARENTHESIS:
                case T_COMMA:
                    // If it's null, then there must be no parameters for this
                    // method.
                    if ($currVar === null) {
                        continue 2;
                    }

                    $vars[$paramCount]            = [];
                    $vars[$paramCount]['token']   = $currVar;
                    $vars[$paramCount]['name']    = $tokens[$currVar]['content'];
                    $vars[$paramCount]['content'] = trim($phpcsFile->getTokensAsString($paramStart, ($i - $paramStart)));

                    if ($defaultStart !== null) {
                        $vars[$paramCount]['default']             = trim($phpcsFile->getTokensAsString($defaultStart, ($i - $defaultStart)));
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

                    if ($tokens[$i]['code'] === T_COMMA) {
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
                case T_EQUAL:
                    $defaultStart = $phpcsFile->findNext(Tokens::$emptyTokens, ($i + 1), null, true);
                    $equalToken   = $i;
                    break;
            }
        }

        return $vars;
    }

    /**
     * Returns the visibility and implementation properties of a method.
     *
     * The format of the return value is:
     * ```php
     * array(
     *   'scope'                 => string,        // Public, private, or protected
     *   'scope_specified'       => boolean,       // TRUE if the scope keyword was found.
     *   'return_type'           => string,        // The return type of the method.
     *   'return_type_token'     => integer|false, // The stack pointer to the start of the return type
     *                                             // or FALSE if there is no return type.
     *   'return_type_end_token' => integer|false, // The stack pointer to the end of the return type
     *                                             // or FALSE if there is no return type.
     *   'nullable_return_type'  => boolean,       // TRUE if the return type is preceded by
     *                                             // the nullability operator.
     *   'is_abstract'           => boolean,       // TRUE if the abstract keyword was found.
     *   'is_final'              => boolean,       // TRUE if the final keyword was found.
     *   'is_static'             => boolean,       // TRUE if the static keyword was found.
     *   'has_body'              => boolean,       // TRUE if the method has a body
     * );
     * ```
     *
     * PHPCS cross-version compatible version of the `File::getMethodProperties()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::getMethodProperties()      Original source.
     * @see \PHPCSUtils\Utils\FunctionDeclarations::getProperties() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the function token to
     *                                               acquire the properties for.
     *
     * @return array<string, mixed>
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
     *                                                      `T_FUNCTION`, `T_CLOSURE`, or `T_FN` token.
     */
    public static function getMethodProperties(File $phpcsFile, $stackPtr)
    {
        return $phpcsFile->getMethodProperties($stackPtr);
    }

    /**
     * Returns the visibility and implementation properties of a class member var.
     *
     * The format of the return value is:
     * ```php
     * array(
     *   'scope'           => string,        // Public, private, or protected.
     *   'scope_specified' => boolean,       // TRUE if the scope was explicitly specified.
     *   'set_scope'       => string|false,  // Scope for asymmetric visibility.
     *                                       // Either public, private, or protected or
     *                                       // FALSE if no set scope is specified.
     *   'is_static'       => boolean,       // TRUE if the static keyword was found.
     *   'is_readonly'     => boolean,       // TRUE if the readonly keyword was found.
     *   'is_final'        => boolean,       // TRUE if the final keyword was found.
     *   'is_abstract'     => boolean,       // TRUE if the abstract keyword was found.
     *   'type'            => string,        // The type of the var (empty if no type specified).
     *   'type_token'      => integer|false, // The stack pointer to the start of the type
     *                                       // or FALSE if there is no type.
     *   'type_end_token'  => integer|false, // The stack pointer to the end of the type
     *                                       // or FALSE if there is no type.
     *   'nullable_type'   => boolean,       // TRUE if the type is preceded by the
     *                                       // nullability operator.
     * );
     * ```
     *
     * PHPCS cross-version compatible version of the `File::getMemberProperties()  method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - PHPCS 3.13.3: support for PHP 8.4 abstract properties.
     * - PHPCS 4.0: properties in interfaces (PHP 8.4+) are accepted.
     * - PHPCS 4.0: will no longer throw a parse error warning.
     *
     * @see \PHP_CodeSniffer\Files\File::getMemberProperties() Original source.
     * @see \PHPCSUtils\Utils\Variables::getMemberProperties() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     * @since 1.1.0 Sync with PHPCS 4.0.0, remove parse error warning and support PHP 8.4 properties in interfaces. PHPCS(new)#991
     * @since 1.1.2 Sync with PHPCS 3.13.3, support for abstract properties. PHPCS(new)#1183
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_VARIABLE` token to
     *                                               acquire the properties for.
     *
     * @return array<string, mixed>
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
     *                                                      `T_VARIABLE` token, or if the position is not
     *                                                      a class member variable.
     */
    public static function getMemberProperties(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_VARIABLE) {
            throw new RuntimeException('$stackPtr must be of type T_VARIABLE');
        }

        if (empty($tokens[$stackPtr]['conditions']) === true) {
            throw new RuntimeException('$stackPtr is not a class member var');
        }

        $conditions = $tokens[$stackPtr]['conditions'];
        $conditions = array_keys($conditions);
        $ptr        = array_pop($conditions);
        if (isset($tokens[$ptr]) === false
            || isset(Tokens::$ooScopeTokens[$tokens[$ptr]['code']]) === false
            || $tokens[$ptr]['code'] === T_ENUM
        ) {
            throw new RuntimeException('$stackPtr is not a class member var');
        }

        // Make sure it's not a method parameter.
        if (empty($tokens[$stackPtr]['nested_parenthesis']) === false) {
            $parenthesis = array_keys($tokens[$stackPtr]['nested_parenthesis']);
            $deepestOpen = array_pop($parenthesis);
            if ($deepestOpen > $ptr
                && isset($tokens[$deepestOpen]['parenthesis_owner']) === true
                && $tokens[$tokens[$deepestOpen]['parenthesis_owner']]['code'] === T_FUNCTION
            ) {
                throw new RuntimeException('$stackPtr is not a class member var');
            }
        }

        $valid = [
            T_STATIC   => T_STATIC,
            T_VAR      => T_VAR,
            T_READONLY => T_READONLY,
            T_FINAL    => T_FINAL,
            T_ABSTRACT => T_ABSTRACT,
        ];

        $valid += Tokens::$scopeModifiers;
        $valid += Tokens::$emptyTokens;

        $scope          = 'public';
        $scopeSpecified = false;
        $setScope       = false;
        $isStatic       = false;
        $isReadonly     = false;
        $isFinal        = false;
        $isAbstract     = false;

        $startOfStatement = $phpcsFile->findPrevious(
            [
                T_SEMICOLON,
                T_OPEN_CURLY_BRACKET,
                T_CLOSE_CURLY_BRACKET,
                T_ATTRIBUTE_END,
            ],
            ($stackPtr - 1)
        );

        for ($i = ($startOfStatement + 1); $i < $stackPtr; $i++) {
            if (isset($valid[$tokens[$i]['code']]) === false) {
                break;
            }

            switch ($tokens[$i]['code']) {
                case T_PUBLIC:
                    $scope          = 'public';
                    $scopeSpecified = true;
                    break;
                case T_PRIVATE:
                    $scope          = 'private';
                    $scopeSpecified = true;
                    break;
                case T_PROTECTED:
                    $scope          = 'protected';
                    $scopeSpecified = true;
                    break;
                case T_PUBLIC_SET:
                    $setScope = 'public';
                    break;
                case T_PROTECTED_SET:
                    $setScope = 'protected';
                    break;
                case T_PRIVATE_SET:
                    $setScope = 'private';
                    break;
                case T_STATIC:
                    $isStatic = true;
                    break;
                case T_READONLY:
                    $isReadonly = true;
                    break;
                case T_FINAL:
                    $isFinal = true;
                    break;
                case T_ABSTRACT:
                    $isAbstract = true;
                    break;
            }
        }

        $type         = '';
        $typeToken    = false;
        $typeEndToken = false;
        $nullableType = false;

        if ($i < $stackPtr) {
            // We've found a type.
            $valid = Collections::propertyTypeTokens();

            for ($i; $i < $stackPtr; $i++) {
                if ($tokens[$i]['code'] === T_VARIABLE) {
                    // Hit another variable in a group definition.
                    break;
                }

                if ($tokens[$i]['code'] === T_NULLABLE) {
                    $nullableType = true;
                }

                if (isset($valid[$tokens[$i]['code']]) === true) {
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

        return [
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
    }

    /**
     * Returns the implementation properties of a class.
     *
     * The format of the return value is:
     * ```php
     * array(
     *   'is_abstract' => boolean, // TRUE if the abstract keyword was found.
     *   'is_final'    => boolean, // TRUE if the final keyword was found.
     *   'is_readonly' => boolean, // TRUE if the readonly keyword was found.
     * );
     * ```
     *
     * PHPCS cross-version compatible version of the `File::getClassProperties()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 1.3.0.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::getClassProperties()          Original source.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::getClassProperties() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_CLASS`
     *                                               token to acquire the properties for.
     *
     * @return array<string, bool>
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified position is not a
     *                                                      `T_CLASS` token.
     */
    public static function getClassProperties(File $phpcsFile, $stackPtr)
    {
        return $phpcsFile->getClassProperties($stackPtr);
    }

    /**
     * Determine if the passed token is a reference operator.
     *
     * PHPCS cross-version compatible version of the `File::isReference()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::isReference() Original source.
     * @see \PHPCSUtils\Utils\Operators::isReference() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_BITWISE_AND` token.
     *
     * @return bool `TRUE` if the specified token position represents a reference.
     *              `FALSE` if the token represents a bitwise operator.
     */
    public static function isReference(File $phpcsFile, $stackPtr)
    {
        return $phpcsFile->isReference($stackPtr);
    }

    /**
     * Returns the content of the tokens from the specified start position in
     * the token stack for the specified length.
     *
     * PHPCS cross-version compatible version of the `File::getTokensAsString()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::getTokensAsString() Original source.
     * @see \PHPCSUtils\Utils\GetTokensAsString              Related set of functions.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile   The file being scanned.
     * @param int                         $start       The position to start from in the token stack.
     * @param int                         $length      The length of tokens to traverse from the start pos.
     * @param bool                        $origContent Whether the original content or the tab replaced
     *                                                 content should be used.
     *
     * @return string The token contents.
     *
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified start position does not exist.
     */
    public static function getTokensAsString(File $phpcsFile, $start, $length, $origContent = false)
    {
        return $phpcsFile->getTokensAsString($start, $length, $origContent);
    }

    /**
     * Returns the position of the first non-whitespace token in a statement.
     *
     * PHPCS cross-version compatible version of the `File::findStartOfStatement()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 2.1.0.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::findStartOfStatement() Original source.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file being scanned.
     * @param int                          $start     The position to start searching from in the token stack.
     * @param int|string|array<int|string> $ignore    Token types that should not be considered stop points.
     *
     * @return int
     */
    public static function findStartOfStatement(File $phpcsFile, $start, $ignore = null)
    {
        return $phpcsFile->findStartOfStatement($start, $ignore);
    }

    /**
     * Returns the position of the last non-whitespace token in a statement.
     *
     * PHPCS cross-version compatible version of the `File::findEndOfStatement()  method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 2.1.0.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::findEndOfStatement() Original source.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file being scanned.
     * @param int                          $start     The position to start searching from in the token stack.
     * @param int|string|array<int|string> $ignore    Token types that should not be considered stop points.
     *
     * @return int
     */
    public static function findEndOfStatement(File $phpcsFile, $start, $ignore = null)
    {
        return $phpcsFile->findEndOfStatement($start, $ignore);
    }

    /**
     * Determine if the passed token has a condition of one of the passed types.
     *
     * PHPCS cross-version compatible version of the `File::hasCondition()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 0.0.5.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::hasCondition()  Original source.
     * @see \PHPCSUtils\Utils\Conditions::hasCondition() PHPCSUtils native alternative.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile The file being scanned.
     * @param int                          $stackPtr  The position of the token we are checking.
     * @param int|string|array<int|string> $types     The type(s) of tokens to search for.
     *
     * @return bool
     */
    public static function hasCondition(File $phpcsFile, $stackPtr, $types)
    {
        return $phpcsFile->hasCondition($stackPtr, $types);
    }

    /**
     * Return the position of the condition for the passed token.
     *
     * PHPCS cross-version compatible version of the `File::getCondition()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 1.3.0.
     * - The upstream method has received no significant updates since PHPCS 3.13.5.
     *
     * @see \PHP_CodeSniffer\Files\File::getCondition()  Original source.
     * @see \PHPCSUtils\Utils\Conditions::getCondition() More versatile alternative.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the token we are checking.
     * @param int|string                  $type      The type of token to search for.
     * @param bool                        $first     If `true`, will return the matched condition
     *                                               furthest away from the passed token.
     *                                               If `false`, will return the matched condition
     *                                               closest to the passed token.
     *
     * @return int|false Integer stack pointer to the condition or `FALSE` if the token
     *                   does not have the condition.
     */
    public static function getCondition(File $phpcsFile, $stackPtr, $type, $first = true)
    {
        return $phpcsFile->getCondition($stackPtr, $type, $first);
    }

    /**
     * Returns the name of the class that the specified class extends.
     * (works for classes, anonymous classes and interfaces)
     *
     * PHPCS cross-version compatible version of the `File::findExtendedClassName()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 1.2.0.
     * - PHPCS 4.0.0: Handling of the namespace relative parent class using the namespace keyword as operator.
     *
     * @see \PHP_CodeSniffer\Files\File::findExtendedClassName()          Original source.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::findExtendedClassName() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The stack position of the class or interface.
     *
     * @return string|false The extended class name or `FALSE` on error or if there
     *                      is no extended class name.
     */
    public static function findExtendedClassName(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        if ($tokens[$stackPtr]['code'] !== T_CLASS
            && $tokens[$stackPtr]['code'] !== T_ANON_CLASS
            && $tokens[$stackPtr]['code'] !== T_INTERFACE
        ) {
            return false;
        }

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return false;
        }

        $classOpenerIndex = $tokens[$stackPtr]['scope_opener'];
        $extendsIndex     = $phpcsFile->findNext(T_EXTENDS, $stackPtr, $classOpenerIndex);
        if ($extendsIndex === false) {
            return false;
        }

        $find   = Collections::namespacedNameTokens();
        $find[] = T_WHITESPACE;

        $end  = $phpcsFile->findNext($find, ($extendsIndex + 1), ($classOpenerIndex + 1), true);
        $name = $phpcsFile->getTokensAsString(($extendsIndex + 1), ($end - $extendsIndex - 1));
        $name = trim($name);

        if ($name === '') {
            return false;
        }

        return $name;
    }

    /**
     * Returns the names of the interfaces that the specified class or enum implements.
     *
     * PHPCS cross-version compatible version of the `File::findImplementedInterfaceNames()` method.
     *
     * Changelog for the PHPCS native function:
     * - Introduced in PHPCS 2.7.0.
     * - PHPCS 4.0.0: Handling of the namespace relative parent class using the namespace keyword as operator.
     *
     * @see \PHP_CodeSniffer\Files\File::findImplementedInterfaceNames()          Original source.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::findImplementedInterfaceNames() PHPCSUtils native improved version.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The stack position of the class or enum token.
     *
     * @return array<string>|false Array with names of the implemented interfaces or `FALSE` on
     *                             error or if there are no implemented interface names.
     */
    public static function findImplementedInterfaceNames(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check for the existence of the token.
        if (isset($tokens[$stackPtr]) === false) {
            return false;
        }

        if ($tokens[$stackPtr]['code'] !== T_CLASS
            && $tokens[$stackPtr]['code'] !== T_ANON_CLASS
            && $tokens[$stackPtr]['code'] !== T_ENUM
        ) {
            return false;
        }

        if (isset($tokens[$stackPtr]['scope_closer']) === false) {
            return false;
        }

        $classOpenerIndex = $tokens[$stackPtr]['scope_opener'];
        $implementsIndex  = $phpcsFile->findNext(T_IMPLEMENTS, $stackPtr, $classOpenerIndex);
        if ($implementsIndex === false) {
            return false;
        }

        $find   = Collections::namespacedNameTokens();
        $find[] = T_WHITESPACE;
        $find[] = T_COMMA;

        $end  = $phpcsFile->findNext($find, ($implementsIndex + 1), ($classOpenerIndex + 1), true);
        $name = $phpcsFile->getTokensAsString(($implementsIndex + 1), ($end - $implementsIndex - 1));
        $name = trim($name);

        if ($name === '') {
            return false;
        } else {
            $names = explode(',', $name);
            $names = array_map('trim', $names);
            return $names;
        }
    }
}
