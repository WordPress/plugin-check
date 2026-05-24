<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\UseStatements;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\UseStatements;

/**
 * Verifies that names being imported in import use statements do not start with a leading backslash.
 *
 * This sniff handles all types of import use statements supported by PHP, in contrast to any
 * of the other sniffs for the same in, for instance, the PSR12 or the Slevomat standard,
 * all of which are incomplete.
 *
 * @since 1.0.0
 */
final class NoLeadingBackslashSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Import use with leading backslash';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_USE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (UseStatements::isImportUse($phpcsFile, $stackPtr) === false) {
            // Trait or closure use statement.
            return;
        }

        $endOfStatement = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG, \T_OPEN_USE_GROUP], ($stackPtr + 1));
        if ($endOfStatement === false) {
            // Live coding or parse error.
            return;
        }

        $tokens  = $phpcsFile->getTokens();
        $current = $stackPtr;

        do {
            $continue = $this->processImport($phpcsFile, $current, $endOfStatement);
            if ($continue === false) {
                break;
            }

            // Move the stackPtr forward to the next part of the use statement, if any.
            $current = $phpcsFile->findNext(\T_COMMA, ($current + 1), $endOfStatement);
        } while ($current !== false);

        if ($tokens[$endOfStatement]['code'] !== \T_OPEN_USE_GROUP) {
            // Finished the statement.
            return;
        }

        $current        = $endOfStatement; // Group open brace.
        $endOfStatement = $phpcsFile->findNext([\T_CLOSE_USE_GROUP], ($endOfStatement + 1));
        if ($endOfStatement === false) {
            // Live coding or parse error.
            return;
        }

        do {
            $continue = $this->processImport($phpcsFile, $current, $endOfStatement, true);
            if ($continue === false) {
                break;
            }

            // Move the stackPtr forward to the next part of the use statement, if any.
            $current = $phpcsFile->findNext(\T_COMMA, ($current + 1), $endOfStatement);
        } while ($current !== false);
    }

    /**
     * Examine an individual import statement.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile      The file being scanned.
     * @param int                         $stackPtr       The position of the current token.
     * @param int                         $endOfStatement End token for the current import statement.
     * @param bool                        $groupUse       Whether the current statement is a partial one
     *                                                    within a group use statement.
     *
     * @return bool Whether or not to continue examining this import use statement.
     */
    private function processImport(File $phpcsFile, $stackPtr, $endOfStatement, $groupUse = false)
    {
        $tokens = $phpcsFile->getTokens();

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), $endOfStatement, true);
        if ($nextNonEmpty === false) {
            // Reached the end of the statement.
            return false;
        }

        // Skip past 'function'/'const' keyword.
        $contentLC = \strtolower($tokens[$nextNonEmpty]['content']);
        if ($tokens[$nextNonEmpty]['code'] === \T_STRING
            && ($contentLC === 'function' || $contentLC === 'const')
        ) {
            $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($nextNonEmpty + 1), $endOfStatement, true);
            if ($nextNonEmpty === false) {
                // Reached the end of the statement.
                return false;
            }
        }

        if ($tokens[$nextNonEmpty]['code'] === \T_NS_SEPARATOR
            || $tokens[$nextNonEmpty]['code'] === \T_NAME_FULLY_QUALIFIED
        ) {
            $phpcsFile->recordMetric($nextNonEmpty, self::METRIC_NAME, 'yes');

            $error = 'An import use statement should never start with a leading backslash';
            $code  = 'LeadingBackslashFound';

            if ($groupUse === true) {
                $error = 'Parse error: partial import use statement in a use group starting with a leading backslash';
                $code  = 'LeadingBackslashFoundInGroup';
            }

            $fix = $phpcsFile->addFixableError($error, $nextNonEmpty, $code);

            if ($fix === true) {
                if ($tokens[$nextNonEmpty]['code'] === \T_NS_SEPARATOR) {
                    // PHPCS 3.x.
                    if ($tokens[$nextNonEmpty - 1]['code'] !== \T_WHITESPACE) {
                        $phpcsFile->fixer->replaceToken($nextNonEmpty, ' ');
                    } else {
                        $phpcsFile->fixer->replaceToken($nextNonEmpty, '');
                    }
                } else {
                    // PHPCS 4.x / T_NAME_FULLY_QUALIFIED.
                    $phpcsFile->fixer->replaceToken($nextNonEmpty, \ltrim($tokens[$nextNonEmpty]['content'], '\\'));
                }
            }
        } else {
            $phpcsFile->recordMetric($nextNonEmpty, self::METRIC_NAME, 'no');
        }

        return true;
    }
}
