<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2023 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\UseStatements;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Fixers\SpacesFixer;
use PHPCSUtils\Utils\UseStatements;

/**
 * Enforce a single space after the keywords in import `use` statements.
 *
 * The keywords this sniff checks are `use`, `function`, `const` and `as`.
 * For `as`, the space *before* the keyword is also checked to be a single space.
 *
 * @since 1.1.0
 */
final class KeywordSpacingSniff implements Sniff
{

    /**
     * Name of the metric for spacing before.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_BEFORE = 'Space before "%s" keyword in import use statement';

    /**
     * Name of the metric for spacing after.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const METRIC_NAME_AFTER = 'Space after "%s" keyword in import use statement';

    /**
     * A list of keywords that are tokenized as `T_STRING` in import `use` statements.
     *
     * @since 1.1.0
     *
     * @var array<string, true>
     */
    protected $keywords = [
        'const'    => true,
        'function' => true,
    ];

    /**
     * Returns an array of tokens this sniff wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_USE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (UseStatements::isImportUse($phpcsFile, $stackPtr) === false) {
            // Trait or closure use statement.
            return;
        }

        $tokens         = $phpcsFile->getTokens();
        $endOfStatement = $phpcsFile->findNext([\T_SEMICOLON, \T_CLOSE_TAG], ($stackPtr + 1));
        if ($endOfStatement === false) {
            // Live coding or parse error.
            return;
        }

        // Check the spacing after the `use` keyword.
        $this->checkSpacingAfterKeyword($phpcsFile, $stackPtr, $tokens[$stackPtr]['content']);

        // Check the spacing before and after each `as` keyword.
        $current = $stackPtr;
        do {
            $current = $phpcsFile->findNext(\T_AS, ($current + 1), $endOfStatement);
            if ($current === false) {
                break;
            }

            // Prevent false positives when "as" is used within a "name".
            if (isset(Tokens::$emptyTokens[$tokens[($current - 1)]['code']]) === true) {
                $this->checkSpacingBeforeKeyword($phpcsFile, $current, $tokens[$current]['content']);
                $this->checkSpacingAfterKeyword($phpcsFile, $current, $tokens[$current]['content']);
            }
        } while (true);

        /*
         * Check the spacing after `function` and `const` keywords.
         */
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if (isset($this->keywords[\strtolower($tokens[$nextNonEmpty]['content'])]) === true) {
            // Keyword found at start of statement, applies to whole statement.
            $this->checkSpacingAfterKeyword($phpcsFile, $nextNonEmpty, $tokens[$nextNonEmpty]['content']);
            return;
        }

        // This may still be a group use statement with function/const substatements.
        $openGroup = $phpcsFile->findNext(\T_OPEN_USE_GROUP, ($stackPtr + 1), $endOfStatement);
        if ($openGroup === false) {
            // Not a group use statement.
            return;
        }

        $closeGroup = $phpcsFile->findNext(\T_CLOSE_USE_GROUP, ($openGroup + 1), $endOfStatement);

        $current = $openGroup;
        do {
            $current = $phpcsFile->findNext(Tokens::$emptyTokens, ($current + 1), $closeGroup, true);
            if ($current === false) {
                return;
            }

            if (isset($this->keywords[\strtolower($tokens[$current]['content'])]) === true) {
                $this->checkSpacingAfterKeyword($phpcsFile, $current, $tokens[$current]['content']);
            }

            // We're within the use group, so find the next comma.
            $current = $phpcsFile->findNext(\T_COMMA, ($current + 1), $closeGroup);
        } while ($current !== false);
    }

    /**
     * Check the spacing before a found keyword.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the keyword in the token stack.
     * @param string                      $content   The keyword as found.
     *
     * @return void
     */
    public function checkSpacingBeforeKeyword(File $phpcsFile, $stackPtr, $content)
    {
        $contentLC    = \strtolower($content);
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $prevNonEmpty,
            1, // Expected spaces.
            'Expected %s before the "' . $contentLC . '" keyword. Found: %s',
            'SpaceBefore' . \ucfirst($contentLC),
            'error',
            0, // Severity.
            \sprintf(self::METRIC_NAME_BEFORE, $contentLC)
        );
    }

    /**
     * Check the spacing after a found keyword.
     *
     * @since 1.1.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the keyword in the token stack.
     * @param string                      $content   The keyword as found.
     *
     * @return void
     */
    public function checkSpacingAfterKeyword(File $phpcsFile, $stackPtr, $content)
    {
        $contentLC    = \strtolower($content);
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonEmpty,
            1, // Expected spaces.
            'Expected %s after the "' . $contentLC . '" keyword. Found: %s',
            'SpaceAfter' . \ucfirst($contentLC),
            'error',
            0, // Severity.
            \sprintf(self::METRIC_NAME_AFTER, $contentLC)
        );
    }
}
