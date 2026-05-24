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
use PHPCSUtils\Internal\AttributeHelper;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Utility functions for use when examining object declaration statements.
 *
 * @since 1.0.0 The `ObjectDeclarations::get(Declaration)Name()`,
 *              `ObjectDeclarations::getClassProperties()`, `ObjectDeclarations::findExtendedClassName()`
 *              and `ObjectDeclarations::findImplementedInterfaceNames()` methods are based on and
 *              inspired by the methods of the same name in the PHPCS native
 *              PHP_CodeSniffer\Files\File` class.
 *              Also see {@see \PHPCSUtils\BackCompat\BCFile}.
 */
final class ObjectDeclarations
{

    /**
     * Retrieves the declaration name for classes, interfaces, traits, enums and functions.
     *
     * Main differences with the PHPCS version:
     * - Defensive coding against incorrect calls to this method.
     * - Improved handling of invalid names, like names starting with a number.
     *   This allows sniffs to report on invalid names instead of ignoring them.
     * - Bug fix: improved handling of parse errors.
     *   Using the original method, a parse error due to an invalid name could cause the method
     *   to return the name of the *next* construct, a partial name and/or the name of a class
     *   being extended/interface being implemented.
     *   Using this version of the utility method, either the complete name (invalid or not) will
     *   be returned or `null` in case of no name (parse error).
     * - The PHPCS 4.0 change to no longer accept tokens for anonymous structures (T_CLOSURE/T_ANON_CLASS)
     *   has not been applied to this method (yet). This will change in PHPCSUtils 2.0.
     * - The PHPCS 4.0 change to normalize the return type to `string` and no longer return `null`
     *   has not been applied to this method (yet). This will change in PHPCSUtils 2.0.
     *
     * @see \PHP_CodeSniffer\Files\File::getDeclarationName()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::getDeclarationName() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the declaration token
     *                                               which declared the class, interface,
     *                                               trait, enum or function.
     *
     * @return string|null The name of the class, interface, trait, enum, or function;
     *                     or `NULL` if the passed token doesn't exist, the function or
     *                     class is anonymous or in case of a parse error/live coding.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_FUNCTION`, `T_CLASS`,
     *                                                    `T_ANON_CLASS`, `T_CLOSURE`, `T_TRAIT`, `T_ENUM`
     *                                                    or `T_INTERFACE` token.
     */
    public static function getName(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false
            || ($tokens[$stackPtr]['code'] === \T_ANON_CLASS || $tokens[$stackPtr]['code'] === \T_CLOSURE)
        ) {
            return null;
        }

        $tokenCode = $tokens[$stackPtr]['code'];

        if ($tokenCode !== \T_FUNCTION
            && $tokenCode !== \T_CLASS
            && $tokenCode !== \T_INTERFACE
            && $tokenCode !== \T_TRAIT
            && $tokenCode !== \T_ENUM
        ) {
            $acceptedTokens = 'T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT or T_ENUM';
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        if ($tokenCode === \T_FUNCTION
            && \strtolower($tokens[$stackPtr]['content']) !== 'function'
        ) {
            // This is a function declared without the "function" keyword.
            // So this token is the function name.
            return $tokens[$stackPtr]['content'];
        }

        /*
         * Determine the name. Note that we cannot simply look for the first T_STRING
         * because an (invalid) class name starting with a number will be multiple tokens.
         * Whitespace or comment are however not allowed within a name.
         */

        $stopPoint = $phpcsFile->numTokens;
        if ($tokenCode === \T_FUNCTION && isset($tokens[$stackPtr]['parenthesis_opener']) === true) {
            $stopPoint = $tokens[$stackPtr]['parenthesis_opener'];
        } elseif (isset($tokens[$stackPtr]['scope_opener']) === true) {
            $stopPoint = $tokens[$stackPtr]['scope_opener'];
        }

        $exclude   = Tokens::$emptyTokens;
        $exclude[] = \T_OPEN_PARENTHESIS;
        $exclude[] = \T_OPEN_CURLY_BRACKET;
        $exclude[] = \T_BITWISE_AND;
        $exclude[] = \T_COLON; // Backed enums.

        $nameStart = $phpcsFile->findNext($exclude, ($stackPtr + 1), $stopPoint, true);
        if ($nameStart === false) {
            // Live coding or parse error.
            return null;
        }

        $tokenAfterNameEnd = $phpcsFile->findNext($exclude, $nameStart, $stopPoint);

        if ($tokenAfterNameEnd === false) {
            return $tokens[$nameStart]['content'];
        }

        // Name starts with number, so is composed of multiple tokens.
        return GetTokensAsString::noEmpties($phpcsFile, $nameStart, ($tokenAfterNameEnd - 1));
    }

    /**
     * Retrieves the implementation properties of a class.
     *
     * Main differences with the PHPCS version:
     * - Bugs fixed:
     *   - Handling of PHPCS annotations.
     *   - Handling of unorthodox docblock placement.
     * - Defensive coding against incorrect calls to this method.
     * - Additional `'abstract_token'`, `'final_token'`, and `'readonly_token'` indexes in the return array.
     *
     * @see \PHP_CodeSniffer\Files\File::getClassProperties()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::getClassProperties() Cross-version compatible version of the original.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the `T_CLASS`
     *                                               token to acquire the properties for.
     *
     * @return array<string, int|bool> Array with implementation properties of a class.
     *               The format of the return value is:
     *               ```php
     *               array(
     *                 'is_abstract'    => bool,      // TRUE if the abstract keyword was found.
     *                 'abstract_token' => int|false, // The stack pointer to the `abstract` keyword or
     *                                                // FALSE if the abstract keyword was not found.
     *                 'is_final'       => bool,      // TRUE if the final keyword was found.
     *                 'final_token'    => int|false, // The stack pointer to the `final` keyword or
     *                                                // FALSE if the abstract keyword was not found.
     *                 'is_readonly'    => bool,      // TRUE if the readonly keyword was found.
     *                 'readonly_token' => int|false, // The stack pointer to the `readonly` keyword or
     *                                                // FALSE if the abstract keyword was not found.
     *               );
     *               ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_CLASS token.
     */
    public static function getClassProperties(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_CLASS) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_CLASS', $tokens[$stackPtr]['type']);
        }

        $valid      = Collections::classModifierKeywords() + Tokens::$emptyTokens;
        $properties = [
            'is_abstract'    => false,
            'abstract_token' => false,
            'is_final'       => false,
            'final_token'    => false,
            'is_readonly'    => false,
            'readonly_token' => false,
        ];

        for ($i = ($stackPtr - 1); $i > 0; $i--) {
            if (isset($valid[$tokens[$i]['code']]) === false) {
                break;
            }

            switch ($tokens[$i]['code']) {
                case \T_ABSTRACT:
                    $properties['is_abstract']    = true;
                    $properties['abstract_token'] = $i;
                    break;

                case \T_FINAL:
                    $properties['is_final']    = true;
                    $properties['final_token'] = $i;
                    break;

                case \T_READONLY:
                    $properties['is_readonly']    = true;
                    $properties['readonly_token'] = $i;
                    break;
            }
        }

        return $properties;
    }

    /**
     * Retrieves the name of the class that the specified class extends.
     *
     * Works for classes, anonymous classes and interfaces, though it is strongly recommended
     * to use the {@see \PHPCSUtils\Utils\ObjectDeclarations::findExtendedInterfaceNames()}
     * method to examine interfaces instead. Interfaces can extend multiple parent interfaces,
     * and that use-case is not handled by this method.
     *
     * Main differences with the PHPCS version:
     * - Bugs fixed:
     *   - Handling of PHPCS annotations.
     *   - Handling of comments.
     * - Improved handling of parse errors.
     * - The returned name will be clean of superfluous whitespace and/or comments.
     * - Support for PHP 8.0 tokenization of identifier/namespaced names, cross-version PHP & PHPCS.
     *
     * @see \PHP_CodeSniffer\Files\File::findExtendedClassName()               Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::findExtendedClassName()             Cross-version compatible version of
     *                                                                         the original.
     * @see \PHPCSUtils\Utils\ObjectDeclarations::findExtendedInterfaceNames() Similar method for extended interfaces.
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
        $names = self::findNames($phpcsFile, $stackPtr, \T_EXTENDS, Collections::ooCanExtend());
        if ($names === false) {
            return false;
        }

        // Classes can only extend one parent class.
        return \array_shift($names);
    }

    /**
     * Retrieves the names of the interfaces that the specified class or enum implements.
     *
     * Main differences with the PHPCS version:
     * - Bugs fixed:
     *   - Handling of PHPCS annotations.
     *   - Handling of comments.
     * - Improved handling of parse errors.
     * - The returned name(s) will be clean of superfluous whitespace and/or comments.
     * - Support for PHP 8.0 tokenization of identifier/namespaced names, cross-version PHP & PHPCS.
     *
     * @see \PHP_CodeSniffer\Files\File::findImplementedInterfaceNames()   Original source.
     * @see \PHPCSUtils\BackCompat\BCFile::findImplementedInterfaceNames() Cross-version compatible version of
     *                                                                     the original.
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
        return self::findNames($phpcsFile, $stackPtr, \T_IMPLEMENTS, Collections::ooCanImplement());
    }

    /**
     * Retrieves the names of the interfaces that the specified interface extends.
     *
     * @see \PHPCSUtils\Utils\ObjectDeclarations::findExtendedClassName() Similar method for extended classes.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack position of the interface keyword.
     *
     * @return array<string>|false Array with names of the extended interfaces or `FALSE` on
     *                             error or if there are no extended interface names.
     */
    public static function findExtendedInterfaceNames(File $phpcsFile, $stackPtr)
    {
        return self::findNames(
            $phpcsFile,
            $stackPtr,
            \T_EXTENDS,
            [\T_INTERFACE => \T_INTERFACE]
        );
    }

    /**
     * Retrieves the names of the extended classes or interfaces or the implemented
     * interfaces that the specific class/interface declaration extends/implements.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File   $phpcsFile  The file where this token was found.
     * @param int                           $stackPtr   The stack position of the
     *                                                  class/interface declaration keyword.
     * @param int                           $keyword    The token constant for the keyword to examine.
     *                                                  Either `T_EXTENDS` or `T_IMPLEMENTS`.
     * @param array<int|string, int|string> $allowedFor Array of OO types for which use of the keyword
     *                                                  is allowed.
     *
     * @return array<string>|false Returns an array of names or `FALSE` on error or when the object
     *                             being declared does not extend/implement another object.
     */
    private static function findNames(File $phpcsFile, $stackPtr, $keyword, array $allowedFor)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]) === false
            || isset($allowedFor[$tokens[$stackPtr]['code']]) === false
            || isset($tokens[$stackPtr]['scope_opener']) === false
        ) {
            return false;
        }

        $scopeOpener = $tokens[$stackPtr]['scope_opener'];
        $keywordPtr  = $phpcsFile->findNext($keyword, ($stackPtr + 1), $scopeOpener);
        if ($keywordPtr === false) {
            return false;
        }

        $find  = Collections::namespacedNameTokens() + Tokens::$emptyTokens;
        $names = [];
        $end   = $keywordPtr;
        do {
            $start = ($end + 1);
            $end   = $phpcsFile->findNext($find, $start, ($scopeOpener + 1), true);
            $name  = GetTokensAsString::noEmpties($phpcsFile, $start, ($end - 1));

            if (\trim($name) !== '') {
                $names[] = $name;
            }
        } while ($tokens[$end]['code'] === \T_COMMA);

        if (empty($names)) {
            return false;
        }

        return $names;
    }

    /**
     * Retrieve the stack pointers to the attribute openers for any attribute block which applies to the OO declaration.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position in the stack of the OO token to
     *                                               acquire the attributes for.
     *
     * @return array<int> Array with the stack pointers to the applicable attribute openers
     *                    or an empty array if there are no attributes attached to the OO declaration.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_CLASS`, `T_ANON_CLASS`,
     *                                                    `T_TRAIT`, `T_ENUM` or `T_INTERFACE` token.
     */
    public static function getAttributeOpeners(File $phpcsFile, $stackPtr)
    {
        return AttributeHelper::getOpeners($phpcsFile, $stackPtr, 'OO');
    }

    /**
     * Retrieve all constants declared in an OO structure.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack position of the OO keyword.
     *
     * @return array<string, int> Array with names of the found constants as keys and the stack pointers
     *                            to the T_CONST token for each constant as values.
     *                            If no constants are found or a parse error is encountered,
     *                            an empty array is returned.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not an OO keyword token.
     */
    public static function getDeclaredConstants(File $phpcsFile, $stackPtr)
    {
        return self::analyzeOOStructure($phpcsFile, $stackPtr)['constants'];
    }

    /**
     * Retrieve all cases declared in an enum.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack position of the OO keyword.
     *
     * @return array<string, int> Array with names of the found cases as keys and the stack pointers
     *                            to the T_ENUM_CASE token for each case as values.
     *                            If no cases are found or a parse error is encountered,
     *                            an empty array is returned.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a T_ENUM token.
     */
    public static function getDeclaredEnumCases(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_ENUM) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_ENUM', $tokens[$stackPtr]['type']);
        }

        return self::analyzeOOStructure($phpcsFile, $stackPtr)['cases'];
    }

    /**
     * Retrieve all properties declared in an OO structure.
     *
     * Notes:
     * - Properties declared via PHP 8.0+ contructor property promotion **will** be included
     *   in the return value.
     *   However, keep in mind that passing the stack pointer of such a property to the
     *   {@see Variables::getMemberProperties()} method is not supported.
     * - Interfaces (prior to PHP 8.4) and enums cannot contain properties. This method does not take this into
     *   account to allow sniffs to flag this kind of incorrect PHP code.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack position of the OO keyword.
     *
     * @return array<string, int> Array with names of the found properties as keys and the stack pointers
     *                            to the T_VARIABLE token for each property as values.
     *                            If no properties are found or a parse error is encountered,
     *                            an empty array is returned.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not an OO keyword token.
     */
    public static function getDeclaredProperties(File $phpcsFile, $stackPtr)
    {
        return self::analyzeOOStructure($phpcsFile, $stackPtr)['properties'];
    }

    /**
     * Retrieve all methods declared in an OO structure.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack pointer to the OO keyword.
     *
     * @return array<string, int> Array with names of the found methods as keys and the stack pointers
     *                            to the T_FUNCTION keyword for each method as values.
     *                            If no methods are found or a parse error is encountered,
     *                            an empty array is returned.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not an OO keyword token.
     */
    public static function getDeclaredMethods(File $phpcsFile, $stackPtr)
    {
        return self::analyzeOOStructure($phpcsFile, $stackPtr)['methods'];
    }

    /**
     * Retrieve all constants, cases, properties and methods in an OO structure.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The stack position of the OO keyword.
     *
     * @return array<string, array<string, int>> Multi-dimensional array with four keys:
     *                                           - "constants"
     *                                           - "cases"
     *                                           - "properties"
     *                                           - "methods"
     *                                           Each index holds an associative array with the name of the "thing"
     *                                           as the key and the stack pointer to the related token as the value.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not an OO keyword token.
     */
    private static function analyzeOOStructure(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if (isset(Tokens::$ooScopeTokens[$tokens[$stackPtr]['code']]) === false) {
            $acceptedTokens = 'T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT or T_ENUM';
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        // Set defaults.
        $found = [
            'constants'  => [],
            'cases'      => [],
            'properties' => [],
            'methods'    => [],
        ];

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            return $found;
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        for ($i = ($tokens[$stackPtr]['scope_opener'] + 1); $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            // Skip over potentially large docblocks.
            if (isset($tokens[$i]['comment_closer']) === true) {
                $i = $tokens[$i]['comment_closer'];
                continue;
            }

            // Skip over attributes.
            if (isset($tokens[$i]['attribute_closer']) === true) {
                $i = $tokens[$i]['attribute_closer'];
                continue;
            }

            // Skip over trait imports with conflict resolution.
            if ($tokens[$i]['code'] === \T_USE
                && isset($tokens[$i]['scope_closer']) === true
            ) {
                $i = $tokens[$i]['scope_closer'];
                continue;
            }

            // Defensive coding against parse errors.
            if ($tokens[$i]['code'] === \T_CLOSURE
                && isset($tokens[$i]['scope_closer']) === true
            ) {
                $i = $tokens[$i]['scope_closer'];
                continue;
            }

            switch ($tokens[$i]['code']) {
                case \T_CONST:
                    $assignmentPtr = $phpcsFile->findNext([\T_EQUAL, \T_SEMICOLON, \T_CLOSE_CURLY_BRACKET], ($i + 1));
                    if ($assignmentPtr === false || $tokens[$assignmentPtr]['code'] !== \T_EQUAL) {
                        // Probably a parse error. Ignore.
                        continue 2;
                    }

                    $namePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($assignmentPtr - 1), ($i + 1), true);
                    if ($namePtr === false || $tokens[$namePtr]['code'] !== \T_STRING) {
                        // Probably a parse error. Ignore.
                        continue 2;
                    }

                    $found['constants'][$tokens[$namePtr]['content']] = $i;

                    // Skip to the assignment pointer, no need to double walk.
                    $i = $assignmentPtr;
                    break;

                case \T_ENUM_CASE:
                    $namePtr = $phpcsFile->findNext(Tokens::$emptyTokens, ($i + 1), null, true);
                    if ($namePtr === false || $tokens[$namePtr]['code'] !== \T_STRING) {
                        // Probably a parse error. Ignore.
                        continue 2;
                    }

                    $name                  = $tokens[$namePtr]['content'];
                    $found['cases'][$name] = $i;

                    // Skip to the name pointer, no need to double walk.
                    $i = $namePtr;
                    break;

                case \T_VARIABLE:
                    $name                       = $tokens[$i]['content'];
                    $found['properties'][$name] = $i;
                    break;

                case \T_FUNCTION:
                    $name = self::getName($phpcsFile, $i);
                    if (\is_string($name) && $name !== '') {
                        $found['methods'][$name] = $i;

                        if (\strtolower($name) === '__construct') {
                            // Check for constructor property promotion.
                            $parameters = FunctionDeclarations::getParameters($phpcsFile, $i);
                            foreach ($parameters as $param) {
                                if (isset($param['property_visibility'])) {
                                    $found['properties'][$param['name']] = $param['token'];
                                }
                            }
                        }
                    }

                    if (isset($tokens[$i]['scope_closer']) === true) {
                        // Skip over the contents of the method, including the parameters.
                        $i = $tokens[$i]['scope_closer'];
                    } elseif (isset($tokens[$i]['parenthesis_closer']) === true) {
                        // Skip over the contents of an abstract/interface method, including the parameters.
                        $i = $tokens[$i]['parenthesis_closer'];
                    }
                    break;
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $found);
        return $found;
    }
}
