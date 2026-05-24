<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\FunctionDeclarations;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Forbids long closures.
 *
 * @since 1.1.0
 */
final class NoLongClosuresSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_CODE = 'Closure length (code only)';

    /**
     * Name of the metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_COMMENTS = 'Closure length (code + comments)';

    /**
     * Name of the metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_ALL = 'Closure length (code + comments + blank lines)';

    /**
     * Maximum number of lines allowed before a closure is considered a "long" closure.
     *
     * Defaults to 5 lines, i.e. when a closure contains 6 lines, a warning will be thrown.
     *
     * @since 1.1.0
     *
     * @var int
     */
    public $recommendedLines = 5;

    /**
     * Maximum number of lines allowed before a closure is considered a "long" closure.
     *
     * Defaults to 8 lines, i.e. when a closure contains 9 lines, an error will be thrown.
     *
     * @since 1.1.0
     *
     * @var int
     */
    public $maxLines = 8;

    /**
     * Whether or not to exclude lines which only contain documentation in the line count.
     *
     * Defaults to `true`.
     *
     * @since 1.1.0
     *
     * @var bool
     */
    public $ignoreCommentLines = true;

    /**
     * Whether or not to exclude empty lines from the line count.
     *
     * Defaults to `true`.
     *
     * @since 1.1.0
     *
     * @var bool
     */
    public $ignoreEmptyLines = true;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_CLOSURE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $this->recommendedLines = (int) $this->recommendedLines;
        $this->maxLines         = (int) $this->maxLines;

        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            // Live coding/parse error. Shouldn't be possible as in that case tokenizer won't retokenize to T_CLOSURE.
            return; // @codeCoverageIgnore
        }

        $opener = $tokens[$stackPtr]['scope_opener'];
        $closer = $tokens[$stackPtr]['scope_closer'];

        $currentLine = $tokens[$opener]['line'];
        $closerLine  = $tokens[$closer]['line'];

        $codeLines    = 0;
        $commentLines = 0;
        $blankLines   = 0;

        // Check whether the line of the scope opener needs to be counted, but ignore trailing comments on that line.
        $firstNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($opener + 1), $closer, true);
        if ($firstNonEmpty !== false && $tokens[$firstNonEmpty]['line'] === $currentLine) {
            ++$codeLines;
        }

        // Check whether the line of the scope closer needs to be counted.
        if ($closerLine !== $currentLine) {
            $hasCommentTokens = false;
            $hasCodeTokens    = false;
            for ($i = ($closer - 1); $tokens[$i]['line'] === $closerLine && $i > $opener; $i--) {
                if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === false) {
                    $hasCodeTokens = true;
                } elseif (isset(Tokens::$commentTokens[$tokens[$i]['code']]) === true) {
                    $hasCommentTokens = true;
                }
            }

            if ($hasCodeTokens === true) {
                ++$codeLines;
            } elseif ($hasCommentTokens === true) {
                ++$commentLines;
            }
        }

        // We've already examined the opener line, so move to the next line.
        for ($i = ($opener + 1); $tokens[$i]['line'] === $currentLine && $i < $closer; $i++);
        $currentLine = $tokens[$i]['line'];

        // Walk tokens.
        while ($currentLine !== $closerLine) {
            $hasCommentTokens = false;
            $hasCodeTokens    = false;

            while ($tokens[$i]['line'] === $currentLine) {
                if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === false) {
                    $hasCodeTokens = true;
                } elseif (isset(Tokens::$commentTokens[$tokens[$i]['code']]) === true) {
                    $hasCommentTokens = true;
                }

                ++$i;
            }

            if ($hasCodeTokens === true) {
                ++$codeLines;
            } elseif ($hasCommentTokens === true) {
                ++$commentLines;
            } else {
                // Only option left is that this is an empty line.
                ++$blankLines;
            }

            $currentLine = $tokens[$i]['line'];
        }

        $nonBlankLines = ($codeLines + $commentLines);
        $totalLines    = ($codeLines + $commentLines + $blankLines);
        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_CODE, $codeLines . ' lines');
        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_COMMENTS, $nonBlankLines . ' lines');
        $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_ALL, $totalLines . ' lines');

        $lines = $codeLines;
        if ($this->ignoreCommentLines === false) {
            $lines += $commentLines;
        }
        if ($this->ignoreEmptyLines === false) {
            $lines += $blankLines;
        }

        $errorSuffix = ' Declare a named function instead. Found closure containing %s lines';

        if ($lines > $this->maxLines) {
            $phpcsFile->addError(
                'Closures which are longer than %s lines are forbidden.' . $errorSuffix,
                $stackPtr,
                'ExceedsMaximum',
                [$this->maxLines, $lines]
            );

            return;
        }

        if ($lines > $this->recommendedLines) {
            $phpcsFile->addWarning(
                'It is recommended for closures to contain %s lines or less.' . $errorSuffix,
                $stackPtr,
                'ExceedsRecommended',
                [$this->recommendedLines, $lines]
            );
        }
    }
}
