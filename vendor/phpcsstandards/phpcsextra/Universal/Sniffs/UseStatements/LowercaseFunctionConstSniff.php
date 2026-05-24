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
 * Verify that the `function` and `const` keyword in import `use` statements are lowercase.
 *
 * Companion sniff to the upstream `Generic.PHP.LowerCaseKeyword` sniff which doesn't cover
 * these keywords as they are not tokenized as `T_FUNCTION`/`T_CONST`, but as `T_STRING`.
 *
 * @since 1.0.0
 */
final class LowercaseFunctionConstSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Import use statement %s keyword case';

    /**
     * A list of keywords that can follow use statements.
     *
     * @since 1.0.0
     *
     * @var array<string, true>
     */
    protected $keywords = [
        'const'    => true,
        'function' => true,
    ];

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

        $tokens       = $phpcsFile->getTokens();
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false) {
            // Live coding or parse error.
            return;
        }

        if (isset($this->keywords[\strtolower($tokens[$nextNonEmpty]['content'])]) === true) {
            // Keyword found at start of statement, applies to whole statement.
            $this->processKeyword($phpcsFile, $nextNonEmpty, $tokens[$nextNonEmpty]['content']);
            return;
        }

        // This may still be a group use statement with function/const substatements.
        $openGroup = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG, \T_OPEN_USE_GROUP], ($stackPtr + 1));
        if ($openGroup === false || $tokens[$openGroup]['code'] !== \T_OPEN_USE_GROUP) {
            // Not a group use statement.
            return;
        }

        $closeGroup = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG, \T_CLOSE_USE_GROUP], ($openGroup + 1));
        if ($closeGroup === false || $tokens[$closeGroup]['code'] !== \T_CLOSE_USE_GROUP) {
            // Live coding or parse error.
            return;
        }

        $current = $openGroup;
        do {
            $current = $phpcsFile->findNext(Tokens::$emptyTokens, ($current + 1), $closeGroup, true);
            if ($current === false) {
                return;
            }

            if (isset($this->keywords[\strtolower($tokens[$current]['content'])]) === true) {
                $this->processKeyword($phpcsFile, $current, $tokens[$current]['content']);
            }

            // We're within the use group, so find the next comma.
            $current = $phpcsFile->findNext(\T_COMMA, ($current + 1), $closeGroup);
        } while ($current !== false);
    }

    /**
     * Processes a found keyword.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the keyword in the token stack.
     * @param string                      $content   The keyword as found.
     *
     * @return void
     */
    public function processKeyword(File $phpcsFile, $stackPtr, $content)
    {
        $contentLC  = \strtolower($content);
        $metricName = \sprintf(self::METRIC_NAME, $contentLC);
        if ($contentLC === $content) {
            // Already lowercase. Bow out.
            $phpcsFile->recordMetric($stackPtr, $metricName, 'lowercase');
            return;
        }

        if (\strtoupper($content) === $content) {
            $phpcsFile->recordMetric($stackPtr, $metricName, 'uppercase');
        } else {
            $phpcsFile->recordMetric($stackPtr, $metricName, 'mixed case');
        }

        $error = 'The "%s" keyword when used in an import use statements must be lowercase.';
        $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NotLowercase', [$contentLC]);

        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, $contentLC);
        }
    }
}
