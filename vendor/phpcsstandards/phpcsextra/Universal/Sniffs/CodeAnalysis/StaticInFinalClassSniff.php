<?php
/**
 * PHPCSExtra, a collection of sniffs and standards for use with PHP_CodeSniffer.
 *
 * @package   PHPCSExtra
 * @copyright 2020 PHPCSExtra Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCSStandards/PHPCSExtra
 */

namespace PHPCSExtra\Universal\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\GetTokensAsString;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\Scopes;

/**
 * Forbid the use of the `static` keyword for late static binding in OO constructs which are final.
 *
 * @since 1.0.0
 */
final class StaticInFinalClassSniff implements Sniff
{

    /**
     * OO Scopes in which late static binding is useless.
     *
     * @var array<int|string>
     */
    private $validOOScopes = [
        \T_CLASS,      // Only if final.
        \T_ANON_CLASS, // Final by nature.
        \T_ENUM,       // Final by design.
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
        return [
            // These tokens are used to retrieve return types reliably.
            \T_FUNCTION,
            \T_FN,
            // While this is our "real" target.
            \T_STATIC,
            // But we also need this as after "instanceof", `static` is tokenized as `T_STRING in PHPCS < 4.0.0.
            // See: https://github.com/squizlabs/PHP_CodeSniffer/pull/3121
            \T_STRING,
        ];
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
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === \T_STRING
            && \strtolower($tokens[$stackPtr]['content']) !== 'static'
        ) {
            return;
        }

        if ($tokens[$stackPtr]['code'] === \T_FUNCTION
            || $tokens[$stackPtr]['code'] === \T_FN
        ) {
            /*
             * Check return types for methods in final classes, anon classes and enums.
             *
             * Will return the scope opener of the function to prevent potential duplicate notifications.
             */
            $scopeOpener = $stackPtr;
            if (isset($tokens[$stackPtr]['scope_opener']) === true) {
                $scopeOpener = $tokens[$stackPtr]['scope_opener'];
            }

            if ($tokens[$stackPtr]['code'] === \T_FUNCTION) {
                $ooPtr = Scopes::validDirectScope($phpcsFile, $stackPtr, $this->validOOScopes);
                if ($ooPtr === false) {
                    // Method in a trait (not known where it is used), interface (never final) or not in an OO scope.
                    return $scopeOpener;
                }
            } else {
                $ooPtr = Conditions::getLastCondition($phpcsFile, $stackPtr, $this->validOOScopes);
                if ($ooPtr === false) {
                    // Arrow function outside of OO.
                    return $scopeOpener;
                }
            }

            if ($tokens[$ooPtr]['code'] === \T_CLASS) {
                $classProps = ObjectDeclarations::getClassProperties($phpcsFile, $ooPtr);
                if ($classProps['is_final'] === false) {
                    // Method in a non-final class.
                    return $scopeOpener;
                }
            }

            $functionProps = FunctionDeclarations::getProperties($phpcsFile, $stackPtr);
            if ($functionProps['return_type'] === '') {
                return $scopeOpener;
            }

            $staticPtr = $phpcsFile->findNext(
                \T_STATIC,
                $functionProps['return_type_token'],
                ($functionProps['return_type_end_token'] + 1)
            );

            if ($staticPtr === false) {
                return $scopeOpener;
            }

            // Found a return type containing the `static` type.
            $this->handleError($phpcsFile, $staticPtr, 'ReturnType', '"static" return type');

            return $scopeOpener;
        }

        /*
         * Check other uses of static.
         */
        $functionPtr = Conditions::getLastCondition($phpcsFile, $stackPtr, [\T_FUNCTION, \T_CLOSURE]);
        if ($functionPtr === false || $tokens[$functionPtr]['code'] === \T_CLOSURE) {
            /*
             * When `false`, this code is absolutely invalid, but not something to be addressed via this sniff.
             * When a closure, we're not interested in it. The closure class is final, but closures
             * can be bound to other classes. This needs further research and should maybe get its own sniff.
             */
            return;
        }

        $ooPtr = Scopes::validDirectScope($phpcsFile, $functionPtr, $this->validOOScopes);
        if ($ooPtr === false) {
            // Not in an OO context.
            return;
        }

        if ($tokens[$ooPtr]['code'] === \T_CLASS) {
            $classProps = ObjectDeclarations::getClassProperties($phpcsFile, $ooPtr);
            if ($classProps['is_final'] === false) {
                // Token in a non-final class.
                return;
            }
        }

        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevNonEmpty !== false) {
            if ($tokens[$prevNonEmpty]['code'] === \T_INSTANCEOF) {
                $prevPrevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prevNonEmpty - 1), null, true);
                $extraMsg         = GetTokensAsString::compact($phpcsFile, $prevPrevNonEmpty, $stackPtr, true);
                $this->handleError($phpcsFile, $stackPtr, 'InstanceOf', '"' . $extraMsg . '"');
                return;
            }

            if ($tokens[$prevNonEmpty]['code'] === \T_NEW) {
                $this->handleError($phpcsFile, $stackPtr, 'NewInstance', '"new static"');
                return;
            }
        }

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty !== false && $tokens[$nextNonEmpty]['code'] === \T_DOUBLE_COLON) {
            $nextNextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($nextNonEmpty + 1), null, true);
            $extraMsg         = GetTokensAsString::compact($phpcsFile, $stackPtr, $nextNextNonEmpty, true);
            $this->handleError($phpcsFile, $stackPtr, 'ScopeResolution', '"' . $extraMsg . '"');
            return;
        }
    }

    /**
     * Throw and potentially fix the error.
     *
     * @since 1.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of erroneous `T_STATIC` token.
     * @param string                      $errorCode The error code for the message.
     * @param string                      $extraMsg  Addition to the error message.
     *
     * @return void
     */
    private function handleError($phpcsFile, $stackPtr, $errorCode, $extraMsg)
    {
        $fix = $phpcsFile->addFixableError(
            'Use "self" instead of "static" when using late static binding in a final OO construct. Found: %s',
            $stackPtr,
            $errorCode,
            [$extraMsg]
        );

        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, 'self');
        }
    }
}
