<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\AttributeBlock;

/**
 * Require a trailing comma for multi-line, multi-attribute attribute blocks and forbid trailing commas
 * in single-line attribute blocks and multi-line attributes containing only a single attribute.
 *
 * @since 1.5.0
 */
final class TrailingCommaSniff implements Sniff
{

    /**
     * Name of the metric for single-line attribute blocks.
     *
     * @since 1.5.0
     *
     * @var string
     */
    const METRIC_NAME_SINGLE_LINE = 'Trailing comma in single-line attribute block';

    /**
     * Name of the metric for multi-line attribute blocks.
     *
     * @since 1.5.0
     *
     * @var string
     */
    const METRIC_NAME_MULTI_LINE = 'Trailing comma in multi-line attribute block';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.5.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ATTRIBUTE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.5.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['attribute_closer']) === false) {
            // Live coding/parse error. Ignore.
            return;
        }

        $opener = $stackPtr;
        $closer = $tokens[$stackPtr]['attribute_closer'];

        $beforeCloser = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($closer - 1), null, true);
        if ($opener === $beforeCloser) {
            // Empty attribute block. Ignore.
            return;
        }

        if ($tokens[$opener]['line'] === $tokens[$closer]['line']) {
            // Single-line attribute block.
            if ($tokens[$beforeCloser]['code'] === \T_COMMA) {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_SINGLE_LINE, 'yes');

                $fix = $phpcsFile->addFixableError(
                    'Trailing comma is not allowed in a single-line attribute block',
                    $beforeCloser,
                    'ForbiddenSingleLine'
                );
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken($beforeCloser, '');
                }
            } else {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_SINGLE_LINE, 'no');
            }

            return;
        }

        // Multi-line attribute block, check whether it contains a single attribute or multiple.
        $attributeCount = AttributeBlock::countAttributes($phpcsFile, $stackPtr);
        if ($attributeCount > 1) {
            // Multiple attributes in a multi-line attribute block, require trailing comma.
            if ($tokens[$beforeCloser]['code'] !== \T_COMMA) {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_MULTI_LINE, 'No, multi-attribute');

                $fix = $phpcsFile->addFixableError(
                    'Trailing comma required after last attribute in a multi-line, multi-attribute attribute block',
                    $beforeCloser,
                    'RequiredMultiAttributeMultiLine'
                );
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($beforeCloser, ',');
                }
            } else {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_MULTI_LINE, 'Yes, multi-attribute');
            }

            return;
        }

        // Single attribute in a multi-line attribute block, forbid trailing comma.
        if ($tokens[$beforeCloser]['code'] === \T_COMMA) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_MULTI_LINE, 'Yes, single attribute');

            $fix = $phpcsFile->addFixableError(
                'Trailing comma is not allowed when a multi-line attribute block only contains a single attribute',
                $beforeCloser,
                'ForbiddenSingleAttributeMultiLine'
            );
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($beforeCloser, '');
            }
        } else {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_MULTI_LINE, 'No, single attribute');
        }
    }
}
