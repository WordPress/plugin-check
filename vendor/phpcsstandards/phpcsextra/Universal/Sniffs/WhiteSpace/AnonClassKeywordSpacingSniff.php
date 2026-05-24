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
 * Checks the spacing between the "class" keyword and the open parenthesis for anonymous classes with parentheses.
 *
 * @since 1.0.0
 */
final class AnonClassKeywordSpacingSniff implements Sniff
{

    /**
     * The amount of spacing to demand between the class keyword and the open parenthesis.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public $spacing = 0;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ANON_CLASS];
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
        $tokens       = $phpcsFile->getTokens();
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false || $tokens[$nextNonEmpty]['code'] !== \T_OPEN_PARENTHESIS) {
            // No parentheses, nothing to do.
            return;
        }

        SpacesFixer::checkAndFix(
            $phpcsFile,
            $stackPtr,
            $nextNonEmpty,
            (int) $this->spacing,
            'There must be %1$s between the class keyword and the open parenthesis for an anonymous class. Found: %2$s',
            'Incorrect',
            'error',
            0,
            'Anon class: space between keyword and open parenthesis'
        );
    }
}
