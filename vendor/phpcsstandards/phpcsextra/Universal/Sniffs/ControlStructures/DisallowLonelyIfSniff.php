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
use PHPCSUtils\Utils\ControlStructures;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Disallow `if` statements as the only statement in an `else` block.
 *
 * Note: This sniff will not fix the indentation of the "inner" code. It is strongly recommended to run
 * this sniff together with the `Generic.WhiteSpace.ScopeIndent` sniff to get the correct indentation.
 *
 * Inspired by the {@link https://eslint.org/docs/rules/no-lonely-if ESLint "no lonely if"} rule
 * in response to upstream {@link https://github.com/squizlabs/PHP_CodeSniffer/issues/3206 PHPCS issue 3206}.
 *
 * @since 1.0.0
 */
final class DisallowLonelyIfSniff implements Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.0.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ELSE];
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

        /*
         * Deal with `else if`.
         */
        if (ControlStructures::isElseIf($phpcsFile, $stackPtr) === true) {
            // Ignore, not our real target.
            return;
        }

        if (isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer']) === false) {
            // Either an else without curly braces or a parse error. Ignore.
            return;
        }

        $outerScopeOpener = $tokens[$stackPtr]['scope_opener'];
        $outerScopeCloser = $tokens[$stackPtr]['scope_closer'];

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($outerScopeOpener + 1), $outerScopeCloser, true);
        if ($nextNonEmpty === false || $tokens[$nextNonEmpty]['code'] !== \T_IF) {
            // Definitely not a lonely if statement.
            return;
        }

        if (isset($tokens[$nextNonEmpty]['scope_closer']) === false) {
            // Either a control structure without curly braces or a parse error. Ignore.
            return;
        }

        /*
         * Find the end of an if - else chain.
         */

        $innerIfPtr       = $nextNonEmpty;
        $innerIfToken     = $tokens[$innerIfPtr];
        $autoFixable      = true;
        $innerScopeCloser = $innerIfToken['scope_closer'];

        // For alternative syntax fixer only.
        // Remember the individual inner scope opener and closers so the fixer doesn't need
        // to do the same walking over the if/else chain again.
        $innerScopes = [
            $innerIfToken['scope_opener'] => $innerScopeCloser,
        ];

        do {
            /*
             * Handle control structures using alternative syntax.
             */
            if ($tokens[$innerScopeCloser]['code'] !== \T_CLOSE_CURLY_BRACKET) {
                if ($tokens[$innerScopeCloser]['code'] === \T_ENDIF) {
                    $nextAfter = $phpcsFile->findNext(
                        Tokens::$emptyTokens,
                        ($innerScopeCloser + 1),
                        $outerScopeCloser,
                        true
                    );

                    if ($tokens[$nextAfter]['code'] === \T_CLOSE_TAG) {
                        // Not "lonely" as at the very least there must be a PHP open tag before the outer closer.
                        return;
                    }

                    if ($tokens[$nextAfter]['code'] === \T_SEMICOLON) {
                        $innerScopeCloser = $nextAfter;
                    } else {
                        // Missing semicolon. Report, but don't auto-fix.
                        $autoFixable = false;
                    }
                } else {
                    // This must be an else[if].
                    --$innerScopeCloser;
                }
            }

            $innerNextNonEmpty = $phpcsFile->findNext(
                Tokens::$emptyTokens,
                ($innerScopeCloser + 1),
                $outerScopeCloser,
                true
            );
            if ($innerNextNonEmpty === false) {
                // This was the last closer.
                break;
            }

            if ($tokens[$innerNextNonEmpty]['code'] !== \T_ELSE
                && $tokens[$innerNextNonEmpty]['code'] !== \T_ELSEIF
            ) {
                // Found another statement after the control structure. The "if" is not lonely.
                return;
            }

            if (isset($tokens[$innerNextNonEmpty]['scope_closer']) === false) {
                // This may still be an "else if"...
                $nextAfter = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($innerNextNonEmpty + 1),
                    $outerScopeCloser,
                    true
                );

                if ($nextAfter === false
                    || $tokens[$nextAfter]['code'] !== \T_IF
                    || isset($tokens[$nextAfter]['scope_closer']) === false
                ) {
                    // Defense in depth. Either a control structure without curly braces or a parse error. Ignore.
                    return;
                }

                $innerNextNonEmpty = $nextAfter;
            }

            $innerScopeCloser = $tokens[$innerNextNonEmpty]['scope_closer'];
            $innerScopes[$tokens[$innerNextNonEmpty]['scope_opener']] = $innerScopeCloser;
        } while (true);

        /*
         * As of now, we know we have an error. Check if it can be auto-fixed.
         */
        if ($phpcsFile->findNext(\T_WHITESPACE, ($innerScopeCloser + 1), $outerScopeCloser, true) !== false) {
            // Comment between the inner and outer closers.
            $autoFixable = false;
        }

        if ($tokens[$innerScopeCloser]['code'] === \T_SEMICOLON) {
            $hasComment = $phpcsFile->findPrevious(\T_WHITESPACE, ($innerScopeCloser - 1), null, true);
            if ($tokens[$hasComment]['code'] !== \T_ENDIF) {
                // Comment between the "endif" and the semi-colon.
                $autoFixable = false;
            }
        }

        if ($tokens[$outerScopeOpener]['line'] !== $innerIfToken['line']) {
            for ($startOfNextLine = ($outerScopeOpener + 1); $startOfNextLine < $innerIfPtr; $startOfNextLine++) {
                if ($tokens[$outerScopeOpener]['line'] !== $tokens[$startOfNextLine]['line']) {
                    break;
                }
            }

            if ($phpcsFile->findNext(\T_WHITESPACE, $startOfNextLine, $innerIfPtr, true) !== false) {
                // Comment between the inner and outer openers.
                $autoFixable = false;
            }
        }

        if (isset($innerIfToken['parenthesis_opener'], $innerIfToken['parenthesis_closer']) === false) {
            // Start/end of the condition of the if unclear. Most likely a parse error.
            $autoFixable = false;
        }

        /*
         * Throw the error and potentially fix it.
         */
        $error = 'If control structure block found as the only statement within an "else" block. Use elseif instead.';
        $code  = 'Found';

        if ($autoFixable === false) {
            $phpcsFile->addError($error, $stackPtr, $code);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, $code);
        if ($fix === false) {
            return;
        }

        /*
         * Fix it.
         */
        $outerInnerSameType = false;
        if (($tokens[$outerScopeCloser]['code'] === \T_CLOSE_CURLY_BRACKET
            && $tokens[$innerScopeCloser]['code'] === \T_CLOSE_CURLY_BRACKET)
            || ($tokens[$outerScopeCloser]['code'] === \T_ENDIF
            && $tokens[$innerScopeCloser]['code'] === \T_SEMICOLON)
        ) {
            $outerInnerSameType = true;
        }

        $targetIsCurly = ($tokens[$outerScopeCloser]['code'] === \T_CLOSE_CURLY_BRACKET);

        $innerScopeCount = \count($innerScopes);

        $condition = GetTokensAsString::origContent($phpcsFile, ($innerIfPtr + 1), ($innerIfToken['scope_opener'] - 1));
        if ($targetIsCurly === true) {
            $condition = \rtrim($condition) . ' ';
        }

        $phpcsFile->fixer->beginChangeset();

        // Remove the inner if + condition up to and including the scope opener.
        for ($i = $innerIfPtr; $i <= $innerIfToken['scope_opener']; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        // Potentially remove trailing whitespace/new line if there is no comment after the inner condition.
        while ($tokens[$i]['line'] === $innerIfToken['line']
            && $tokens[$i]['code'] === \T_WHITESPACE
        ) {
            $phpcsFile->fixer->replaceToken($i, '');
            ++$i;
        }

        // Remove any potential indentation whitespace for the inner if.
        if ($tokens[$outerScopeOpener]['line'] !== $innerIfToken['line']
            && $tokens[$i]['line'] !== $innerIfToken['line']
        ) {
            $i = ($nextNonEmpty - 1);
            while ($tokens[$i]['line'] === $innerIfToken['line']
                && $tokens[$i]['code'] === \T_WHITESPACE
            ) {
                $phpcsFile->fixer->replaceToken($i, '');
                --$i;
            }
        }

        // Remove the inner scope closer.
        $phpcsFile->fixer->replaceToken($innerScopeCloser, '');
        $i = ($innerScopeCloser - 1);

        // Handle alternative syntax for the closer.
        if ($tokens[$innerScopeCloser]['code'] === \T_SEMICOLON) {
            // Remove potential whitespace between the "endif" and the semicolon.
            while ($tokens[$i]['code'] === \T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken($i, '');
                --$i;
            }

            // Remove the "endif".
            $phpcsFile->fixer->replaceToken($i, '');
            --$i;
        }

        // Remove superfluous whitespace before the inner scope closer.
        while ($tokens[$i]['code'] === \T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken($i, '');
            --$i;
        }

        // Replace the else.
        $phpcsFile->fixer->replaceToken($stackPtr, 'elseif' . $condition);

        // Remove potential superfluous whitespace between the new condition and the scope opener.
        $i = ($stackPtr + 1);
        while ($tokens[$i]['line'] === $tokens[$stackPtr]['line']
            && $tokens[$i]['code'] === \T_WHITESPACE
        ) {
            $phpcsFile->fixer->replaceToken($i, '');
            ++$i;
        }

        if ($outerInnerSameType === false
            && $innerScopeCount > 1
        ) {
            $loop = 1;
            foreach ($innerScopes as $opener => $closer) {
                if ($targetIsCurly === true) {
                    if ($loop !== 1) {
                        // Only handle the opener when it's not the first of the chain as that's already handled above.
                        $phpcsFile->fixer->replaceToken($opener, ' {');
                    }

                    if ($loop !== $innerScopeCount) {
                        // Only handle the closer when it's not the last of the chain as that's already handled above.
                        $phpcsFile->fixer->addContentBefore($closer, '} ');
                    }
                } else {
                    if ($loop !== 1) {
                        // Only handle the opener when it's not the first of the chain as that's already handled above.
                        $phpcsFile->fixer->replaceToken($opener, ':');
                    }

                    if ($loop !== $innerScopeCount) {
                        // Only handle the closer when it's not the last of the chain as that's already handled above.
                        $phpcsFile->fixer->replaceToken($closer, '');

                        $j = ($closer + 1);
                        while ($tokens[$j]['code'] === \T_WHITESPACE) {
                            $phpcsFile->fixer->replaceToken($j, '');
                            ++$j;
                        }
                    }
                }

                ++$loop;
            }
        }

        $phpcsFile->fixer->endChangeset();
    }
}
