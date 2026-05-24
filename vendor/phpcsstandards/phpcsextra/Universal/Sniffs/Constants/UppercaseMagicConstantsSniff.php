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
 * Verifies that PHP native `__...__` magic constants are in uppercase when used.
 *
 * @link https://www.php.net/manual/en/language.constants.predefined.php
 *
 * @since 1.0.0
 */
final class UppercaseMagicConstantsSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Magic constant case';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return Tokens::$magicConstants;
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
        $contentUC = \strtoupper($content);
        if ($contentUC === $content) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'uppercase');
            return;
        }

        $error     = 'Magic constants should be in uppercase. Expected: %s; found: %s';
        $errorCode = '';
        $data      = [
            $contentUC,
            $content,
        ];

        if (\strtolower($content) === $content) {
            $errorCode = 'Lowercase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'lowercase');
        } else {
            $errorCode = 'Mixedcase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'mixed case');
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $errorCode, $data);
        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, $contentUC);
        }
    }
}
