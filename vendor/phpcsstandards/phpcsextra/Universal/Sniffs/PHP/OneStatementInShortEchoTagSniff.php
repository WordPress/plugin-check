<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows multiple statements when PHP is opened with a short open echo tag.
 *
 * When using short open echo tags, PHP will echo out the result of the first statement.
 * Subsequent statements will not be echo-ed out, but will be treated as "normal" PHP.
 *
 * As a best practice, short open echo tags should contain only one statement.
 *
 * @since 1.0.0
 */
final class OneStatementInShortEchoTagSniff implements Sniff
{

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_OPEN_TAG_WITH_ECHO];
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
        $tokens = $phpcsFile->getTokens();

        for ($endOfStatement = ($stackPtr + 1); $endOfStatement < $phpcsFile->numTokens; $endOfStatement++) {
            if ($tokens[$endOfStatement]['code'] === \T_CLOSE_TAG
                || $tokens[$endOfStatement]['code'] === \T_SEMICOLON
            ) {
                break;
            }

            // Skip over anything within parenthesis.
            if ($tokens[$endOfStatement]['code'] === \T_OPEN_PARENTHESIS
                && isset($tokens[$endOfStatement]['parenthesis_closer'])
            ) {
                $endOfStatement = $tokens[$endOfStatement]['parenthesis_closer'];
            }
        }

        if ($endOfStatement === $phpcsFile->numTokens
            || $tokens[$endOfStatement]['code'] === \T_CLOSE_TAG
        ) {
            return;
        }

        // Semicolon, so check for any code between it and the close tag.
        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($endOfStatement + 1), null, true);
        if ($nextNonEmpty === false
            || $tokens[$nextNonEmpty]['code'] === \T_CLOSE_TAG
        ) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Only one statement is allowed when using short open echo PHP tags.'
                . ' Use the "<?php" open tag for multi-statement PHP.',
            $nextNonEmpty,
            'MultipleFound'
        );

        if ($fix === true) {
            if ($tokens[($stackPtr + 1)]['code'] === \T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken($stackPtr, '<?php echo');
            } else {
                $phpcsFile->fixer->replaceToken($stackPtr, '<?php echo ');
            }
        }
    }
}
