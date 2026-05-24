<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Constants;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that the "::class" keyword when used for class name resolution is in lowercase.
 *
 * @link https://www.php.net/manual/en/language.constants.predefined.php
 * @link https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.class
 *
 * @since 1.0.0
 */
final class LowercaseClassResolutionKeywordSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Magic ::class constant case';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_STRING];
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
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $content   = $tokens[$stackPtr]['content'];
        $contentLC = \strtolower($content);

        if ($contentLC !== 'class') {
            return;
        }

        $nextToken = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextToken !== false && $tokens[$nextToken]['code'] === \T_OPEN_PARENTHESIS) {
            // Function call or declaration for a function called "class".
            return;
        }

        $prevToken = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevToken === false || $tokens[$prevToken]['code'] !== \T_DOUBLE_COLON) {
            return;
        }

        if ($contentLC === $content) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'lowercase');
            return;
        }

        $error = "The ::class keyword for class name resolution must be in lowercase. Expected: '::%s'; found: '::%s'";
        $data  = [
            $contentLC,
            $content,
        ];

        $errorCode = '';
        if (\strtoupper($content) === $content) {
            $errorCode = 'Uppercase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'uppercase');
        } else {
            $errorCode = 'Mixedcase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'mixed case');
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $errorCode, $data);
        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, $contentLC);
        }
    }
}
