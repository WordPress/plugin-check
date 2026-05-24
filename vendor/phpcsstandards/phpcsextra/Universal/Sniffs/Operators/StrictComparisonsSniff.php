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

/**
 * Enforce the use of strict comparisons.
 *
 * Warning: the auto-fixer for this sniff _may_ cause bugs in applications and should be used with care!
 * This is considered a _risky_ fix.
 *
 * @since 1.0.0
 */
final class StrictComparisonsSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Type of comparison used';

    /**
     * The tokens this sniff records metrics for.
     *
     * @since 1.0.0
     *
     * @var array<int|string, string>
     */
    private $metricType = [
        \T_IS_EQUAL         => 'loose',
        \T_IS_NOT_EQUAL     => 'loose',
        \T_IS_IDENTICAL     => 'strict',
        \T_IS_NOT_IDENTICAL => 'strict',
    ];

    /**
     * The tokens this sniff targets with error code and replacements.
     *
     * @since 1.0.0
     *
     * @var array<int|string, array<string, string>>
     */
    private $targetTokenInfo = [
        \T_IS_EQUAL     => [
            'error_code'  => 'LooseEqual',
            'replacement' => '===',
        ],
        \T_IS_NOT_EQUAL => [
            'error_code'  => 'LooseNotEqual',
            'replacement' => '!==',
        ],
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
        return \array_keys($this->metricType);
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
        $tokenCode = $tokens[$stackPtr]['code'];

        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, $this->metricType[$tokenCode]);

        if (isset($this->targetTokenInfo[$tokenCode]) === false) {
            // Already using strict comparison operator.
            return;
        }

        $error = 'Loose comparisons are not allowed. Expected: "%s"; Found: "%s"';
        $data  = [
            $this->targetTokenInfo[$tokenCode]['replacement'],
            $tokens[ $stackPtr ]['content'],
        ];

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $this->targetTokenInfo[$tokenCode]['error_code'], $data);
        if ($fix === false) {
            return;
        }

        $phpcsFile->fixer->replaceToken($stackPtr, $this->targetTokenInfo[$tokenCode]['replacement']);
    }
}
