<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\ControlStructures;

/**
 * Forbid the use of the alternative syntax for control structures.
 *
 * @since 1.0.0
 */
final class DisallowAlternativeSyntaxSniff implements Sniff
{

    /**
     * Name of the metric.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const METRIC_NAME = 'Control Structure Style';

    /**
     * Whether to allow the alternative syntax when it is wrapped around
     * inline HTML, as is often seen in views.
     *
     * Note: inline HTML within "closed scopes" - like function declarations -,
     * within the control structure body will not be taken into account.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $allowWithInlineHTML = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        $targets = Collections::alternativeControlStructureSyntaxes();

        // Don't look for elseif/else as they need to be dealt with in one go with the if.
        unset($targets[\T_ELSEIF], $targets[\T_ELSE]);

        return $targets;
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
        /*
         * Ignore control structures without body (i.e. single line control structures).
         * This doesn't ignore _empty_ bodies.
         */
        if (ControlStructures::hasBody($phpcsFile, $stackPtr, true) === false) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'single line (without body)');
            return;
        }

        $tokens = $phpcsFile->getTokens();

        /*
         * Check if the control structure uses alternative syntax.
         */
        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            // No scope opener found: inline control structure or parse error.
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'inline');
            return;
        }

        $opener = $tokens[$stackPtr]['scope_opener'];
        $closer = $tokens[$stackPtr]['scope_closer'];

        if ($tokens[$opener]['code'] !== \T_COLON) {
            // Curly brace syntax (not our concern).
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'curly braces');
            return;
        }

        /*
         * As of here, we *know* the control structure must be using alternative syntax and
         * must have all scope openers/closers set as, in case of parse errors, PHPCS wouldn't
         * have set the scope opener, even for the first `if`.
         *
         * Also note that alternative syntax cannot be used with `else if`, so we don't need to take that
         * into account.
         */

        /*
         * Determine whether there is inline HTML.
         *
         * For "chained" control structures (if - elseif - else), the complete control structure
         * needs to be examined in one go as these cannot be changed individually, only as a complete group.
         */
        $closedScopes         = Collections::closedScopes();
        $find                 = $closedScopes;
        $find[\T_INLINE_HTML] = \T_INLINE_HTML;

        $chainedIssues = [];
        $hasInlineHTML = false;
        $currentPtr    = $stackPtr;

        do {
            $opener                 = $tokens[$currentPtr]['scope_opener'];
            $closer                 = $tokens[$currentPtr]['scope_closer'];
            $chainedIssues[$opener] = $closer;

            if ($hasInlineHTML === true) {
                // No need to search the contents, we already know there is inline HTML.
                $currentPtr = $closer;
                continue;
            }

            $inlineHTMLPtr = $opener;

            do {
                $inlineHTMLPtr = $phpcsFile->findNext($find, ($inlineHTMLPtr + 1), $closer);
                if ($tokens[$inlineHTMLPtr]['code'] === \T_INLINE_HTML) {
                    $hasInlineHTML = true;
                    break;
                }

                if (isset($closedScopes[$tokens[$inlineHTMLPtr]['code']], $tokens[$inlineHTMLPtr]['scope_closer'])) {
                    $inlineHTMLPtr = $tokens[$inlineHTMLPtr]['scope_closer'];
                }
            } while ($inlineHTMLPtr !== false && $inlineHTMLPtr < $closer);

            $currentPtr = $closer;
        } while (isset(Collections::alternativeControlStructureSyntaxes()[$tokens[$closer]['code']]) === true);

        if ($hasInlineHTML === true) {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'alternative syntax with inline HTML');
        } else {
            $phpcsFile->recordMetric($stackPtr, self::METRIC_NAME, 'alternative syntax');
        }

        if ($hasInlineHTML === true && $this->allowWithInlineHTML === true) {
            return;
        }

        $error = 'Using control structures with the alternative syntax is not allowed';
        if ($this->allowWithInlineHTML === true) {
            $error .= ' unless the control structure contains inline HTML';
        }
        $error .= '. Found: %1$s(): ... end%1$s;';

        $code = 'Found' . \ucfirst($tokens[$stackPtr]['content']);
        if ($hasInlineHTML === true) {
            $code .= 'WithInlineHTML';
        }

        $data = [$tokens[$stackPtr]['content']];

        foreach ($chainedIssues as $opener => $closer) {
            $fix = $phpcsFile->addFixableError($error, $opener, $code, $data);
        }

        if ($fix === false) {
            return;
        }

        /*
         * Fix all issues for this chain in one go to diminish the chance of conflicts.
         */
        $phpcsFile->fixer->beginChangeset();

        foreach ($chainedIssues as $opener => $closer) {
            $phpcsFile->fixer->replaceToken($opener, '{');

            if (isset(Collections::alternativeControlStructureSyntaxClosers()[$tokens[$closer]['code']]) === true) {
                $phpcsFile->fixer->replaceToken($closer, '}');

                $semicolon = $phpcsFile->findNext(Tokens::$emptyTokens, ($closer + 1), null, true);
                if ($semicolon !== false && $tokens[$semicolon]['code'] === \T_SEMICOLON) {
                    $phpcsFile->fixer->replaceToken($semicolon, '');
                }
            } else {
                /*
                 * This must be an if/else using alternative syntax.
                 * The closer will be the next control structure keyword.
                 */
                $phpcsFile->fixer->addContentBefore($closer, '} ');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }
}
