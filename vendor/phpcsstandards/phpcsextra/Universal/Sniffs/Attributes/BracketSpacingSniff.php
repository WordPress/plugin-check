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
use PHPCSUtils\Fixers\SpacesFixer;

/**
 * Requires a configurable number of spaces on the inside of attribute block brackets.
 *
 * When newlines are allowed, will also safeguard against blank lines at the start/end of the attribute block.
 *
 * @since 1.5.0
 */
final class BracketSpacingSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.5.0
     *
     * @var string
     */
    const METRIC_NAME = 'Spaces on the inside of attribute brackets';

    /**
     * The amount of spacing to demand on the inside of attribute brackets.
     *
     * @since 1.5.0
     *
     * @var int
     */
    public $spacing = 0;

    /**
     * Allow newlines instead of spaces.
     *
     * @since 1.5.0
     *
     * @var bool
     */
    public $ignoreNewlines = false;

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

        if ($tokens[$stackPtr]['attribute_closer'] === ($stackPtr + 1)) {
            // Empty attribute block. Ignore.
            return;
        }

        $this->spacing = (int) $this->spacing;

        $this->processOpener($phpcsFile, $stackPtr);
        $this->processCloser($phpcsFile, $tokens[$stackPtr]['attribute_closer']);
    }

    /**
     * Processes the attribute block opener bracket.
     *
     * @since 1.5.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the attribute block opener
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function processOpener(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($this->ignoreNewlines === true
            && $tokens[$stackPtr]['line'] !== $tokens[$nextNonWhitespace]['line']
        ) {
            if (($tokens[$stackPtr]['line'] + 1) === $tokens[$nextNonWhitespace]['line']) {
                // Single new line.
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'a new line');
                return;
            }

            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'multiple new lines');

            $error = 'Blank line(s) found at the start of an attribute block';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineAtStart');

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($stackPtr);

                // Remove all blank lines, but don't remove the indentation of the line containing the next bit of code.
                for ($i = ($stackPtr + 1); $i < $nextNonWhitespace; $i++) {
                    if ($tokens[$i]['line'] === $tokens[$nextNonWhitespace]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonWhitespace,
            $this->spacing,
            'Expected %s after the attribute block opener. Found: %s.',
            'SpaceAfterOpener',
            'error',
            0,
            self::METRIC_NAME
        );
    }

    /**
     * Processes the attribute block closer bracket.
     *
     * @since 1.5.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the attribute block closer
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function processCloser(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $previousNonWhitespace = $phpcsFile->findPrevious(\T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($this->ignoreNewlines === true
            && $tokens[$stackPtr]['line'] !== $tokens[$previousNonWhitespace]['line']
        ) {
            if (($tokens[$stackPtr]['line'] - 1) === $tokens[$previousNonWhitespace]['line']) {
                // Single new line.
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'a new line');
                return;
            }

            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'multiple new lines');

            $error = 'Blank line(s) found at the end of an attribute block';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineAtEnd');

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($previousNonWhitespace);

                // Remove all blank lines, but don't remove the indentation of the line containing the next bit of code.
                for ($i = ($previousNonWhitespace + 1); $i < $stackPtr; $i++) {
                    if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $previousNonWhitespace,
            $stackPtr,
            $this->spacing,
            'Expected %s before the attribute block closer. Found: %s.',
            'SpaceBeforeCloser',
            'error',
            0,
            self::METRIC_NAME
        );
    }
}
