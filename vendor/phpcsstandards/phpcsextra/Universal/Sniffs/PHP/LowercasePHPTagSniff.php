<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforce that the "PHP" in a PHP open tag is lowercase.
 *
 * @since 1.2.0
 */
final class LowercasePHPTagSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.2.0
     *
     * @var string
     */
    const METRIC_NAME = 'PHP open tag case';

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @since 1.2.0
     *
     * @return array<int>
     */
    public function register()
    {
        return [\T_OPEN_TAG];
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
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $content   = $tokens[$stackPtr]['content'];
        $contentLC = \strtolower($content);

        if ($contentLC === $content) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'lowercase');
            return;
        }

        $errorCode = '';
        if (\strtoupper($content) === $content) {
            $errorCode = 'Uppercase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'uppercase');
        } else {
            $errorCode = 'Mixedcase';
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'mixed case');
        }

        $fix = $phpcsFile->addFixableError(
            'The php open tag should be in lowercase. Found: %s',
            $stackPtr,
            $errorCode,
            [\trim($content)]
        );

        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, $contentLC);
        }
    }
}
