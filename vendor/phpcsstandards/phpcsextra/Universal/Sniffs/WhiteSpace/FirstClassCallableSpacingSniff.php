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
use PHPCSUtils\Fixers\SpacesFixer;

/**
 * Checks the spacing around the ellipses for first class callables.
 *
 * @since 1.5.0
 */
final class FirstClassCallableSpacingSniff implements Sniff
{

    /**
     * The number of spaces to demand before and after the ellipsis for a first class callable.
     *
     * @since 1.5.0
     *
     * @var int
     */
    public $spacing = 0;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.5.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ELLIPSIS];
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

        // Verify this is an ellipsis for a first class callable.
        $previousNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($tokens[$previousNonEmpty]['code'] !== \T_OPEN_PARENTHESIS) {
            return;
        }

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false || $tokens[$nextNonEmpty]['code'] !== \T_CLOSE_PARENTHESIS) {
            return;
        }

        $spacing = (int) $this->spacing;

        // Check spacing before the ellipsis.
        SpacesFixer::checkAndFix(
            $phpcsFile,
            $previousNonEmpty,
            $stackPtr,
            $spacing,
            'Incorrect spacing between first class callable open parentheses and ellipsis. Expected: %s, found: %s.',
            'SpacingBefore',
            'error',
            0,
            'First class callables: space before ellipsis'
        );

        // Check spacing after the ellipsis.
        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonEmpty,
            $spacing,
            'Incorrect spacing between first class callable ellipsis and close parentheses. Expected: %s, found: %s.',
            'SpacingAfter',
            'error',
            0,
            'First class callables: space after ellipsis'
        );
    }
}
