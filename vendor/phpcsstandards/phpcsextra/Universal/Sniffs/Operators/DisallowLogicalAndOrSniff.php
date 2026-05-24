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
 * Enforce the use of the boolean `&&` and `||` operators instead of the logical `and`/`or` operators.
 *
 * Note: as the {@link https://www.php.net/manual/en/language.operators.precedence.php operator precedence}
 * of the logical operators is significantly lower than the operator precedence of boolean operators,
 * this sniff does not contain an auto-fixer.
 *
 * @since 1.0.0
 */
final class DisallowLogicalAndOrSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Type of and/or operator used';

    /**
     * The tokens this sniff records metrics for.
     *
     * @since 1.0.0
     *
     * @var array<int|string, string>
     */
    private $metricType = [
        \T_LOGICAL_AND => 'logical (and/or)',
        \T_LOGICAL_OR  => 'logical (and/or)',
        \T_BOOLEAN_AND => 'boolean (&&/||)',
        \T_BOOLEAN_OR  => 'boolean (&&/||)',
    ];

    /**
     * The tokens this sniff targets with error code and replacements.
     *
     * @since 1.0.0
     *
     * @var array<int|string, array<string, string>>
     */
    private $targetTokenInfo = [
        \T_LOGICAL_AND => [
            'error_code'  => 'LogicalAnd',
            'replacement' => '&&',
        ],
        \T_LOGICAL_OR  => [
            'error_code'  => 'LogicalOr',
            'replacement' => '||',
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
            // Already using boolean operator.
            return;
        }

        $error = 'Using logical operators is not allowed. Expected: "%s"; Found: "%s"';
        $data  = [
            $this->targetTokenInfo[$tokenCode]['replacement'],
            $tokens[ $stackPtr ]['content'],
        ];

        $phpcsFile->addError($error, $stackPtr, $this->targetTokenInfo[$tokenCode]['error_code'], $data);
    }
}
