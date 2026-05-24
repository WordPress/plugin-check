<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2023 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Enforces that the concatenation operator in multi-line concatenations is in a preferred position,
 * either always at the start of the next line or always at the end of the previous line.
 *
 * Note: this sniff has no opinion on spacing before/after the concatenation operator.
 * It will normalize based on the "one space before/after" PSR-12 industry standard.
 * If different spacing is preferred, use the `Squiz.Strings.ConcatenationSpacing` to enforce/correct that.
 *
 * @since 1.2.0
 */
final class ConcatPositionSniff implements Sniff
{

    /**
     * The phrase to use for the metric recorded by this sniff.
     *
     * @since 1.2.0
     *
     * @var string
     */
    const METRIC_NAME = 'Multi-line concatenation operator position';

    /**
     * Position indication: start of next line.
     *
     * @since 1.2.0
     *
     * @var string
     */
    const POSITION_START = 'start';

    /**
     * Position indication: end of previous line.
     *
     * @since 1.2.0
     *
     * @var string
     */
    const POSITION_END = 'end';

    /**
     * Position indication: neither start of next line nor end of previous line.
     *
     * @since 1.2.0
     *
     * @var string
     */
    const POSITION_STANDALONE = 'stand-alone';

    /**
     * Preferred position for the concatenation operator.
     *
     * Valid values are: 'start' and 'end'.
     * Defaults to 'start'.
     *
     * @since 1.2.0
     *
     * @var string
     */
    public $allowOnly = self::POSITION_START;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.2.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_STRING_CONCAT];
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
        /*
         * Validate the setting.
         */
        if ($this->allowOnly !== self::POSITION_END) {
            // Use the default.
            $this->allowOnly = self::POSITION_START;
        }

        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        if ($nextNonEmpty === false) {
            // Parse error/live coding.
            return;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$prevNonEmpty]['line'] === $tokens[$nextNonEmpty]['line']) {
            // Not multi-line concatenation. Not our target.
            return;
        }

        $position = self::POSITION_STANDALONE;
        if ($tokens[$prevNonEmpty]['line'] === $tokens[$stackPtr]['line']) {
            $position = self::POSITION_END;
        } elseif ($tokens[$nextNonEmpty]['line'] === $tokens[$stackPtr]['line']) {
            $position = self::POSITION_START;
        }

        // Record metric.
        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, $position);

        if ($this->allowOnly === $position) {
            // All okay.
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'The concatenation operator for multi-line concatenations should always be at the %s of a line.',
            $stackPtr,
            'Incorrect',
            [$this->allowOnly]
        );

        if ($fix === true) {
            if ($this->allowOnly === self::POSITION_END) {
                $phpcsFile->fixer->beginChangeset();

                // Move the concat operator.
                $phpcsFile->fixer->replaceToken($stackPtr, '');
                $phpcsFile->fixer->addContent($prevNonEmpty, ' .');

                if ($position === self::POSITION_START
                    && $tokens[($stackPtr + 1)]['code'] === \T_WHITESPACE
                ) {
                    // Remove trailing space.
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
                } elseif ($position === self::POSITION_STANDALONE) {
                    // Remove potential indentation space.
                    if ($tokens[($stackPtr - 1)]['code'] === \T_WHITESPACE) {
                        $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
                    }

                    // Remove new line.
                    if ($tokens[($stackPtr + 1)]['code'] === \T_WHITESPACE) {
                        $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
                    }
                }

                $phpcsFile->fixer->endChangeset();
                return;
            }

            // Fixer for allowOnly === self::POSITION_START.
            $phpcsFile->fixer->beginChangeset();

            // Move the concat operator.
            $phpcsFile->fixer->replaceToken($stackPtr, '');
            $phpcsFile->fixer->addContentBefore($nextNonEmpty, '. ');

            if ($position === self::POSITION_END
                && $tokens[($stackPtr - 1)]['code'] === \T_WHITESPACE
            ) {
                // Remove trailing space.
                $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
            } elseif ($position === self::POSITION_STANDALONE) {
                // Remove potential indentation space.
                if ($tokens[($stackPtr - 1)]['code'] === \T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
                }

                // Remove new line.
                if ($tokens[($stackPtr + 1)]['code'] === \T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
                }
            }

            $phpcsFile->fixer->endChangeset();
        }
    }
}
