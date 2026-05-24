<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2023 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\BCFile;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\Parentheses;

/**
 * Detects double negation in code, which is effectively the same as a boolean cast,
 * but with a much higher cognitive load.
 *
 * The sniff will only autofix if the precedence change from boolean not to boolean cast
 * will not cause a behavioural change (as it would with instanceof).
 *
 * @since 1.2.0
 */
final class NoDoubleNegativeSniff implements Sniff
{

    /**
     * Operators with lower precedence than the not-operator.
     *
     * Used to determine when to stop searching for `instanceof`.
     *
     * @since 1.2.0
     *
     * @var array<int|string, int|string>
     */
    private $operatorsWithLowerPrecedence;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.2.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        // Collect all the operators only once.
        $this->operatorsWithLowerPrecedence  = Tokens::$assignmentTokens;
        $this->operatorsWithLowerPrecedence += Tokens::$booleanOperators;
        $this->operatorsWithLowerPrecedence += Tokens::$comparisonTokens;
        $this->operatorsWithLowerPrecedence += Tokens::$operators;
        $this->operatorsWithLowerPrecedence += Collections::ternaryOperators();

        return [\T_BOOLEAN_NOT];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $notCount = 1;
        $lastNot  = $stackPtr;
        for ($afterNot = ($stackPtr + 1); $afterNot < $phpcsFile->numTokens; $afterNot++) {
            if (isset(Tokens::$emptyTokens[$tokens[$afterNot]['code']])) {
                continue;
            }

            if ($tokens[$afterNot]['code'] === \T_BOOLEAN_NOT) {
                $lastNot = $afterNot;
                ++$notCount;
                continue;
            }

            break;
        }

        if ($notCount === 1) {
            // Singular unary not-operator. Nothing to do.
            return;
        }

        $found = \trim(GetTokensAsString::compact($phpcsFile, $stackPtr, $lastNot));
        $data  = [$found];

        if (($notCount % 2) === 1) {
            /*
             * Oh dear... silly code time, found a triple negative (or other uneven number),
             * this should just be a singular not-operator.
             */
            $fix = $phpcsFile->addFixableError(
                'Triple negative (or more) detected. Use a singular not (!) operator instead. Found: %s',
                $stackPtr,
                'FoundTriple',
                $data
            );

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                $this->removeNotAndTrailingSpaces($phpcsFile, $stackPtr, $lastNot);

                $phpcsFile->fixer->endChangeset();
            }

            // Only throw one error, even if there are more than two not-operators.
            return $lastNot;
        }

        /*
         * Found a double negative, which should be a boolean cast.
         */

        $fixable = true;

        /*
         * If whatever is being "cast" is within parentheses, we're good.
         * If not, we need to prevent creating a change in behaviour
         * when what follows is an `$x instanceof ...` expression, as
         * the "instanceof" operator is right between a boolean cast
         * and the ! operator precedence-wise.
         *
         * Note: this only applies to double negative, not triple negative.
         *
         * @link https://www.php.net/language.operators.precedence
         */
        if ($tokens[$afterNot]['code'] !== \T_OPEN_PARENTHESIS) {
            $end = Parentheses::getLastCloser($phpcsFile, $stackPtr);
            if ($end === false) {
                $end = BCFile::findEndOfStatement($phpcsFile, $stackPtr);
            }

            for ($nextRelevant = $afterNot; $nextRelevant < $end; $nextRelevant++) {
                if (isset(Tokens::$emptyTokens[$tokens[$nextRelevant]['code']])) {
                    continue;
                }

                if ($tokens[$nextRelevant]['code'] === \T_INSTANCEOF) {
                    $fixable = false;
                    break;
                }

                if (isset($this->operatorsWithLowerPrecedence[$tokens[$nextRelevant]['code']])) {
                    // The expression the `!` belongs to has ended.
                    break;
                }

                // Skip over anything within some form of brackets.
                if (isset($tokens[$nextRelevant]['scope_closer'])
                    && ($nextRelevant === $tokens[$nextRelevant]['scope_opener']
                    || $nextRelevant === $tokens[$nextRelevant]['scope_condition'])
                ) {
                    $nextRelevant = $tokens[$nextRelevant]['scope_closer'];
                    continue;
                }

                if (isset($tokens[$nextRelevant]['bracket_opener'], $tokens[$nextRelevant]['bracket_closer'])
                    && $nextRelevant === $tokens[$nextRelevant]['bracket_opener']
                ) {
                    $nextRelevant = $tokens[$nextRelevant]['bracket_closer'];
                    continue;
                }

                if ($tokens[$nextRelevant]['code'] === \T_OPEN_PARENTHESIS
                    && isset($tokens[$nextRelevant]['parenthesis_closer'])
                ) {
                    $nextRelevant = $tokens[$nextRelevant]['parenthesis_closer'];
                    continue;
                }

                // Skip over attributes (just in case).
                if ($tokens[$nextRelevant]['code'] === \T_ATTRIBUTE
                    && isset($tokens[$nextRelevant]['attribute_closer'])
                ) {
                    $nextRelevant = $tokens[$nextRelevant]['attribute_closer'];
                    continue;
                }
            }
        }

        $error = 'Double negative detected. Use a (bool) cast %s instead. Found: %s';
        $code  = 'FoundDouble';
        $data  = [
            '',
            $found,
        ];

        if ($fixable === false) {
            $code    = 'FoundDoubleWithInstanceof';
            $data[0] = 'and parentheses around the instanceof expression';

            // Don't auto-fix in combination with instanceof.
            $phpcsFile->addError($error, $stackPtr, $code, $data);

            // Only throw one error, even if there are more than two not-operators.
            return $lastNot;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $code, $data);

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            $this->removeNotAndTrailingSpaces($phpcsFile, $stackPtr, $lastNot);

            $phpcsFile->fixer->replaceToken($lastNot, '(bool)');

            $phpcsFile->fixer->endChangeset();
        }

        // Only throw one error, even if there are more than two not-operators.
        return $lastNot;
    }

    /**
     * Remove boolean not-operators and trailing whitespace after those,
     * but don't remove comments or trailing whitespace after comments.
     *
     * @since 1.2.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     * @param int                         $lastNot   The position of the last boolean not token
     *                                               in the chain.
     *
     * @return void
     */
    private function removeNotAndTrailingSpaces(File $phpcsFile, $stackPtr, $lastNot)
    {
        $tokens = $phpcsFile->getTokens();
        $ignore = false;

        for ($i = $stackPtr; $i < $lastNot; $i++) {
            if (isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                // Ignore comments and whitespace after comments.
                $ignore = true;
                continue;
            }

            if ($tokens[$i]['code'] === \T_WHITESPACE && $ignore === false) {
                $phpcsFile->fixer->replaceToken($i, '');
                continue;
            }

            if ($tokens[$i]['code'] === \T_BOOLEAN_NOT) {
                $ignore = false;
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }
}
