<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\BackCompat\Helper;
use PHPCSUtils\Fixers\SpacesFixer;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\Parentheses;

/**
 * Check spacing around commas.
 *
 * - Demands there is no whitespace between the preceeding code and the comma.
 * - Demands exactly one space or a new line after a comma.
 * - Demands that when there is a trailing comment, the comma follows the code, not the comment.
 *
 * The following exclusions are in place:
 * - A comma preceded or followed by a parenthesis, curly or square bracket, including attribute brackets.
 *   These will not be flagged to prevent conflicts with sniffs handling spacing around braces/brackets.
 * - A comma preceded or followed by another comma, like for skipping items in a list assignment.
 *   These will not be flagged.
 * - A comma preceded by a non-indented heredoc/nowdoc closer.
 *   In that case, unless the `php_version` config directive is set to a version higher than PHP 7.3.0,
 *   a new line before will be enforced to prevent parse errors on PHP < 7.3.
 *
 * The sniff has a separate error code for when a comma is found with more than one space after it, followed
 * by a trailing comment. That way trailing comment alignment can be allowed by excluding that error code.
 *
 * The sniff uses modular error code suffixes for select situations, like `*InFunctionDeclaration`,
 * `*InFunctionCall`, to allow for preventing duplicate messages if another sniff is already
 * handling the comma spacing.
 *
 * @since 1.1.0
 */
final class CommaSpacingSniff implements Sniff
{

    /**
     * Name of the "Spacing before" metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_BEFORE = 'Spaces found before comma';

    /**
     * Name of the "Spacing after" metric.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_AFTER = 'Spaces found after comma';

    /**
     * PHP version as configured or 0 if unknown.
     *
     * @since 1.1.0
     *
     * @var int
     */
    private $phpVersion;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_COMMA];
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
        if (isset($this->phpVersion) === false || \defined('PHP_CODESNIFFER_IN_TESTS')) {
            // Set default value to prevent this code from running every time the sniff is triggered.
            $this->phpVersion = 0;

            $phpVersion = Helper::getConfigData('php_version');
            if ($phpVersion !== null) {
                $this->phpVersion = (int) $phpVersion;
            }
        }

        $this->processSpacingBefore($phpcsFile, $stackPtr);
        $this->processSpacingAfter($phpcsFile, $stackPtr);
    }

    /**
     * Check the spacing before the comma.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processSpacingBefore(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $prevNonWhitespace = $phpcsFile->findPrevious(\T_WHITESPACE, ($stackPtr - 1), null, true);
        $prevNonEmpty      = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        $nextNonEmpty      = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        if ($prevNonWhitespace !== $prevNonEmpty
            && $tokens[$prevNonEmpty]['code'] !== \T_COMMA
            && $tokens[$prevNonEmpty]['line'] !== $tokens[$nextNonEmpty]['line']
        ) {
            // Special case: comma after a trailing comment - the comma should be moved to before the comment.
            $fix = $phpcsFile->addFixableError(
                'Comma found after comment, expected the comma after the end of the code',
                $stackPtr,
                'CommaAfterComment'
            );

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                $phpcsFile->fixer->replaceToken($stackPtr, '');
                $phpcsFile->fixer->addContent($prevNonEmpty, ',');

                // Clean up potential trailing whitespace left behind, but don't remove blank lines.
                $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($stackPtr + 1), null, true);
                if ($tokens[($stackPtr - 1)]['code'] === \T_WHITESPACE
                    && $tokens[($stackPtr - 1)]['line'] === $tokens[$stackPtr]['line']
                    && $tokens[$stackPtr]['line'] !== $tokens[$nextNonWhitespace]['line']
                ) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
                }

                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        if ($tokens[$prevNonWhitespace]['code'] === \T_COMMA) {
            // This must be a list assignment with ignored items. Ignore.
            return;
        }

        if (isset(Tokens::$blockOpeners[$tokens[$prevNonWhitespace]['code']]) === true
            || $tokens[$prevNonWhitespace]['code'] === \T_OPEN_SHORT_ARRAY
            || $tokens[$prevNonWhitespace]['code'] === \T_OPEN_USE_GROUP
            || $tokens[$prevNonWhitespace]['code'] === \T_ATTRIBUTE
        ) {
            // Should only realistically be possible for lists. Leave for a block brace spacing sniff to sort out.
            return;
        }

        $expectedSpaces = 0;

        if ($tokens[$prevNonEmpty]['code'] === \T_END_HEREDOC
            || $tokens[$prevNonEmpty]['code'] === \T_END_NOWDOC
        ) {
            /*
             * If php_version is explicitly set to PHP < 7.3, enforce a new line between the closer and the comma.
             *
             * If php_version is *not* explicitly set, let the indent be leading and only enforce
             * a new line between the closer and the comma when this is an old-style heredoc/nowdoc.
             */
            if ($this->phpVersion !== 0 && $this->phpVersion < 70300) {
                $expectedSpaces = 'newline';
            }

            if ($this->phpVersion === 0
                && \ltrim($tokens[$prevNonEmpty]['content']) === $tokens[$prevNonEmpty]['content']
            ) {
                $expectedSpaces = 'newline';
            }
        }

        $error        = 'Expected %1$s between "' . $this->escapePlaceholders($tokens[$prevNonWhitespace]['content'])
            . '" and the comma. Found: %2$s';
        $codeSuffix   = $this->getSuffix($phpcsFile, $stackPtr);
        $metricSuffix = $this->codeSuffixToMetric($codeSuffix);

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $prevNonWhitespace,
            $expectedSpaces,
            $error,
            'SpaceBefore' . $codeSuffix,
            'error',
            0,
            self::METRIC_NAME_BEFORE . $metricSuffix
        );
    }

    /**
     * Check the spacing after the comma.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processSpacingAfter(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextNonWhitespace = $phpcsFile->findNext(\T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($nextNonWhitespace === false) {
            // Live coding/parse error. Ignore.
            return;
        }

        if ($tokens[$nextNonWhitespace]['code'] === \T_COMMA) {
            // This must be a list assignment with ignored items. Ignore.
            return;
        }

        if ($tokens[$nextNonWhitespace]['code'] === \T_CLOSE_CURLY_BRACKET
            || $tokens[$nextNonWhitespace]['code'] === \T_CLOSE_SQUARE_BRACKET
            || $tokens[$nextNonWhitespace]['code'] === \T_CLOSE_PARENTHESIS
            || $tokens[$nextNonWhitespace]['code'] === \T_CLOSE_SHORT_ARRAY
            || $tokens[$nextNonWhitespace]['code'] === \T_CLOSE_USE_GROUP
            || $tokens[$nextNonWhitespace]['code'] === \T_ATTRIBUTE_END
        ) {
            // Ignore. Leave for a block spacing sniff to sort out.
            return;
        }

        $nextToken = $tokens[($stackPtr + 1)];

        $error = 'Expected %1$s between the comma and "'
            . $this->escapePlaceholders($tokens[$nextNonWhitespace]['content']) . '". Found: %2$s';

        $codeSuffix   = $this->getSuffix($phpcsFile, $stackPtr);
        $metricSuffix = $this->codeSuffixToMetric($codeSuffix);

        if ($nextToken['code'] === \T_WHITESPACE) {
            if ($nextToken['content'] === ' ') {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_AFTER . $metricSuffix, '1 space');
                return;
            }

            // Note: this check allows for trailing whitespace between the comma and a new line char.
            // The trailing whitespace is not the concern of this sniff.
            if (\ltrim($nextToken['content'], ' ') === $phpcsFile->eolChar) {
                $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME_AFTER . $metricSuffix, 'a new line');
                return;
            }

            $errorCode = 'TooMuchSpaceAfter' . $codeSuffix;

            $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if (isset(Tokens::$commentTokens[$tokens[$nextNonWhitespace]['code']]) === true
                && ($nextNonEmpty === false || $tokens[$stackPtr]['line'] !== $tokens[$nextNonEmpty]['line'])
            ) {
                // Separate error code to allow for aligning trailing comments.
                $errorCode = 'TooMuchSpaceAfterCommaBeforeTrailingComment';
            }

            SpacesFixer::checkAndFix(
                $phpcsFile,
                $stackPtr,
                $nextNonWhitespace,
                1,
                $error,
                $errorCode,
                'error',
                0,
                self::METRIC_NAME_AFTER . $metricSuffix
            );
            return;
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonWhitespace,
            1,
            $error,
            'NoSpaceAfter' . $codeSuffix,
            'error',
            0,
            self::METRIC_NAME_AFTER . $metricSuffix
        );
    }

    /**
     * Escape arbitrary token content for *printf() placeholders.
     *
     * @since 1.1.0
     *
     * @param string $text Arbitrary text string.
     *
     * @return string
     */
    private function escapePlaceholders($text)
    {
        $escapeSinglePercentSign = function ($text) {
            return \preg_replace('`(^|[^%])%([^%]|$)`', '\1%%\2', \trim($text));
        };

        // We need to "double" escape to make sure chars involved in the `\2` match will
        // be taken into account for the decision on whether or not to escape the char _after_ that.
        return $escapeSinglePercentSign($escapeSinglePercentSign($text));
    }

    /**
     * Retrieve a text string for use as a suffix to an error code.
     *
     * This allows for modular error codes, which in turn allow for selectively excluding
     * error codes.
     *
     * {@internal Closure use will be parentheses owner in PHPCS 4.x, this code will
     * need an update for that in due time.}
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return string
     */
    private function getSuffix($phpcsFile, $stackPtr)
    {
        $opener = Parentheses::getLastOpener($phpcsFile, $stackPtr);
        if ($opener === false) {
            if (Context::inAttribute($phpcsFile, $stackPtr) === true) {
                return 'InAttributeBlock';
            }

            return '';
        }

        $tokens = $phpcsFile->getTokens();

        $owner = Parentheses::getOwner($phpcsFile, $opener);
        if ($owner !== false) {
            switch ($tokens[$owner]['code']) {
                case \T_FUNCTION:
                case \T_CLOSURE:
                case \T_FN:
                    return 'InFunctionDeclaration';

                case \T_USE:
                    return 'InClosureUse';

                case \T_DECLARE:
                    return 'InDeclare';

                case \T_ANON_CLASS:
                case \T_ISSET:
                case \T_UNSET:
                    return 'InFunctionCall';

                // Long array, long list, empty, exit, eval, control structures.
                default:
                    return '';
            }
        }

        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($opener - 1), null, true);

        if (isset(Collections::nameTokens()[$tokens[$prevNonEmpty]['code']]) === true) {
            return 'InFunctionCall';
        }

        switch ($tokens[$prevNonEmpty]['code']) {
            case \T_VARIABLE:
            case \T_SELF:
            case \T_STATIC:
            case \T_PARENT:
                return 'InFunctionCall';

            default:
                return '';
        }
    }

    /**
     * Transform a suffix for an error code into a suffix for a metric.
     *
     * @since 1.1.0
     *
     * @param string $suffix Error code suffix.
     *
     * @return string
     */
    private function codeSuffixToMetric($suffix)
    {
        return \strtolower(\preg_replace('`([A-Z])`', ' $1', $suffix));
    }
}
