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
use PHPCSUtils\Exceptions\MissingArgumentError;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Utility functions to retrieve information about parameters passed to function calls,
 * class instantiations, array declarations, isset and unset constructs.
 *
 * @since 1.0.0
 */
final class PassedParameters
{

    /**
     * Tokens which are considered stop point, either because they are the end
     * of the parameter (comma) or because we need to skip over them.
     *
     * @since 1.0.0
     *
     * @var array<int|string, int|string>
     */
    private static $callParsingStopPoints = [
        \T_COMMA                => \T_COMMA,
        \T_OPEN_SHORT_ARRAY     => \T_OPEN_SHORT_ARRAY,
        \T_OPEN_SQUARE_BRACKET  => \T_OPEN_SQUARE_BRACKET,
        \T_OPEN_PARENTHESIS     => \T_OPEN_PARENTHESIS,
        \T_DOC_COMMENT_OPEN_TAG => \T_DOC_COMMENT_OPEN_TAG,
        \T_ATTRIBUTE            => \T_ATTRIBUTE,
    ];

    /**
     * Checks if any parameters have been passed.
     *
     * - If passed a `T_STRING`, `T_NAME_FULLY_QUALIFIED`, `T_NAME_RELATIVE`, `T_NAME_QUALIFIED`,
     *   or `T_VARIABLE` stack pointer, it will treat it as a function call.
     *   If a token which is *not* a function call is passed, the behaviour is undetermined.
     * - If passed a `T_ANON_CLASS` stack pointer, it will accept it as a class instantiation.
     * - If passed a `T_SELF`, `T_STATIC` or `T_PARENT` stack pointer, it will accept it as a
     *   class instantiation function call when used like `new self()` (with or without parentheses).
     *   When these hierarchical keywords are not preceded by the `new` keyword, parentheses
     *   will be required for the token to be accepted.
     * - If passed a `T_ARRAY` or `T_OPEN_SHORT_ARRAY` stack pointer, it will detect
     *   whether the array has values or is empty.
     *   For purposes of backward-compatibility with older PHPCS versions, `T_OPEN_SQUARE_BRACKET`
     *   tokens will also be accepted and will be checked whether they are in reality
     *   a short array opener.
     * - If passed a `T_ISSET` or `T_UNSET` stack pointer, it will detect whether those
     *   language constructs have "parameters".
     * - If passed a `T_EXIT` stack pointer, it will treat it as a function call and detect whether
     *   it has been passed parameters. When the `T_EXIT` is used as a constant, the return value
     *   will be `false` (no parameters).
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file where this token was found.
     * @param int                         $stackPtr     The position of function call name,
     *                                                  language construct or array open token.
     * @param true|null                   $isShortArray Optional. Short-circuit the short array check for
     *                                                  `T_OPEN_SHORT_ARRAY` tokens if it isn't necessary.
     *                                                  Efficiency tweak for when this has already been established,
     *                                                  Use with EXTREME care.
     *
     * @return bool
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not one of the accepted types.
     */
    public static function hasParameters(File $phpcsFile, $stackPtr, $isShortArray = null)
    {
        $tokens = $phpcsFile->getTokens();

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        $acceptedTokens = 'function call, array, isset, unset or exit';
        if (isset(Collections::parameterPassingTokens()[$tokens[$stackPtr]['code']]) === false) {
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        // Only accept self/static/parent if preceded by `new` or followed by an open parenthesis.
        $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if (isset(Collections::ooHierarchyKeywords()[$tokens[$stackPtr]['code']]) === true) {
            $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
            if ($tokens[$prev]['code'] !== \T_NEW
                && ($next !== false && $tokens[$next]['code'] !== \T_OPEN_PARENTHESIS)
            ) {
                throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
            }
        }

        if (isset(Collections::shortArrayListOpenTokensBC()[$tokens[$stackPtr]['code']]) === true
            && $isShortArray !== true
            && Arrays::isShortArray($phpcsFile, $stackPtr) === false
        ) {
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        if ($next === false) {
            return false;
        }

        // Deal with short array syntax.
        if (isset(Collections::shortArrayListOpenTokensBC()[$tokens[$stackPtr]['code']]) === true) {
            if ($next === $tokens[$stackPtr]['bracket_closer']) {
                // No parameters.
                return false;
            }

            return true;
        }

        // Deal with function calls, long arrays, isset, unset and exit/die.
        // Next non-empty token should be the open parenthesis.
        if ($tokens[$next]['code'] !== \T_OPEN_PARENTHESIS) {
            return false;
        }

        if (isset($tokens[$next]['parenthesis_closer']) === false) {
            return false;
        }

        $ignore              = Tokens::$emptyTokens;
        $ignore[\T_ELLIPSIS] = \T_ELLIPSIS; // Prevent PHP 8.1 first class callables from being seen as function calls.

        $closeParenthesis = $tokens[$next]['parenthesis_closer'];
        $nextNextNonEmpty = $phpcsFile->findNext($ignore, ($next + 1), ($closeParenthesis + 1), true);

        if ($nextNextNonEmpty === $closeParenthesis) {
            // No parameters.
            return false;
        }

        return true;
    }

    /**
     * Get information on all parameters passed.
     *
     * See {@see PassedParameters::hasParameters()} for information on the supported constructs.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file where this token was found.
     * @param int                         $stackPtr     The position of function call name,
     *                                                  language construct or array open token.
     * @param int                         $limit        Optional. Limit the parameter retrieval to the first #
     *                                                  parameters/array entries.
     *                                                  Use with care on function calls, as this can break
     *                                                  support for named parameters!
     * @param true|null                   $isShortArray Optional. Short-circuit the short array check for
     *                                                  `T_OPEN_SHORT_ARRAY` tokens if it isn't necessary.
     *                                                  Efficiency tweak for when this has already been established,
     *                                                  Use with EXTREME care.
     *
     * @return array<int|string, array<string, int|string>>
     *               A multi-dimentional array with information on each parameter/array item.
     *               The information gathered about each parameter/array item is in the following format:
     *               ```php
     *               1 => array(
     *                 'start' => int,    // The stack pointer to the first token in the parameter/array item.
     *                 'end'   => int,    // The stack pointer to the last token in the parameter/array item.
     *                 'raw'   => string, // A string with the contents of all tokens between `start` and `end`.
     *                 'clean' => string, // Same as `raw`, but all comment tokens have been stripped out.
     *               )
     *               ```
     *               If a named parameter is encountered in a function call, the top-level index will not be
     *               the parameter _position_, but the _parameter name_ and the array will include two extra keys:
     *               ```php
     *               'parameter_name' => array(
     *                 'name'       => string, // The parameter name (without the colon).
     *                 'name_token' => int,    // The stack pointer to the parameter name token.
     *                 ...
     *               )
     *               ```
     *               The `'start'`, `'end'`, `'raw'` and `'clean'` indexes will always contain just and only
     *               information on the parameter value.
     *               _Note: The array starts at index 1 for positional parameters._
     *                     _The key for named parameters will be the parameter name._
     *               If no parameters/array items are found, an empty array will be returned.
     *
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not one of the accepted types.
     */
    public static function getParameters(File $phpcsFile, $stackPtr, $limit = 0, $isShortArray = null)
    {
        if (self::hasParameters($phpcsFile, $stackPtr, $isShortArray) === false) {
            return [];
        }

        $effectiveLimit = (\is_int($limit) && $limit > 0) ? $limit : 0;

        if (Cache::isCached($phpcsFile, __METHOD__, "$stackPtr-$effectiveLimit") === true) {
            return Cache::get($phpcsFile, __METHOD__, "$stackPtr-$effectiveLimit");
        }

        if ($effectiveLimit !== 0 && Cache::isCached($phpcsFile, __METHOD__, "$stackPtr-0") === true) {
            return \array_slice(Cache::get($phpcsFile, __METHOD__, "$stackPtr-0"), 0, $effectiveLimit, true);
        }

        // Ok, we know we have a valid token with parameters and valid open & close brackets/parenthesis.
        $tokens = $phpcsFile->getTokens();

        // Mark the beginning and end tokens.
        if (isset(Collections::shortArrayListOpenTokensBC()[$tokens[$stackPtr]['code']]) === true) {
            $opener = $stackPtr;
            $closer = $tokens[$stackPtr]['bracket_closer'];
        } else {
            $opener = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            $closer = $tokens[$opener]['parenthesis_closer'];
        }

        $mayHaveNames = (isset(Collections::functionCallTokens()[$tokens[$stackPtr]['code']]) === true);

        $parameters   = [];
        $nextComma    = $opener;
        $paramStart   = ($opener + 1);
        $cnt          = 1;
        $stopPoints   = self::$callParsingStopPoints + Tokens::$scopeOpeners;
        $stopPoints[] = $tokens[$closer]['code'];

        while (($nextComma = $phpcsFile->findNext($stopPoints, ($nextComma + 1), ($closer + 1))) !== false) {
            // Ignore anything within square brackets.
            if (isset($tokens[$nextComma]['bracket_opener'], $tokens[$nextComma]['bracket_closer'])
                && $nextComma === $tokens[$nextComma]['bracket_opener']
            ) {
                $nextComma = $tokens[$nextComma]['bracket_closer'];
                continue;
            }

            // Skip past nested arrays, function calls and arbitrary groupings.
            if ($tokens[$nextComma]['code'] === \T_OPEN_PARENTHESIS
                && isset($tokens[$nextComma]['parenthesis_closer'])
            ) {
                $nextComma = $tokens[$nextComma]['parenthesis_closer'];
                continue;
            }

            // Skip past closures, anonymous classes and anything else scope related.
            if (isset($tokens[$nextComma]['scope_condition'], $tokens[$nextComma]['scope_closer'])
                && $tokens[$nextComma]['scope_condition'] === $nextComma
            ) {
                $nextComma = $tokens[$nextComma]['scope_closer'];
                continue;
            }

            // Skip over potentially large docblocks.
            if ($tokens[$nextComma]['code'] === \T_DOC_COMMENT_OPEN_TAG
                && isset($tokens[$nextComma]['comment_closer'])
            ) {
                $nextComma = $tokens[$nextComma]['comment_closer'];
                continue;
            }

            // Skip over attributes.
            if ($tokens[$nextComma]['code'] === \T_ATTRIBUTE
                && isset($tokens[$nextComma]['attribute_closer'])
            ) {
                $nextComma = $tokens[$nextComma]['attribute_closer'];
                continue;
            }

            if ($tokens[$nextComma]['code'] !== \T_COMMA
                && $tokens[$nextComma]['code'] !== $tokens[$closer]['code']
            ) {
                // Just in case.
                continue; // @codeCoverageIgnore
            }

            // Ok, we've reached the end of the parameter.
            $paramEnd = ($nextComma - 1);
            $key      = $cnt;

            if ($mayHaveNames === true) {
                $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, $paramStart, ($paramEnd + 1), true);
                if ($firstNonEmpty !== $paramEnd) {
                    $secondNonEmpty = $phpcsFile->findNext(
                        Tokens::$emptyTokens,
                        ($firstNonEmpty + 1),
                        ($paramEnd + 1),
                        true
                    );

                    if ($tokens[$secondNonEmpty]['code'] === \T_COLON
                        && $tokens[$firstNonEmpty]['code'] === \T_PARAM_NAME
                    ) {
                        if (isset($parameters[$tokens[$firstNonEmpty]['content']]) === false) {
                            // Set the key to be the name, but only if we've not seen this name before.
                            $key = $tokens[$firstNonEmpty]['content'];
                        }

                        $parameters[$key]['name']       = $tokens[$firstNonEmpty]['content'];
                        $parameters[$key]['name_token'] = $firstNonEmpty;
                        $paramStart                     = ($secondNonEmpty + 1);
                    }
                }
            }

            $parameters[$key]['start'] = $paramStart;
            $parameters[$key]['end']   = $paramEnd;
            $parameters[$key]['raw']   = \trim(GetTokensAsString::normal($phpcsFile, $paramStart, $paramEnd));
            $parameters[$key]['clean'] = \trim(GetTokensAsString::noComments($phpcsFile, $paramStart, $paramEnd));

            // Check if there are more tokens before the closing parenthesis.
            // Prevents function calls with trailing comma's from setting an extra parameter:
            // `functionCall( $param1, $param2, );`.
            $hasNextParam = $phpcsFile->findNext(
                Tokens::$emptyTokens,
                ($nextComma + 1),
                $closer,
                true
            );
            if ($hasNextParam === false) {
                // Reached the end, so for the purpose of caching, this should be saved as if no limit was set.
                $effectiveLimit = 0;
                break;
            }

            // Stop if there is a valid limit and the limit has been reached.
            if ($effectiveLimit !== 0 && $cnt === $effectiveLimit) {
                break;
            }

            // Prepare for the next parameter.
            $paramStart = ($nextComma + 1);
            ++$cnt;
        }

        if ($effectiveLimit !== 0 && $cnt === $effectiveLimit) {
            Cache::set($phpcsFile, __METHOD__, "$stackPtr-$effectiveLimit", $parameters);
        } else {
            // Limit is 0 or total items is less than effective limit.
            Cache::set($phpcsFile, __METHOD__, "$stackPtr-0", $parameters);
        }

        return $parameters;
    }

    /**
     * Get information on a specific parameter passed.
     *
     * See {@see PassedParameters::hasParameters()} for information on the supported constructs.
     *
     * @see PassedParameters::getParameterFromStack() For when the parameter stack of a function call is
     *                                                already retrieved.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile   The file where this token was found.
     * @param int                         $stackPtr    The position of function call name,
     *                                                 language construct or array open token.
     * @param int                         $paramOffset The 1-based index position of the parameter to retrieve.
     * @param string|array<string>        $paramNames  Optional. Either the name of the target parameter
     *                                                 to retrieve as a string or an array of names for the
     *                                                 same target parameter.
     *                                                 Only relevant for function calls.
     *                                                 An arrays of names is supported to allow for functions
     *                                                 for which the parameter names have undergone name
     *                                                 changes over time.
     *                                                 When specified, the name will take precedence over the
     *                                                 offset.
     *                                                 For PHP 8 support, it is STRONGLY recommended to
     *                                                 always pass both the offset as well as the parameter
     *                                                 name when examining function calls.
     *
     * @return array<string, int|string>|false Array with information on the parameter/array item at the specified
     *                                         offset, or with the specified name.
     *                                         Or `FALSE` if the specified parameter/array item is not found.
     *                                         See {@see PassedParameters::getParameters()} for the format of the
     *                                         returned (single-dimensional) array.
     *
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr  If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType  If the token passed is not one of the accepted types.
     * @throws \PHPCSUtils\Exceptions\MissingArgumentError If a function call parameter is requested and
     *                                                     the `$paramName` parameter is not passed.
     */
    public static function getParameter(File $phpcsFile, $stackPtr, $paramOffset, $paramNames = [])
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($paramNames) === true) {
            $parameters = self::getParameters($phpcsFile, $stackPtr, $paramOffset);
        } else {
            $parameters = self::getParameters($phpcsFile, $stackPtr);
        }

        /*
         * Non-function calls.
         */
        if (isset(Collections::functionCallTokens()[$tokens[$stackPtr]['code']]) === false) {
            if (isset($parameters[$paramOffset]) === true) {
                return $parameters[$paramOffset];
            }

            return false;
        }

        /*
         * Function calls.
         */
        return self::getParameterFromStack($parameters, $paramOffset, $paramNames);
    }

    /**
     * Count the number of parameters which have been passed.
     *
     * See {@see PassedParameters::hasParameters()} for information on the supported constructs.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position of function call name,
     *                                               language construct or array open token.
     *
     * @return int
     *
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not one of the accepted types.
     */
    public static function getParameterCount(File $phpcsFile, $stackPtr)
    {
        if (self::hasParameters($phpcsFile, $stackPtr) === false) {
            return 0;
        }

        return \count(self::getParameters($phpcsFile, $stackPtr));
    }

    /**
     * Get information on a specific function call parameter passed.
     *
     * This is an efficiency method to correctly handle positional versus named parameters
     * for function calls when multiple parameters need to be examined.
     *
     * See {@see PassedParameters::hasParameters()} for information on the supported constructs.
     *
     * @since 1.0.0
     *
     * @param array<int|string, array<string, int|string>> $parameters  The output of a previous call to
     *                                                                  {@see PassedParameters::getParameters()}.
     * @param int                                          $paramOffset The 1-based index position of the parameter
     *                                                                  to retrieve.
     * @param string|array<string>                         $paramNames  Either the name of the target parameter to retrieve
     *                                                                  as a string or an array of names for the same target
     *                                                                  parameter.
     *                                                                  An array of names is supported to allow for functions
     *                                                                  for which the parameter names have undergone name
     *                                                                  changes over time.
     *                                                                  The name will take precedence over the offset.
     *
     * @return array<string, int|string>|false Array with information on the parameter at the specified offset,
     *                                         or with the specified name.
     *                                         Or `FALSE` if the specified parameter is not found.
     *                                         See {@see PassedParameters::getParameters()} for the format of the
     *                                         returned (single-dimensional) array.
     *
     * @throws \PHPCSUtils\Exceptions\MissingArgumentError If the `$paramNames` parameter is not passed and the
     *                                                     requested parameter was not passed as a positional
     *                                                     parameter in the function call being examined.
     */
    public static function getParameterFromStack(array $parameters, $paramOffset, $paramNames)
    {
        if (empty($parameters) === true) {
            return false;
        }

        // First check for a named parameter.
        if (empty($paramNames) === false) {
            $paramNames = (array) $paramNames;
            foreach ($paramNames as $name) {
                // Note: parameter names are case-sensitive!.
                if (isset($parameters[$name]) === true) {
                    return $parameters[$name];
                }
            }
        }

        // Next check for positional parameters.
        if (isset($parameters[$paramOffset]) === true
            && isset($parameters[$paramOffset]['name']) === false
        ) {
            return $parameters[$paramOffset];
        }

        if (empty($paramNames) === true) {
            throw MissingArgumentError::create(3, '$paramNames', 'to allow for support for PHP 8 named parameters');
        }

        return false;
    }
}
