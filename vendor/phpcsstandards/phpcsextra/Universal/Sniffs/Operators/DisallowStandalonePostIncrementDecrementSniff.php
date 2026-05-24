<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\BCFile;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Disallow the use of post-in/decrements in stand-alone statements and discourage the use of
 * multiple increment/decrement operators in a stand-alone statement.
 *
 * Post-in/decrement returns the value and in/decrements afterwards.
 * Pre-in/decrement in/decrements the value and returns afterwards.
 * Using pre-in/decrement is more in line with the principle of least astonishment
 * and prevents bugs when code gets moved around at a later point in time.
 *
 * @since 1.0.0
 */
final class DisallowStandalonePostIncrementDecrementSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'In/decrement usage in stand-alone statements';

    /**
     * Tokens which can be expected in a stand-alone in/decrement statement.
     *
     * {@internal This array is enriched in the register() method.}
     *
     * @since 1.0.0
     *
     * @var array<int|string>
     */
    private $allowedTokens = [
        \T_VARIABLE => \T_VARIABLE,
    ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        $this->allowedTokens += Collections::ooHierarchyKeywords();
        $this->allowedTokens += Collections::objectOperators();
        $this->allowedTokens += Collections::namespacedNameTokens();

        /*
         * Remove nullsafe object operator. In/decrement not allowed in write context,
         * so ignore.
         */
        unset($this->allowedTokens[\T_NULLSAFE_OBJECT_OPERATOR]);

        return Collections::incrementDecrementOperators();
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
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

        if (empty($tokens[$stackPtr]['nested_parenthesis']) === false) {
            // Not a stand-alone statement.
            return;
        }

        $start = BCFile::findStartOfStatement($phpcsFile, $stackPtr);
        $end   = BCFile::findEndOfStatement($phpcsFile, $stackPtr);

        if (isset(Collections::incrementDecrementOperators()[$tokens[$end]['code']])) {
            // Statement ends on a PHP close tag, set the end pointer to the close tag.
            $end = $phpcsFile->findNext(Tokens::$emptyTokens, ($end + 1), null, true);
        }

        if ($tokens[$end]['code'] !== \T_SEMICOLON
            && $tokens[$end]['code'] !== \T_CLOSE_TAG
        ) {
            // Not a stand-alone statement.
            return $end;
        }

        $counter   = 0;
        $lastCode  = null;
        $operators = Collections::incrementDecrementOperators();
        for ($i = $start; $i < $end; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === true) {
                continue;
            }

            if (isset($operators[$tokens[$i]['code']]) === true) {
                $lastCode = $tokens[$i]['code'];
                ++$counter;
                continue;
            }

            if (isset($this->allowedTokens[$tokens[$i]['code']]) === true) {
                $lastCode = $tokens[$i]['code'];
                continue;
            }

            if ($tokens[$i]['code'] === \T_OPEN_SQUARE_BRACKET
                && isset($tokens[$i]['bracket_closer'])
                && ($lastCode === \T_VARIABLE || $lastCode === \T_STRING)
            ) {
                // Array access.
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }

            // Came across an unexpected token. This is (probably) not a stand-alone statement.
            return $end;
        }

        if ($counter > 1) {
            $phpcsFile->addWarning(
                'Using multiple increment/decrement operators in a stand-alone statement is strongly discouraged.'
                    . ' Found: %s',
                $stackPtr,
                'MultipleOperatorsFound',
                [GetTokensAsString::compact($phpcsFile, $start, ($end - 1), true)]
            );

            return $end;
        }

        $type = 'increment';
        if ($tokens[$stackPtr]['code'] === \T_DEC) {
            $type = 'decrement';
        }

        $lastNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($end - 1), $start, true);
        if ($start === $stackPtr && $lastNonEmpty !== $stackPtr) {
            // This is already pre-in/decrement.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'pre-' . $type);
            return $end;
        }

        if ($lastNonEmpty === false || $lastNonEmpty === $start || $lastNonEmpty !== $stackPtr) {
            // Parse error or otherwise unsupported syntax. Ignore.
            return $end;
        }

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'post-' . $type);

        $error        = 'Stand-alone post-%1$s statement found. Use pre-%1$s instead: %2$s.';
        $errorCode    = 'Post' . \ucfirst($type) . 'Found';
        $replacement  = $tokens[$stackPtr]['content'];
        $replacement .= GetTokensAsString::compact($phpcsFile, $start, ($lastNonEmpty - 1), true);
        $data         = [
            $type,
            $replacement,
        ];

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $errorCode, $data);

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr, '');
            $phpcsFile->fixer->addContentBefore($start, $tokens[$stackPtr]['content']);
            $phpcsFile->fixer->endChangeset();
        }

        return $end;
    }
}
