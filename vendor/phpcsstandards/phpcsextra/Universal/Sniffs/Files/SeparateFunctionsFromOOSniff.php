<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;

/**
 * A file should either declare (global/namespaced) functions or declare OO structures, but not both.
 *
 * Nested function declarations, i.e. functions declared within a function/method will be disregarded
 * for the purposes of this sniff.
 * The same goes for anonymous classes, closures and arrow functions.
 *
 * Notes:
 * - This sniff has no opinion on side effects. If you want to sniff for those, use the PHPCS
 *   native `PSR1.Files.SideEffects` sniff.
 * - This sniff has no opinion on multiple OO structures being declared in one file.
 *   If you want to sniff for that, use the PHPCS native `Generic.Files.OneObjectStructurePerFile` sniff.
 *
 * @since 1.0.0
 */
final class SeparateFunctionsFromOOSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Functions or OO declarations ?';

    /**
     * Tokens this sniff searches for.
     *
     * Enhanced from within the register() methods.
     *
     * @since 1.0.0
     *
     * @var array<int|string>
     */
    private $search = [
        // Some tokens to help skip over structures we're not interested in.
        \T_START_HEREDOC => \T_START_HEREDOC,
        \T_START_NOWDOC  => \T_START_NOWDOC,
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
        $this->search += Tokens::$ooScopeTokens;
        $this->search += Collections::functionDeclarationTokens();

        return Collections::phpOpenTags();
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
     * @return int Integer stack pointer to skip forward.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $firstOO       = null;
        $firstFunction = null;
        $functionCount = 0;
        $OOCount       = 0;

        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            // Ignore anything within square brackets.
            if ($tokens[$i]['code'] !== \T_OPEN_CURLY_BRACKET
                && isset($tokens[$i]['bracket_opener'], $tokens[$i]['bracket_closer'])
                && $i === $tokens[$i]['bracket_opener']
            ) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }

            // Skip past nested arrays, function calls and arbitrary groupings.
            if ($tokens[$i]['code'] === \T_OPEN_PARENTHESIS
                && isset($tokens[$i]['parenthesis_closer'])
            ) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            // Skip over potentially large docblocks.
            if ($tokens[$i]['code'] === \T_DOC_COMMENT_OPEN_TAG
                && isset($tokens[$i]['comment_closer'])
            ) {
                $i = $tokens[$i]['comment_closer'];
                continue;
            }

            // Ignore everything else we're not interested in.
            if (isset($this->search[$tokens[$i]['code']]) === false) {
                continue;
            }

            // Skip over structures which won't contain anything we're interested in.
            if (($tokens[$i]['code'] === \T_START_HEREDOC
                || $tokens[$i]['code'] === \T_START_NOWDOC
                || $tokens[$i]['code'] === \T_ANON_CLASS
                || $tokens[$i]['code'] === \T_CLOSURE
                || $tokens[$i]['code'] === \T_FN)
                && isset($tokens[$i]['scope_condition'], $tokens[$i]['scope_closer'])
                && $tokens[$i]['scope_condition'] === $i
            ) {
                $i = $tokens[$i]['scope_closer'];
                continue;
            }

            // This will be either a function declaration or an OO declaration token.
            if ($tokens[$i]['code'] === \T_FUNCTION) {
                if (isset($firstFunction) === false) {
                    $firstFunction = $i;
                }

                ++$functionCount;
            } else {
                if (isset($firstOO) === false) {
                    $firstOO = $i;
                }

                ++$OOCount;
            }

            if (isset($tokens[$i]['scope_closer']) === true) {
                $i = $tokens[$i]['scope_closer'];
            }
        }

        if ($functionCount > 0 && $OOCount > 0) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'Both function and OO declarations');

            $reportToken = \max($firstFunction, $firstOO);

            $phpcsFile->addError(
                'A file should either contain function declarations or OO structure declarations, but not both.'
                    . ' Found %d function declaration(s) and %d OO structure declaration(s).'
                    . ' The first function declaration was found on line %d;'
                    . ' the first OO declaration was found on line %d',
                $reportToken,
                'Mixed',
                [
                    $functionCount,
                    $OOCount,
                    $tokens[$firstFunction]['line'],
                    $tokens[$firstOO]['line'],
                ]
            );
        } elseif ($functionCount > 0) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'Only function(s)');
        } elseif ($OOCount > 0) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'Only OO structure(s)');
        } else {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'Neither');
        }

        // Ignore the rest of the file.
        return $phpcsFile->numTokens;
    }
}
