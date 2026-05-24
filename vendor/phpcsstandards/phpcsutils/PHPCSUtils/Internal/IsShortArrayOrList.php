<?php
/**
 * PHPCSUtils, utility functions and classes for PHP_CodeSniffer sniff developers.
 *
 * @package   PHPCSUtils
 * @copyright 2019-2020 PHPCSUtils Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSUtils
 */

namespace PHPCSUtils\Internal;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Exceptions\OutOfBoundsStackPtr;
use PHPCSUtils\Exceptions\TypeError;
use PHPCSUtils\Exceptions\UnexpectedTokenType;
use PHPCSUtils\Internal\Cache;
use PHPCSUtils\Internal\IsShortArrayOrListWithCache;
use PHPCSUtils\Internal\StableCollections;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\Parentheses;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Determination of short array vs short list vs square brackets.
 *
 * Short list versus short array determination is done based on the following:
 * - For "outer" arrays/lists the determination is straight forward, the surrounding
 *   tokens give enough clues. This includes "outer" arrays/lists in `foreach()` conditions
 *   or in attributes.
 * - For nested short arrays/lists, it's a whole different matter.
 *   - Both arrays as well as lists can be used when setting _keys_ in arrays and lists
 *     (with array access or as a parameter in a function call etc).
 *     For arrays, a nested array used as a key will always need array access.
 *     For lists, a plain array can be used as the key. (seriously ^@!%&#?)
 *     When used as a key though, the nesting is irrelevant and if there is nesting, the "outer"
 *     set of brackets will also be part of the key.
 *   - Both arrays as well as lists can be used as _values_ in arrays.
 *   - Only nested lists (or variables) can be used as _values_ in lists.
 *
 * ---------------------------------------------------------------------------------------------
 * This class is only intended for internal use by PHPCSUtils and is not part of the public API.
 * This also means that it has no promise of backward compatibility.
 *
 * End-users should use the {@see \PHPCSUtils\Utils\Arrays::isShortArray()}
 * or the {@see \PHPCSUtils\Utils\Lists::isShortList()} method instead.
 * ---------------------------------------------------------------------------------------------
 *
 * @internal
 *
 * @since 1.0.0
 */
final class IsShortArrayOrList
{

    /**
     * Type annotation for short arrays.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const SHORT_ARRAY = 'short array';

    /**
     * Type annotation for short lists.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const SHORT_LIST = 'short list';

    /**
     * Type annotation for square brackets.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const SQUARE_BRACKETS = 'square brackets';

    /**
     * Limit for the amount of items to retrieve from inside a nested array/list.
     *
     * @since 1.0.0
     *
     * @var int
     */
    const ITEM_LIMIT = 5;

    /**
     * Limit for recursing over inner nested arrays/lists.
     *
     * @since 1.0.0
     *
     * @var int
     */
    const RECURSION_LIMIT = 3;

    /**
     * The PHPCS file in which the current stackPtr was found.
     *
     * @since 1.0.0
     *
     * @var \PHP_CodeSniffer\Files\File
     */
    private $phpcsFile;

    /**
     * The token stack from the current file.
     *
     * @since 1.0.0
     *
     * @var array<int, array<string, mixed>>
     */
    private $tokens;

    /**
     * Stack pointer to the open bracket.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $opener;

    /**
     * Stack pointer to the close bracket.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $closer;

    /**
     * Stack pointer to the first non-empty token before the open bracket.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private $beforeOpener;

    /**
     * Stack pointer to the first non-empty token after the close bracket.
     *
     * @since 1.0.0
     *
     * @var int|false Will be `false` if the close bracket is the last token in the file.
     */
    private $afterCloser;

    /**
     * Current PHPCS version being used.
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $phpcsVersion; // @phpstan-ignore property.onlyWritten

    /**
     * Tokens which can open a short array or short list (PHPCS cross-version compatible).
     *
     * @since 1.0.0
     *
     * @var array<int|string, int|string>
     */
    private $openBrackets;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the short array opener token.
     *
     * @return void
     *
     * @throws \PHPCSUtils\Exceptions\TypeError           If the $stackPtr parameter is not an integer.
     * @throws \PHPCSUtils\Exceptions\OutOfBoundsStackPtr If the token passed does not exist in the $phpcsFile.
     * @throws \PHPCSUtils\Exceptions\UnexpectedTokenType If the token passed is not one of the accepted types.
     */
    public function __construct(File $phpcsFile, $stackPtr)
    {
        $tokens       = $phpcsFile->getTokens();
        $openBrackets = StableCollections::$shortArrayListOpenTokensBC;

        if (\is_int($stackPtr) === false) {
            throw TypeError::create(2, '$stackPtr', 'integer', $stackPtr);
        }

        if (isset($tokens[$stackPtr]) === false) {
            throw OutOfBoundsStackPtr::create(2, '$stackPtr', $stackPtr);
        }

        if (isset($openBrackets[$tokens[$stackPtr]['code']]) === false) {
            $acceptedTokens = 'T_OPEN_SHORT_ARRAY or T_OPEN_SQUARE_BRACKET';
            throw UnexpectedTokenType::create(2, '$stackPtr', $acceptedTokens, $tokens[$stackPtr]['type']);
        }

        $this->phpcsFile = $phpcsFile;
        $this->tokens    = $tokens;
        $this->opener    = $stackPtr;

        $this->closer = $stackPtr;
        if (isset($this->tokens[$stackPtr]['bracket_closer'])) {
            $this->closer = $this->tokens[$stackPtr]['bracket_closer'];
        }

        $this->beforeOpener = $this->phpcsFile->findPrevious(Tokens::$emptyTokens, ($this->opener - 1), null, true);
        $this->afterCloser  = $this->phpcsFile->findNext(Tokens::$emptyTokens, ($this->closer + 1), null, true);

        $this->phpcsVersion = Helper::getVersion();
        $this->openBrackets = $openBrackets;
    }

    /**
     * Determine whether the bracket is a short array, short list or real square bracket.
     *
     * @since 1.0.0
     *
     * @return string Either 'short array', 'short list' or 'square brackets'.
     */
    public function solve()
    {
        if ($this->isSquareBracket() === true) {
            return self::SQUARE_BRACKETS;
        }

        if ($this->afterCloser === false) {
            // Live coding. Short array until told differently.
            return self::SHORT_ARRAY;
        }

        // If the bracket closer is followed by an equals sign, it's always a short list.
        if ($this->tokens[$this->afterCloser]['code'] === \T_EQUAL) {
            return self::SHORT_LIST;
        }

        // Attributes can only contain constant expressions, i.e. lists not allowed.
        if (Context::inAttribute($this->phpcsFile, $this->opener) === true) {
            return self::SHORT_ARRAY;
        }

        $type = $this->isInForeach();
        if ($type !== false) {
            return $type;
        }

        /*
         * Check if this can be a nested set of brackets used as a value.
         * That's the only "confusing" syntax left. In all other cases, it will be a short array.
         */
        $hasRiskyTokenBeforeOpener = false;
        if (isset($this->openBrackets[$this->tokens[$this->beforeOpener]['code']]) === true
            || $this->tokens[$this->beforeOpener]['code'] === \T_COMMA
            || $this->tokens[$this->beforeOpener]['code'] === \T_DOUBLE_ARROW
        ) {
            $hasRiskyTokenBeforeOpener = true;
        }

        $hasRiskyTokenAfterCloser = false;
        if ($this->tokens[$this->afterCloser]['code'] === \T_COMMA
            || $this->tokens[$this->afterCloser]['code'] === \T_CLOSE_SHORT_ARRAY
            || $this->tokens[$this->afterCloser]['code'] === \T_CLOSE_SQUARE_BRACKET
        ) {
            $hasRiskyTokenAfterCloser = true;
        }

        if ($hasRiskyTokenBeforeOpener === false || $hasRiskyTokenAfterCloser === false) {
            return self::SHORT_ARRAY;
        }

        /*
         * Check if this is the first/last item in a "parent" set of brackets.
         * If so, skip straight to the parent and determine the type of that, the type
         * of the inner set of brackets will be the same (as all other options have
         * already been eliminated).
         */
        if (isset($this->openBrackets[$this->tokens[$this->beforeOpener]['code']]) === true) {
            return IsShortArrayOrListWithCache::getType($this->phpcsFile, $this->beforeOpener);
        }

        $nextEffectiveAfterCloser = $this->afterCloser;
        if ($this->tokens[$this->afterCloser]['code'] === \T_COMMA) {
            // Skip over potential trailing commas.
            $nextEffectiveAfterCloser = $this->phpcsFile->findNext(
                Tokens::$emptyTokens,
                ($this->afterCloser + 1),
                null,
                true
            );
        }

        if ($this->tokens[$nextEffectiveAfterCloser]['code'] === \T_CLOSE_SHORT_ARRAY
            || $this->tokens[$nextEffectiveAfterCloser]['code'] === \T_CLOSE_SQUARE_BRACKET
        ) {
            return IsShortArrayOrListWithCache::getType($this->phpcsFile, $nextEffectiveAfterCloser);
        }

        /*
         * Okay, so as of here, we know this set of brackets is preceded by a comma or double arrow
         * and followed by a comma. This is the only ambiguous syntax left.
         */

        /*
         * Check if this could be a (nested) short list at all.
         * A list must have at least one variable inside and can not be empty.
         * An array, however, cannot contain empty items.
         */
        $type = $this->walkInside($this->opener);
        if ($type !== false) {
            return $type;
        }

        // Last resort: walk up in the file to see if we can find a set of parent brackets...
        $type = $this->walkOutside();
        if ($type !== false) {
            return $type;
        }

        // If everything failed, this will be a short array (shouldn't be possible).
        return self::SHORT_ARRAY; // @codeCoverageIgnore
    }

    /**
     * Check if the brackets are in actual fact real square brackets.
     *
     * @since 1.0.0
     *
     * @return bool TRUE if these are real square brackets; FALSE otherwise.
     */
    private function isSquareBracket()
    {
        if ($this->opener === $this->closer) {
            // Parse error (unclosed bracket) or live coding. Bow out.
            return true;
        }

        // Check if this is a bracket we need to examine or a mistokenization.
        return ($this->isShortArrayBracket() === false);
    }

    /**
     * Verify that the current set of brackets is not affected by known PHPCS cross-version tokenizer issues.
     *
     * List of previous tokenizer issues which affected the short array/short list tokenization for reference:
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/issues/1284 PHPCS#1284} (PHPCS < 2.8.1)
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/issues/1381 PHPCS#1381} (PHPCS < 2.9.0)
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/issues/1971 PHPCS#1971} (PHPCS 2.8.0 - 3.2.3)
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/pull/3013 PHPCS#3013} (PHPCS < 3.5.6)
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/pull/3172 PHPCS#3172} (PHPCS < 3.6.0)
     * - {@link https://github.com/squizlabs/PHP_CodeSniffer/pull/3632 PHPCS#3632} (PHPCS < 3.7.2)
     *
     * @since 1.0.0
     *
     * @return bool TRUE if this is actually a short array bracket which needs to be examined,
     *              FALSE if it is an (incorrectly tokenized) square bracket.
     */
    private function isShortArrayBracket()
    {
        if ($this->tokens[$this->opener]['code'] === \T_OPEN_SQUARE_BRACKET) {
            // Currently there are no known issues with the tokenization in PHPCS.
            return false;
        }

        return true;
    }

    /**
     * Check is this set of brackets is used within a foreach expression.
     *
     * @since 1.0.0
     *
     * @return string|false The determined type or FALSE if undetermined.
     */
    private function isInForeach()
    {
        $inForeach = Context::inForeachCondition($this->phpcsFile, $this->opener);
        if ($inForeach === false) {
            return false;
        }

        switch ($inForeach) {
            case 'beforeAs':
                if ($this->tokens[$this->afterCloser]['code'] === \T_AS) {
                    return self::SHORT_ARRAY;
                }

                break;

            case 'afterAs':
                if ($this->tokens[$this->afterCloser]['code'] === \T_CLOSE_PARENTHESIS) {
                    $owner = Parentheses::getOwner($this->phpcsFile, $this->afterCloser);
                    if ($owner !== false && $this->tokens[$owner]['code'] === \T_FOREACH) {
                        return self::SHORT_LIST;
                    }
                }

                break;
        }

        /*
         * Everything else will be a nested set of brackets (provided we're talking valid PHP),
         * so disregard as it can not be determined yet.
         */
        return false;
    }

    /**
     * Walk the first part of the contents between the brackets to see if we can determine if this
     * is a short array or short list based on its contents.
     *
     * Short lists can only have another (nested) list or variable assignments, including property assignments
     * and array index assignment, as the value inside the brackets.
     *
     * This won't walk the complete contents as that could be a huge performance drain. Just the first x items.
     *
     * @since 1.0.0
     *
     * @param int $opener     The position of the short array open bracket token.
     * @param int $recursions Optional. Keep track of how often we've recursed into this methd.
     *                        Prevent infinite loops for extremely deeply nested arrays.
     *                        Defaults to 0.
     *
     * @return string|false The determined type or FALSE if undetermined.
     */
    private function walkInside($opener, $recursions = 0)
    {
        // Get the first 5 "parameters" and ignore the "is short array" check.
        $items = PassedParameters::getParameters($this->phpcsFile, $opener, self::ITEM_LIMIT, true);

        if ($items === []) {
            /*
             * A list can not be empty, so this must be an array, however as this is a nested
             * set of brackets, let the outside brackets be the decider as it may be
             * a coding error which a sniff needs to flag.
             */
            return false;
        }

        // Make sure vars assigned by reference are handled correctly.
        $skip   = Tokens::$emptyTokens;
        $skip[] = \T_BITWISE_AND;

        $skipNames = Collections::namespacedNameTokens() + Collections::ooHierarchyKeywords();

        foreach ($items as $item) {
            /*
             * If we encounter a completely empty item, this must be a short list as arrays cannot contain
             * empty items.
             */
            if ($item['clean'] === '') {
                return self::SHORT_LIST;
            }

            /*
             * If the "value" part of the entry doesn't start with a variable, a (nested) short list/array,
             * or a static property assignment, we know for sure that the outside brackets will be an array.
             */
            $arrow = Arrays::getDoubleArrowPtr($this->phpcsFile, $item['start'], $item['end']);
            if ($arrow === false) {
                $firstNonEmptyInValue = $this->phpcsFile->findNext($skip, $item['start'], ($item['end'] + 1), true);
            } else {
                $firstNonEmptyInValue = $this->phpcsFile->findNext($skip, ($arrow + 1), ($item['end'] + 1), true);
            }

            if ($this->tokens[$firstNonEmptyInValue]['code'] !== \T_VARIABLE
                && isset(Collections::namespacedNameTokens()[$this->tokens[$firstNonEmptyInValue]['code']]) === false
                && isset(Collections::ooHierarchyKeywords()[$this->tokens[$firstNonEmptyInValue]['code']]) === false
                && isset($this->openBrackets[$this->tokens[$firstNonEmptyInValue]['code']]) === false
            ) {
                return self::SHORT_ARRAY;
            }

            /*
             * Check if this is a potential list assignment to a static variable.
             * If not, again, we can be sure it will be a short array.
             */
            if (isset(Collections::namespacedNameTokens()[$this->tokens[$firstNonEmptyInValue]['code']]) === true
                || isset(Collections::ooHierarchyKeywords()[$this->tokens[$firstNonEmptyInValue]['code']]) === true
            ) {
                $nextAfter = $this->phpcsFile->findNext($skipNames, ($firstNonEmptyInValue + 1), null, true);

                if ($this->tokens[$nextAfter]['code'] !== \T_DOUBLE_COLON) {
                    return self::SHORT_ARRAY;
                } else {
                    /*
                     * Double colon, so make sure there is a variable after it.
                     * If not, it's constant or function call, i.e. a short array.
                     */
                    $nextNextAfter = $this->phpcsFile->findNext(Tokens::$emptyTokens, ($nextAfter + 1), null, true);
                    if ($this->tokens[$nextNextAfter]['code'] !== \T_VARIABLE) {
                        return self::SHORT_ARRAY;
                    }
                }

                continue;
            }

            if (isset($this->openBrackets[$this->tokens[$firstNonEmptyInValue]['code']]) === true) {
                /*
                 * If the "value" part starts with an open bracket, but has other tokens after it, the current,
                 * outside set of brackets will always be an array (the brackets in the value can still be both,
                 * but that's not the concern of the current determination).
                 */
                $lastNonEmptyInValue = $this->phpcsFile->findPrevious(
                    Tokens::$emptyTokens,
                    $item['end'],
                    $item['start'],
                    true
                );
                if (isset($this->tokens[$firstNonEmptyInValue]['bracket_closer']) === true
                    && $this->tokens[$firstNonEmptyInValue]['bracket_closer'] !== $lastNonEmptyInValue
                ) {
                    return self::SHORT_ARRAY;
                }

                /*
                 * Recursively check the inner set of brackets for contents indicating this is not a short list.
                 */
                if ($recursions < self::RECURSION_LIMIT) {
                    $innerType = $this->walkInside($firstNonEmptyInValue, ($recursions + 1));
                    if ($innerType !== false) {
                        return $innerType;
                    }
                }
            }
        }

        // Undetermined.
        return false;
    }

    /**
     * Walk up in the file to try and find an "outer" set of brackets for an ambiguous, potentially
     * nested set of brackets.
     *
     * This should really be the last resort, if all else fails to determine the type of the brackets.
     *
     * @since 1.0.0
     *
     * @return string|false The determined type or FALSE if undetermined.
     */
    private function walkOutside()
    {
        $stopPoints               = Collections::phpOpenTags();
        $stopPoints[\T_SEMICOLON] = \T_SEMICOLON;

        for ($i = ($this->opener - 1); $i >= 0; $i--) {
            // Skip over block comments (just in case).
            if ($this->tokens[$i]['code'] === \T_DOC_COMMENT_CLOSE_TAG) {
                $i = $this->tokens[$i]['comment_opener'];
                continue;
            }

            if (isset(Tokens::$emptyTokens[$this->tokens[$i]['code']]) === true) {
                continue;
            }

            // Stop on an end of statement.
            if (isset($stopPoints[$this->tokens[$i]['code']]) === true) {
                // End of previous statement or start of document.
                return self::SHORT_ARRAY;
            }

            if (isset($this->tokens[$i]['scope_opener'], $this->tokens[$i]['scope_closer']) === true) {
                if ($i === $this->tokens[$i]['scope_opener']
                    && $this->tokens[$i]['scope_closer'] > $this->closer
                ) {
                    // Found a scope wrapping this set of brackets before finding a outer set of brackets.
                    // This will be a short array.
                    return self::SHORT_ARRAY;
                }

                if ($i === $this->tokens[$i]['scope_closer']
                    && isset($this->tokens[$i]['scope_condition']) === true
                ) {
                    $i = $this->tokens[$i]['scope_condition'];
                    continue;
                }

                // Scope opener without scope condition shouldn't be possible, but just in case.
                // @codeCoverageIgnoreStart
                $i = $this->tokens[$i]['scope_opener'];
                continue;
                // @codeCoverageIgnoreEnd
            }

            if (isset($this->tokens[$i]['parenthesis_opener'], $this->tokens[$i]['parenthesis_closer']) === true) {
                if ($i === $this->tokens[$i]['parenthesis_opener']
                    && $this->tokens[$i]['parenthesis_closer'] > $this->closer
                ) {
                    $beforeParensOpen = $this->phpcsFile->findPrevious(Tokens::$emptyTokens, ($i - 1), null, true);
                    if ($this->tokens[$beforeParensOpen]['code'] === \T_LIST) {
                        // Parse error, mixing long and short list, but that's not our concern.
                        return self::SHORT_LIST;
                    }

                    // Found parentheses wrapping this set of brackets before finding a outer set of brackets.
                    // This will be a short array.
                    return self::SHORT_ARRAY;
                }

                if ($i === $this->tokens[$i]['parenthesis_closer']) {
                    if (isset($this->tokens[$i]['parenthesis_owner']) === true) {
                        $i = $this->tokens[$i]['parenthesis_owner'];
                        continue;
                    }
                }

                // Parenthesis closer without owner (function call and such).
                $i = $this->tokens[$i]['parenthesis_opener'];
                continue;
            }

            /*
             * Skip over attributes.
             * No special handling needed, brackets within attributes won't reach this
             * method as they are already handled within the solve() method.
             */
            if (isset($this->tokens[$i]['attribute_opener'], $this->tokens[$i]['attribute_closer']) === true
                && $i === $this->tokens[$i]['attribute_closer']
            ) {
                $i = $this->tokens[$i]['attribute_opener'];
                continue;
            }

            /*
             * This is a close bracket, but it's not the outer wrapper.
             * As we skip over parentheses and curlies above, we *know* this will be a
             * set of brackets at the same bracket "nesting level" as the set we are examining.
             */
            if (isset($this->tokens[$i]['bracket_opener'], $this->tokens[$i]['bracket_closer']) === true
                && $i === $this->tokens[$i]['bracket_closer']
            ) {
                /*
                 * Now, if the set of brackets follows the same code pattern (comma or double arrow before,
                 * comma after), this will be an adjacent set of potentially nested brackets.
                 * If so, check if the type of the previous set of brackets has already been determined
                 * as adjacent sets of brackets will have the same type.
                 */
                $adjOpener    = $this->tokens[$i]['bracket_opener'];
                $prevNonEmpty = $this->phpcsFile->findPrevious(Tokens::$emptyTokens, ($adjOpener - 1), null, true);
                $nextNonEmpty = $this->phpcsFile->findNext(Tokens::$emptyTokens, ($i + 1), null, true);

                if ($this->tokens[$prevNonEmpty]['code'] === $this->tokens[$this->beforeOpener]['code']
                    && $this->tokens[$nextNonEmpty]['code'] === $this->tokens[$this->afterCloser]['code']
                    && Cache::isCached($this->phpcsFile, IsShortArrayOrListWithCache::CACHE_KEY, $adjOpener) === true
                ) {
                    return Cache::get($this->phpcsFile, IsShortArrayOrListWithCache::CACHE_KEY, $adjOpener);
                }

                // If not, skip over it.
                $i = $this->tokens[$i]['bracket_opener'];
                continue;
            }

            // Open bracket.
            if (isset($this->openBrackets[$this->tokens[$i]['code']]) === true) {
                if (isset($this->tokens[$i]['bracket_closer']) === false
                    || $this->tokens[$i]['code'] === \T_OPEN_SQUARE_BRACKET
                ) {
                    /*
                     * If the type of the unclosed "outer" brackets cannot be determined or
                     * they are identified as plain square brackets, the inner brackets
                     * we are examining should be regarded as a short array.
                     */
                    return self::SHORT_ARRAY;
                }

                if ($this->tokens[$i]['bracket_closer'] > $this->closer) {
                    // This is one we have to examine further as an outer set of brackets.
                    // As all the other checks have already failed to get a result, we know that
                    // whatever the outer set is, the inner set will be the same.
                    return IsShortArrayOrListWithCache::getType($this->phpcsFile, $i);
                }
            }
        }

        // Reached the start of the file without finding an outer set of brackets.
        // Shouldn't be possible, but just in case.
        return false; // @codeCoverageIgnore
    }
}
