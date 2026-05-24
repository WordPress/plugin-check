<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2023 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Detects use of `echo [v]sprintf();.
 *
 * @link https://www.php.net/manual/en/function.printf.php
 * @link https://www.php.net/manual/en/function.sprintf.php
 * @link https://www.php.net/manual/en/function.vprintf.php
 * @link https://www.php.net/manual/en/function.vsprintf.php
 *
 * @since 1.1.0
 */
final class NoEchoSprintfSniff implements Sniff
{

    /**
     * Functions to look for with their replacements.
     *
     * @since 1.1.0
     *
     * @var array<string, string>
     */
    private $targetFunctions = [
        'sprintf'  => 'printf',
        'vsprintf' => 'vprintf',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @since 1.1.0
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [\T_ECHO];
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
        $tokens = $phpcsFile->getTokens();

        $skip   = Tokens::$emptyTokens;
        $skip[] = \T_NS_SEPARATOR;

        $next = $phpcsFile->findNext($skip, ($stackPtr + 1), null, true);
        if ($next === false
            || ($tokens[$next]['code'] !== \T_STRING
            && $tokens[$next]['code'] !== \T_NAME_FULLY_QUALIFIED)
        ) {
            // Definitely not our target.
            return;
        }

        $detectedFunction = \strtolower($tokens[$next]['content']);
        if ($tokens[$next]['code'] === \T_NAME_FULLY_QUALIFIED) {
            $detectedFunction = \ltrim($detectedFunction, '\\');
        }

        if (isset($this->targetFunctions[$detectedFunction]) === false) {
            // Not one of our target functions.
            return;
        }

        $openParens = $phpcsFile->findNext(Tokens::$emptyTokens, ($next + 1), null, true);
        if ($openParens === false
            || $tokens[$openParens]['code'] !== \T_OPEN_PARENTHESIS
            || isset($tokens[$openParens]['parenthesis_closer']) === false
        ) {
            // Live coding/parse error.
            return;
        }

        $closeParens       = $tokens[$openParens]['parenthesis_closer'];
        $afterFunctionCall = $phpcsFile->findNext(Tokens::$emptyTokens, ($closeParens + 1), null, true);
        if ($afterFunctionCall === false
            || ($tokens[$afterFunctionCall]['code'] !== \T_SEMICOLON
            && $tokens[$afterFunctionCall]['code'] !== \T_CLOSE_TAG)
        ) {
            // Live coding/parse error or compound echo statement.
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Unnecessary "echo %s(...)" found. Use "%s(...)" instead.',
            $next,
            'Found',
            [
                $tokens[$next]['content'],
                $this->targetFunctions[$detectedFunction],
            ]
        );

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            // Remove echo and whitespace.
            $phpcsFile->fixer->replaceToken($stackPtr, '');

            for ($i = ($stackPtr + 1); $i < $next; $i++) {
                if ($tokens[$i]['code'] !== \T_WHITESPACE) {
                    break;
                }

                $phpcsFile->fixer->replaceToken($i, '');
            }

            $replacement = $this->targetFunctions[$detectedFunction];
            if ($tokens[$next]['code'] === \T_NAME_FULLY_QUALIFIED) {
                $replacement = '\\' . $replacement;
            }

            $phpcsFile->fixer->replaceToken($next, $replacement);

            $phpcsFile->fixer->endChangeset();
        }
    }
}
