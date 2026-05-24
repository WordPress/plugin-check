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
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\Parentheses;

/**
 * Utility functions for examining use statements.
 *
 * @since 1.0.0
 */
final class UseStatements
{

    /**
     * Determine what a T_USE token is used for.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_USE` token.
     *
     * @return string Either `'closure'`, `'import'` or `'trait'`.
     *                An empty string will be returned if the token is used in an
     *                invalid context or if it couldn't be reliably determined what
     *                the `T_USE` token is used for. An empty string being returned will
     *                normally mean the code being examined contains a parse error.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_USE` token.
     */
    public static function getType(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if ($tokens[$stackPtr]['code'] !== \T_USE) {
            throw UnexpectedTokenType::create(2, '$stackPtr', 'T_USE', $tokens[$stackPtr]['type']);
        }

        // More efficient & simpler check for closure use in PHPCS 4.x.
        if (isset($tokens[$stackPtr]['parenthesis_owner'])
            && $tokens[$stackPtr]['parenthesis_owner'] === $stackPtr
        ) {
            return 'closure';
        }

        $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prev !== false && $tokens[$prev]['code'] === \T_CLOSE_PARENTHESIS
            // T_FUNCTION is included to handle certain parse errors, which are still clearly closure use, correctly.
            && Parentheses::isOwnerIn($phpcsFile, $prev, [\T_CLOSURE, \T_FUNCTION]) === true
        ) {
            return 'closure';
        }

        $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($next === false) {
            // Live coding or parse error.
            return '';
        }

        $lastCondition = Conditions::getLastCondition($phpcsFile, $stackPtr);
        if ($lastCondition === false || $tokens[$lastCondition]['code'] === \T_NAMESPACE) {
            // Global or scoped namespace and not a closure use statement.
            return 'import';
        }

        $traitScopes = Tokens::$ooScopeTokens;
        // Only classes, traits and enums can import traits.
        unset($traitScopes[\T_INTERFACE]);

        if (isset($traitScopes[$tokens[$lastCondition]['code']]) === true) {
            return 'trait';
        }

        return '';
    }

    /**
     * Determine whether a T_USE token represents a closure use statement.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_USE` token.
     *
     * @return bool `TRUE` if the token passed is a closure use statement.
     *              `FALSE` if it's not.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_USE` token.
     */
    public static function isClosureUse(File $phpcsFile, $stackPtr)
    {
        return (self::getType($phpcsFile, $stackPtr) === 'closure');
    }

    /**
     * Determine whether a T_USE token represents a class/function/constant import use statement.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_USE` token.
     *
     * @return bool `TRUE` if the token passed is an import use statement.
     *              `FALSE` if it's not.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_USE` token.
     */
    public static function isImportUse(File $phpcsFile, $stackPtr)
    {
        return (self::getType($phpcsFile, $stackPtr) === 'import');
    }

    /**
     * Determine whether a T_USE token represents a trait use statement.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the `T_USE` token.
     *
     * @return bool `TRUE` if the token passed is a trait use statement.
     *              `FALSE` if it's not.
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_USE` token.
     */
    public static function isTraitUse(File $phpcsFile, $stackPtr)
    {
        return (self::getType($phpcsFile, $stackPtr) === 'trait');
    }

    /**
     * Split an import use statement into individual imports.
     *
     * Handles single import, multi-import and group-import use statements.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position in the stack of the `T_USE` token.
     *
     * @return array<string, array<string, string>>
     *               A multi-level array containing information about the use statement.
     *               The first level is `'name'`, `'function'` and `'const'`. These keys will always exist.
     *               If any statements are found for any of these categories, the second level
     *               will contain the alias/name as the key and the full original use name as the
     *               value for each of the found imports or an empty array if no imports were found
     *               in this use statement for a particular category.
     *
     *               For example, for this function group use statement:
     *               ```php
     *               use function Vendor\Package\{
     *                   LevelA\Name as Alias,
     *                   LevelB\Another_Name,
     *               };
     *               ```
     *               the return value would look like this:
     *               ```php
     *               array(
     *                 'name'     => array(),
     *                 'function' => array(
     *                   'Alias'        => 'Vendor\Package\LevelA\Name',
     *                   'Another_Name' => 'Vendor\Package\LevelB\Another_Name',
     *                 ),
     *                 'const'    => array(),
     *               )
     *               ```
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not a `T_USE` token.
     * @throws \PHPCSUtils\Exceptions\ValueError          If the `T_USE` token is not for an import use statement.
     */
    public static function splitImportUseStatement(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (self::isImportUse($phpcsFile, $stackPtr) === false) {
            throw ValueError::create(2, '$stackPtr', 'must be the pointer to an import use statement');
        }

        if (Cache::isCached($phpcsFile, __METHOD__, $stackPtr) === true) {
            return Cache::get($phpcsFile, __METHOD__, $stackPtr);
        }

        $statements = [
            'name'     => [],
            'function' => [],
            'const'    => [],
        ];

        $endOfStatement = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG], ($stackPtr + 1));
        if ($endOfStatement === false) {
            // Live coding or parse error.
            Cache::set($phpcsFile, __METHOD__, $stackPtr, $statements);
            return $statements;
        }

        ++$endOfStatement;

        $start     = true;
        $useGroup  = false;
        $hasAlias  = false;
        $baseName  = '';
        $name      = '';
        $type      = '';
        $fixedType = false;
        $alias     = '';

        for ($i = ($stackPtr + 1); $i < $endOfStatement; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === true) {
                continue;
            }

            $tokenCode = $tokens[$i]['code'];
            switch ($tokenCode) {
                case \T_STRING:
                    // Only when either at the start of the statement or at the start of a new sub within a group.
                    if ($start === true && $fixedType === false) {
                        $content = \strtolower($tokens[$i]['content']);
                        if ($content === 'function'
                            || $content === 'const'
                        ) {
                            $type  = $content;
                            $start = false;
                            if ($useGroup === false) {
                                $fixedType = true;
                            }

                            break;
                        } else {
                            $type = 'name';
                        }
                    }

                    $start = false;

                    if ($hasAlias === false) {
                        $name .= $tokens[$i]['content'];
                    }

                    $alias = $tokens[$i]['content'];
                    break;

                case \T_NAME_QUALIFIED:
                case \T_NAME_FULLY_QUALIFIED: // This would be a parse error, but handle it anyway.
                    /*
                     * PHPCS 4.x.
                     *
                     * These tokens can only be encountered when either at the start of the statement
                     * or at the start of a new sub within a group.
                     */
                    if ($start === true && $fixedType === false) {
                        $type = 'name';
                    }

                    $start = false;

                    if ($hasAlias === false) {
                        $name .= $tokens[$i]['content'];
                    }

                    $alias = \substr($tokens[$i]['content'], (\strrpos($tokens[$i]['content'], '\\') + 1));
                    break;

                case \T_AS:
                    $hasAlias = true;
                    break;

                case \T_OPEN_USE_GROUP:
                    $start    = true;
                    $useGroup = true;
                    $baseName = $name;
                    $name     = '';
                    break;

                case \T_SEMICOLON:
                case \T_CLOSE_TAG:
                case \T_CLOSE_USE_GROUP:
                case \T_COMMA:
                    if ($name !== '') {
                        if ($useGroup === true) {
                            $statements[$type][$alias] = \ltrim($baseName, '\\') . $name;
                        } else {
                            $statements[$type][$alias] = \ltrim($name, '\\');
                        }
                    }

                    if ($tokenCode !== \T_COMMA) {
                        break 2;
                    }

                    // Reset.
                    $start    = true;
                    $name     = '';
                    $hasAlias = false;
                    if ($fixedType === false) {
                        $type = '';
                    }
                    break;

                case \T_NS_SEPARATOR:
                    $name .= $tokens[$i]['content'];
                    break;

                /*
                 * Fall back in case reserved keyword is (illegally) used in name.
                 * Parse error, but not our concern.
                 */
                default:
                    if ($hasAlias === false) {
                        // Defensive coding, just in case. Should no longer be possible since PHPCS 3.7.0.
                        $name .= $tokens[$i]['content']; // @codeCoverageIgnore
                    }

                    $alias = $tokens[$i]['content'];
                    break;
            }
        }

        Cache::set($phpcsFile, __METHOD__, $stackPtr, $statements);
        return $statements;
    }

    /**
     * Split an import use statement into individual imports and merge it with an array of previously
     * seen import use statements.
     *
     * Beware: this method should only be used to combine the import use statements found in *one* file.
     * Do NOT combine the statements of multiple files as the result will be inaccurate and unreliable.
     *
     * In most cases when tempted to use this method, the {@see \PHPCSUtils\AbstractSniffs\AbstractFileContextSniff}
     * (upcoming) should be used instead.
     *
     * @see \PHPCSUtils\AbstractSniffs\AbstractFileContextSniff
     * @see \PHPCSUtils\Utils\UseStatements::splitImportUseStatement()
     * @see \PHPCSUtils\Utils\UseStatements::mergeImportUseStatements()
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File          $phpcsFile             The file where this token was found.
     * @param int                                  $stackPtr              The position in the stack of the
     *                                                                    `T_USE` token.
     * @param array<string, array<string, string>> $previousUseStatements The import `use` statements collected so far.
     *                                                                    This should be either the output of a
     *                                                                    previous call to this method or the output of
     *                                                                    an earlier call to the
     *                                                                    {@see UseStatements::splitImportUseStatement()}
     *                                                                    method.
     *
     * @return array<string, array<string, string>>
     *               A multi-level array containing information about the current `use` statement combined with
     *               the previously collected `use` statement information.
     *               See {@see UseStatements::splitImportUseStatement()} for more details about the array format.
     */
    public static function splitAndMergeImportUseStatement(File $phpcsFile, $stackPtr, array $previousUseStatements)
    {
        try {
            $useStatements         = self::splitImportUseStatement($phpcsFile, $stackPtr);
            $previousUseStatements = self::mergeImportUseStatements($previousUseStatements, $useStatements);
        } catch (ValueError $e) {
            // Not an import use statement.
        }

        return $previousUseStatements;
    }

    /**
     * Merge two import use statement arrays.
     *
     * Beware: this method should only be used to combine the import use statements found in *one* file.
     * Do NOT combine the statements of multiple files as the result will be inaccurate and unreliable.
     *
     * @see \PHPCSUtils\Utils\UseStatements::splitImportUseStatement()
     *
     * @since 1.0.0
     *
     * @param array<string, array<string, string>> $previousUseStatements The import `use` statements collected so far.
     *                                                                    This should be either the output of a
     *                                                                    previous call to this method or the output of
     *                                                                    an earlier call to the
     *                                                                    {@see UseStatements::splitImportUseStatement()}
     *                                                                    method.
     * @param array<string, array<string, string>> $currentUseStatement   The parsed import `use` statements to merge with
     *                                                                    the previously collected use statements.
     *                                                                    This should be the output of a call to the
     *                                                                    {@see UseStatements::splitImportUseStatement()}
     *                                                                    method.
     *
     * @return array<string, array<string, string>>
     *               A multi-level array containing information about the current `use` statement combined with
     *               the previously collected `use` statement information.
     *               See {@see UseStatements::splitImportUseStatement()} for more details about the array format.
     */
    public static function mergeImportUseStatements(array $previousUseStatements, array $currentUseStatement)
    {
        if (isset($previousUseStatements['name']) === false) {
            $previousUseStatements['name'] = $currentUseStatement['name'];
        } else {
            $previousUseStatements['name'] += $currentUseStatement['name'];
        }
        if (isset($previousUseStatements['function']) === false) {
            $previousUseStatements['function'] = $currentUseStatement['function'];
        } else {
            $previousUseStatements['function'] += $currentUseStatement['function'];
        }
        if (isset($previousUseStatements['const']) === false) {
            $previousUseStatements['const'] = $currentUseStatement['const'];
        } else {
            $previousUseStatements['const'] += $currentUseStatement['const'];
        }

        return $previousUseStatements;
    }
}
